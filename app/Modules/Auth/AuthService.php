<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\AuditLogger;
use App\Core\Password;
use App\Core\Validator;

/**
 * Reglas de negocio de la autenticación. El controlador no valida ni decide
 * nada: solo traduce HTTP a llamadas a este servicio.
 */
final class AuthService
{
    public const MAX_FALLOS_CUENTA  = 10;
    public const MAX_FALLOS_IP      = 5;
    public const VENTANA_MINUTOS    = 15;
    public const TTL_EMAIL_MIN      = 1440;  // 24 h
    public const TTL_RECUPERO_MIN   = 30;

    /** Mensaje único: no revela si el correo existe o no (SEG-02). */
    public const MSG_REGISTRO = 'Si el correo es válido, te enviamos un enlace de confirmación.';
    public const MSG_RECUPERO = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.';
    public const MSG_CREDENCIALES = 'Correo o contraseña incorrectos.';

    public function __construct(
        private readonly UsuarioRepository $repo,
        private readonly AuditLogger $auditoria,
        private readonly ?\App\Modules\Notificaciones\NotificacionService $notificaciones = null,
    ) {
    }

    /**
     * Registro. Devuelve siempre el mismo mensaje, exista o no el correo.
     *
     * @return array{ok: bool, mensaje: string, errores: array<string, string>, token: ?string, usuario_id: ?int}
     */
    public function registrar(array $datos, string $ip): array
    {
        $v = Validator::make($datos, [
            'nombre_negocio' => 'required|max:150',
            'email'          => 'required|email|max:190',
            'telefono'       => 'required|telefono',
            'password'       => 'required|min:' . Password::MINIMO,
            'rol'            => 'required|in:comercio,mayorista,repartidor',
        ], [
            'nombre_negocio' => 'nombre del negocio',
            'email'          => 'correo',
            'telefono'       => 'teléfono',
            'password'       => 'contraseña',
            'rol'            => 'rol',
        ]);

        if ($v->falla()) {
            return ['ok' => false, 'mensaje' => $v->primerError() ?? 'Datos inválidos.', 'errores' => $v->errores(), 'token' => null, 'usuario_id' => null];
        }

        $email = (string) $datos['email'];
        $existente = $this->repo->buscarPorEmail($email);

        if ($existente !== null) {
            // Mismo tiempo de respuesta que un alta real.
            Password::verificarFicticio((string) $datos['password']);
            $this->auditoria->log('registro_duplicado', null, 'usuario', null, ['email' => $email], $ip);

            return ['ok' => true, 'mensaje' => self::MSG_REGISTRO, 'errores' => [], 'token' => null, 'usuario_id' => null];
        }

        $id = $this->repo->crear(
            $email,
            Password::hash((string) $datos['password']),
            (string) $datos['telefono'],
            (string) $datos['rol'],
        );

        $token = bin2hex(random_bytes(32));
        $this->repo->crearToken($id, 'email', hash('sha256', $token), self::TTL_EMAIL_MIN);
        $this->auditoria->log('registro', $id, 'usuario', (string) $id, ['rol' => $datos['rol']], $ip);

        return ['ok' => true, 'mensaje' => self::MSG_REGISTRO, 'errores' => [], 'token' => $token, 'usuario_id' => $id];
    }

    /**
     * Login. Aplica rate limit, verifica y devuelve el usuario con sus roles.
     *
     * @return array{ok: bool, mensaje: string, usuario: ?array, roles: string[], codigo: string}
     */
    public function login(string $email, string $password, string $ip): array
    {
        $fallosCuenta = $this->repo->contarFallos(self::VENTANA_MINUTOS, $email, null);
        $fallosIp     = $this->repo->contarFallos(self::VENTANA_MINUTOS, null, $ip);

        if ($fallosCuenta >= self::MAX_FALLOS_CUENTA || $fallosIp >= self::MAX_FALLOS_IP) {
            $this->auditoria->log('login_bloqueado', null, 'usuario', null, ['email' => $email], $ip);

            return ['ok' => false, 'mensaje' => 'Demasiados intentos. Espera unos minutos.', 'usuario' => null, 'roles' => [], 'codigo' => 'BLOQUEADO'];
        }

        $usuario = $this->repo->buscarPorEmail($email);

        if ($usuario === null) {
            // Tiempo uniforme: sin esto se puede enumerar correos midiendo.
            Password::verificarFicticio($password);
            $this->repo->registrarIntento($email, $ip, false);
            $this->auditoria->log('login_fallido', null, 'usuario', null, ['motivo' => 'inexistente'], $ip);

            return ['ok' => false, 'mensaje' => self::MSG_CREDENCIALES, 'usuario' => null, 'roles' => [], 'codigo' => 'CREDENCIALES'];
        }

        if (!Password::verificar($password, (string) $usuario['password_hash'])) {
            $this->repo->registrarIntento($email, $ip, false);
            $this->auditoria->log('login_fallido', (int) $usuario['id'], 'usuario', (string) $usuario['id'], ['motivo' => 'password'], $ip);

            return ['ok' => false, 'mensaje' => self::MSG_CREDENCIALES, 'usuario' => null, 'roles' => [], 'codigo' => 'CREDENCIALES'];
        }

        if ($usuario['estado'] === 'suspendido' || $usuario['estado'] === 'baneado') {
            $this->auditoria->log('login_rechazado', (int) $usuario['id'], 'usuario', (string) $usuario['id'], ['estado' => $usuario['estado']], $ip);

            return ['ok' => false, 'mensaje' => 'Tu cuenta está suspendida. Contacta a soporte.', 'usuario' => null, 'roles' => [], 'codigo' => 'SUSPENDIDO'];
        }

        // Rehash transparente si el algoritmo subió de costo.
        if (Password::necesitaRehash((string) $usuario['password_hash'])) {
            $this->repo->actualizarPassword((int) $usuario['id'], Password::hash($password));
        }

        $this->repo->registrarIntento($email, $ip, true);
        $this->repo->actualizarUltimoLogin((int) $usuario['id']);
        $this->repo->registrarSesion((int) $usuario['id'], $ip, $_SERVER['HTTP_USER_AGENT'] ?? null);
        $this->auditoria->log('login_exitoso', (int) $usuario['id'], 'usuario', (string) $usuario['id'], null, $ip);

        return [
            'ok'      => true,
            'mensaje' => 'Bienvenido.',
            'usuario' => $usuario,
            'roles'   => $this->repo->roles((int) $usuario['id']),
            'codigo'  => 'OK',
        ];
    }

    public function verificarEmail(string $token): bool
    {
        $registro = $this->repo->buscarToken(hash('sha256', $token), 'email');

        if ($registro === null) {
            return false;
        }

        $this->repo->marcarTokenUsado((int) $registro['id']);
        $this->repo->marcarEmailVerificado((int) $registro['usuario_id']);
        $this->auditoria->log('email_verificado', (int) $registro['usuario_id']);

        return true;
    }

    /**
     * Acepta correo o teléfono. Siempre responde lo mismo y siempre tarda
     * parecido, exista o no la cuenta.
     *
     * @return array{ok: bool, mensaje: string, token: ?string}
     */
    public function solicitarRecuperacion(string $identificador, string $ip): array
    {
        $identificador = trim($identificador);
        $esCorreo = str_contains($identificador, '@');

        $usuario = $esCorreo
            ? $this->repo->buscarPorEmail($identificador)
            : $this->repo->buscarPorTelefono($identificador);

        if ($usuario === null) {
            Password::verificarFicticio($identificador);

            return ['ok' => true, 'mensaje' => self::MSG_RECUPERO, 'token' => null];
        }

        $this->repo->invalidarTokens((int) $usuario['id'], 'recuperacion_pass');

        $token = bin2hex(random_bytes(32));
        $this->repo->crearToken((int) $usuario['id'], 'recuperacion_pass', hash('sha256', $token), self::TTL_RECUPERO_MIN);

        $enlace = '/restablecer?token=' . urlencode($token);
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if ($this->notificaciones !== null) {
            $this->notificaciones->encolar(
                (int) $usuario['id'],
                'recuperacion',
                'Recupera tu contraseña de Ares',
                'Abre este enlace para elegir una contraseña nueva. Caduca en 30 minutos.\n\n'
                    . 'Si no fuiste tú, ignora este mensaje: tu contraseña sigue igual.',
                $enlace,
                ['correo'],
            );

            $this->notificaciones->encolar(
                (int) $usuario['id'],
                'recuperacion',
                'Código de recuperación Ares',
                'Tu código de Ares es ' . $codigo . '. Caduca en 30 minutos.',
                null,
                ['sms'],
            );

            $this->notificaciones->enviarPendientes(10);
        }

        $this->auditoria->log('recuperacion_solicitada', (int) $usuario['id'], 'usuario', (string) $usuario['id'], null, $ip);

        // En local se devuelve el token para poder probar sin servidor de
        // correo; en producción nunca sale de aquí.
        $entorno = strtolower((string) (getenv('APP_ENV') ?: 'production'));

        return [
            'ok'      => true,
            'mensaje' => self::MSG_RECUPERO,
            'token'   => $entorno === 'production' ? null : $token,
        ];
    }

    public function restablecer(string $token, string $nueva): bool
    {
        if (mb_strlen($nueva) < Password::MINIMO) {
            return false;
        }

        $registro = $this->repo->buscarToken(hash('sha256', $token), 'recuperacion_pass');

        if ($registro === null) {
            return false;
        }

        $this->repo->marcarTokenUsado((int) $registro['id']);
        $this->repo->actualizarPassword((int) $registro['usuario_id'], Password::hash($nueva));
        $this->auditoria->log('password_restablecida', (int) $registro['usuario_id']);

        return true;
    }
}
