<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Traduce HTTP a llamadas del servicio. No valida reglas de negocio ni escribe
 * SQL: eso vive en AuthService y UsuarioRepository.
 */
final class AuthController
{
    public function __construct(
        private readonly AuthService $servicio,
        private readonly string $directorioVistas,
        private readonly ?\App\Modules\Perfil\PerfilRepository $perfiles = null,
    ) {
    }

    private function vista(string $plantilla, array $datos, int $estado = 200): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $vista = new View($this->directorioVistas, $nonce);

        $datos['nonce'] ??= $nonce;
        $datos['csrf'] ??= Csrf::token();

        return Response::html($vista->render($plantilla, $datos), $estado)->withNonce($nonce);
    }

    // --- Registro ----------------------------------------------------------

    public function registroForm(Request $peticion): Response
    {
        Session::iniciar();

        return $this->vista('auth/registro', ['titulo' => 'Crear cuenta', 'errores' => [], 'valores' => []]);
    }

    public function registro(Request $peticion): Response
    {
        Session::iniciar();

        $datos = [
            'nombre_negocio' => (string) $peticion->input('nombre_negocio', ''),
            'email'          => (string) $peticion->input('email', ''),
            'telefono'       => (string) $peticion->input('telefono', ''),
            'password'       => (string) $peticion->input('password', ''),
            'rol'            => (string) $peticion->input('rol', 'comercio'),
        ];

        $confirmacion = (string) $peticion->input('password_confirmacion', '');

        if ($confirmacion !== '' && $confirmacion !== $datos['password']) {
            return $this->vista('auth/registro', [
                'titulo'  => 'Crear cuenta',
                'errores' => ['password_confirmacion' => 'Las contraseñas no coinciden.'],
                'valores' => $datos,
            ], 422);
        }

        $resultado = $this->servicio->registrar($datos, $peticion->ip());

        if (!$resultado['ok']) {
            return $this->vista('auth/registro', [
                'titulo'  => 'Crear cuenta',
                'errores' => $resultado['errores'],
                'valores' => $datos,
            ], 422);
        }

        // El perfil del rol se crea junto con la cuenta.
        if ($resultado['usuario_id'] !== null && $this->perfiles !== null) {
            $this->perfiles->asegurarPerfil(
                (int) $resultado['usuario_id'],
                (string) $datos['rol'],
                (string) $datos['nombre_negocio'],
            );
        }

        // En producción el token viaja por correo. En local se muestra para
        // poder probar el flujo completo sin servidor de correo.
        return $this->vista('auth/mensaje', [
            'titulo'  => 'Revisa tu correo',
            'mensaje' => $resultado['mensaje'],
            'enlace'  => $resultado['token'] !== null
                ? '/verificar?token=' . urlencode($resultado['token'])
                : null,
        ]);
    }

    // --- Login / logout ----------------------------------------------------

    public function loginForm(Request $peticion): Response
    {
        Session::iniciar();

        $error = match (true) {
            $peticion->query('expirado') !== null  => 'Tu sesión expiró por inactividad. Vuelve a escribir tus datos.',
            $peticion->query('bloqueado') !== null => 'Demasiados intentos con esta cuenta. Espera unos minutos.',
            default                                => null,
        };

        return $this->vista('auth/login', ['titulo' => 'Iniciar sesión', 'error' => $error, 'email' => '']);
    }

    public function login(Request $peticion): Response
    {
        Session::iniciar();

        $email = (string) $peticion->input('email', '');
        $password = (string) $peticion->input('password', '');

        $resultado = $this->servicio->login($email, $password, $peticion->ip());

        if (!$resultado['ok']) {
            return $this->vista('auth/login', [
                'titulo' => 'Iniciar sesión',
                'error'  => $resultado['mensaje'],
                'email'  => $email,
            ], 401);
        }

        Session::regenerar();
        Session::poner('user_id', (int) $resultado['usuario']['id']);
        Session::poner('uuid', (string) $resultado['usuario']['uuid']);
        Session::poner('email', (string) $resultado['usuario']['email']);
        Session::poner('roles', $resultado['roles']);
        Csrf::regenerar();

        return Response::redirect($this->panelSegunRol($resultado['roles']));
    }

    public function logout(Request $peticion): Response
    {
        Session::iniciar();
        Session::destruir();

        return Response::redirect('/login');
    }

    /** @param string[] $roles */
    private function panelSegunRol(array $roles): string
    {
        return match (true) {
            in_array('admin_total', $roles, true) => '/admin',
            in_array('mayorista', $roles, true)   => '/mayorista',
            in_array('repartidor', $roles, true)  => '/repartidor',
            default                               => '/explorar',
        };
    }

    // --- Verificación de correo -------------------------------------------

    public function verificar(Request $peticion): Response
    {
        $token = (string) $peticion->query('token', '');
        $ok = $token !== '' && $this->servicio->verificarEmail($token);

        return $this->vista('auth/mensaje', [
            'titulo'  => $ok ? 'Cuenta confirmada' : 'Enlace no válido',
            'mensaje' => $ok
                ? 'Tu correo quedó confirmado. Ya puedes iniciar sesión.'
                : 'El enlace expiró o ya se usó. Solicita uno nuevo.',
            'enlace'  => $ok ? '/login' : '/recuperar',
        ], $ok ? 200 : 400);
    }

    // --- Recuperación de contraseña ---------------------------------------

    public function recuperarForm(Request $peticion): Response
    {
        Session::iniciar();

        return $this->vista('auth/recuperar', ['titulo' => 'Recuperar contraseña', 'mensaje' => null, 'enlace' => null]);
    }

    public function recuperar(Request $peticion): Response
    {
        Session::iniciar();

        $identificador = (string) $peticion->input('identificador', $peticion->input('email', ''));
        $resultado = $this->servicio->solicitarRecuperacion($identificador, $peticion->ip());

        return $this->vista('auth/recuperar', [
            'titulo'  => 'Recuperar contraseña',
            'mensaje' => $resultado['mensaje'],
            'enlace'  => $resultado['token'] !== null
                ? '/restablecer?token=' . urlencode($resultado['token'])
                : null,
        ]);
    }

    public function restablecerForm(Request $peticion): Response
    {
        Session::iniciar();

        return $this->vista('auth/restablecer', [
            'titulo' => 'Nueva contraseña',
            'token'  => (string) $peticion->query('token', ''),
            'error'  => null,
        ]);
    }

    public function restablecer(Request $peticion): Response
    {
        Session::iniciar();

        $token = (string) $peticion->input('token', '');
        $nueva = (string) $peticion->input('password', '');

        if (!$this->servicio->restablecer($token, $nueva)) {
            return $this->vista('auth/restablecer', [
                'titulo' => 'Nueva contraseña',
                'token'  => $token,
                'error'  => 'El enlace no es válido o la contraseña es demasiado corta (mínimo 12).',
            ], 422);
        }

        return $this->vista('auth/mensaje', [
            'titulo'  => 'Contraseña actualizada',
            'mensaje' => 'Ya puedes iniciar sesión con tu nueva contraseña.',
            'enlace'  => '/login',
        ]);
    }
}
