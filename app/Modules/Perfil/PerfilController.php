<?php

declare(strict_types=1);

namespace App\Modules\Perfil;

use App\Core\AuditLogger;
use App\Core\Csrf;
use App\Core\Password;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Modules\Kyc\KycService;

/**
 * Perfil del usuario, direcciones y KYC.
 */
final class PerfilController
{
    public function __construct(
        private readonly PerfilRepository $repo,
        private readonly KycService $kyc,
        private readonly AvatarService $avatares,
        private readonly string $directorioVistas,
        private readonly ?AuditLogger $auditoria = null,
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

    private function usuarioId(): int
    {
        return (int) Session::obtener('user_id', 0);
    }

    private function rol(): string
    {
        $roles = Session::obtener('roles', []);

        return is_array($roles) && $roles !== [] ? (string) $roles[0] : 'comercio';
    }

    public function perfil(Request $peticion): Response
    {
        $id = $this->usuarioId();
        $rol = $this->rol();

        return $this->vista('perfil/index', [
            'titulo'      => 'Mi perfil',
            'rol'         => $rol,
            'perfil'      => $this->repo->perfil($id, $rol),
            'direcciones' => $this->repo->listarDirecciones($id),
            'kyc'         => $this->kyc->estado($id, $rol),
            'errores'     => [],
            'valores'     => [],
            'avatarUrl'   => AvatarService::url($id),
            'mensaje'     => null,
            'calificacion' => $this->repo->calificacion($id),
            'desglose'     => $this->repo->desgloseCalificaciones($id),
        ]);
    }

    /** POST /perfil/avatar */
    public function subirAvatar(Request $peticion): Response
    {
        $id = $this->usuarioId();

        try {
            $this->avatares->guardar($id, $peticion->file('avatar') ?? []);
            $mensaje = 'Foto actualizada.';
            $error = null;
        } catch (\Throwable $e) {
            $mensaje = null;
            $error = $e->getMessage();
        }

        return $this->vista('perfil/index', [
            'titulo'      => 'Mi perfil',
            'rol'         => $this->rol(),
            'perfil'      => $this->repo->perfil($id, $this->rol()),
            'direcciones' => $this->repo->listarDirecciones($id),
            'kyc'         => $this->kyc->estado($id, $this->rol()),
            'errores'     => $error !== null ? ['avatar' => $error] : [],
            'valores'     => [],
            'avatarUrl'   => AvatarService::url($id),
            'mensaje'     => $mensaje,
        ], $error !== null ? 422 : 200);
    }

    /** POST /perfil/avatar/quitar */
    public function quitarAvatar(Request $peticion): Response
    {
        $this->avatares->quitar($this->usuarioId());

        return Response::redirect('/perfil');
    }

    /** GET /avatar/{id} — sirve la imagen guardada fuera de public/. */
    public function avatar(Request $peticion, array $parametros): Response
    {
        $id = (int) ($parametros['id'] ?? 0);
        $ruta = $this->avatares->ruta($id);

        if ($ruta === null) {
            return Response::json(['error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Sin foto.']], 404);
        }

        $tipo = str_ends_with($ruta, '.webp') ? 'image/webp' : 'image/jpeg';

        return Response::archivo($ruta, $tipo);
    }

    public function guardarDireccion(Request $peticion): Response
    {
        $id = $this->usuarioId();
        $rol = $this->rol();

        $datos = [
            'alias'        => (string) $peticion->input('alias', ''),
            'calle'        => (string) $peticion->input('calle', ''),
            'colonia'      => (string) $peticion->input('colonia', ''),
            'ciudad'       => (string) $peticion->input('ciudad', ''),
            'estado_mx'    => (string) $peticion->input('estado_mx', ''),
            'cp'           => (string) $peticion->input('cp', ''),
            'latitud'      => (float) $peticion->input('latitud', 0),
            'longitud'     => (float) $peticion->input('longitud', 0),
            'referencias'  => (string) $peticion->input('referencias', ''),
            'es_principal' => (bool) $peticion->input('es_principal', false),
        ];

        $v = Validator::make($datos, [
            'calle'  => 'required|max:190',
            'ciudad' => 'required|max:100',
        ], ['calle' => 'calle', 'ciudad' => 'ciudad']);

        if ($datos['latitud'] === 0.0 || $datos['longitud'] === 0.0) {
            $v = Validator::make(['x' => ''], ['x' => 'required']);
        }

        if ($v->falla()) {
            return $this->vista('perfil/index', [
                'titulo'      => 'Mi perfil',
                'rol'         => $rol,
                'perfil'      => $this->repo->perfil($id, $rol),
                'direcciones' => $this->repo->listarDirecciones($id),
                'kyc'         => $this->kyc->estado($id, $rol),
                'errores'     => ['direccion' => 'Completa la calle, la ciudad y marca el punto en el mapa.'],
                'valores'     => $datos,
            ], 422);
        }

        $this->repo->guardarDireccion($id, $datos);

        return Response::redirect('/perfil');
    }

    public function kycForm(Request $peticion): Response
    {
        $id = $this->usuarioId();
        $rol = $this->rol();

        return $this->vista('perfil/kyc', [
            'titulo'  => 'Verificación de identidad',
            'rol'     => $rol,
            'kyc'     => $this->kyc->estado($id, $rol),
            'tipos'   => KycService::TIPOS,
            'mensaje' => null,
            'error'   => null,
        ]);
    }

    public function subirKyc(Request $peticion): Response
    {
        $id = $this->usuarioId();
        $rol = $this->rol();
        $tipo = (string) $peticion->input('tipo', '');
        $archivo = $peticion->file('documento') ?? [];

        $resultado = $this->kyc->subir($id, $tipo, $archivo, $peticion->ip());

        return $this->vista('perfil/kyc', [
            'titulo'  => 'Verificación de identidad',
            'rol'     => $rol,
            'kyc'     => $this->kyc->estado($id, $rol),
            'tipos'   => KycService::TIPOS,
            'mensaje' => $resultado['ok'] ? $resultado['mensaje'] : null,
            'error'   => $resultado['ok'] ? null : $resultado['mensaje'],
        ], $resultado['ok'] ? 200 : 422);
    }

    // --- Administración ----------------------------------------------------

    public function colaKyc(Request $peticion): Response
    {
        return $this->vista('perfil/admin-kyc', [
            'titulo'     => 'Cola de verificación',
            'pendientes' => $this->kyc->pendientes(),
            'mensaje'    => null,
        ]);
    }

    public function aprobarKyc(Request $peticion, array $args): Response
    {
        $this->kyc->aprobar((int) $args['id'], $this->usuarioId(), $peticion->ip());

        return Response::redirect('/admin/kyc');
    }

    public function rechazarKyc(Request $peticion, array $args): Response
    {
        $motivo = (string) $peticion->input('motivo', 'Documento ilegible');
        $this->kyc->rechazar((int) $args['id'], $this->usuarioId(), $motivo, $peticion->ip());

        return Response::redirect('/admin/kyc');
    }

    // ------------------------------------------------------- editar cuenta

    /** GET /perfil/editar */
    public function editarForm(Request $peticion): Response
    {
        return $this->formularioEdicion([], []);
    }

    /** POST /perfil/editar */
    public function editar(Request $peticion): Response
    {
        $id = $this->usuarioId();
        $rol = $this->rol();
        $cuenta = $this->repo->cuenta($id);

        $datos = [
            'nombre'          => trim((string) $peticion->input('nombre', '')),
            'email'           => trim((string) $peticion->input('email', '')),
            'telefono'        => trim((string) $peticion->input('telefono', '')),
            'actual'          => (string) $peticion->input('password_actual', ''),
            'nueva'           => (string) $peticion->input('password_nueva', ''),
            'repetir'         => (string) $peticion->input('password_repetir', ''),
            'calle'           => trim((string) $peticion->input('calle', '')),
            'colonia'         => trim((string) $peticion->input('colonia', '')),
            'ciudad'          => trim((string) $peticion->input('ciudad', '')),
            'estado_mx'       => trim((string) $peticion->input('estado_mx', '')),
            'cp'              => trim((string) $peticion->input('cp', '')),
            'referencias'     => trim((string) $peticion->input('referencias', '')),
        ];

        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) < 3) {
            $errores['nombre'] = 'Escribe el nombre con el que quieres aparecer.';
        }

        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Ese correo no parece válido.';
        } elseif ($this->repo->emailEnUso($datos['email'], $id)) {
            $errores['email'] = 'Ya hay otra cuenta con ese correo.';
        }

        $cambiarClave = $datos['nueva'] !== '' || $datos['repetir'] !== '';

        if ($cambiarClave) {
            if (!Password::verificar($datos['actual'], (string) ($cuenta['password_hash'] ?? ''))) {
                $errores['password_actual'] = 'La contraseña actual no coincide.';
            }

            if (mb_strlen($datos['nueva']) < Password::MINIMO) {
                $errores['password_nueva'] = 'La contraseña nueva necesita al menos ' . Password::MINIMO . ' caracteres.';
            } elseif ($datos['nueva'] !== $datos['repetir']) {
                $errores['password_repetir'] = 'Las contraseñas nuevas no coinciden.';
            }
        }

        if ($errores !== []) {
            return $this->formularioEdicion($errores, $datos, 422);
        }

        $this->repo->actualizarCuenta($id, $rol, $datos['nombre'], $datos['email']);

        if ($cambiarClave) {
            $this->repo->cambiarPassword($id, Password::hash($datos['nueva']));
            Csrf::regenerar();
        }

        if ($datos['calle'] !== '' && $datos['ciudad'] !== '') {
            $this->repo->guardarPrincipal($id, [
                'alias'        => 'Principal',
                'calle'        => $datos['calle'],
                'colonia'      => $datos['colonia'],
                'ciudad'       => $datos['ciudad'],
                'estado_mx'    => $datos['estado_mx'],
                'cp'           => $datos['cp'],
                'referencias'  => $datos['referencias'],
                'latitud'      => (float) $peticion->input('latitud', 0),
                'longitud'     => (float) $peticion->input('longitud', 0),
                'es_principal' => true,
            ]);
        }

        return Response::redirect('/perfil?ok=datos');
    }

    /** @param array<string,string> $errores */
    private function formularioEdicion(array $errores, array $valores, int $estado = 200): Response
    {
        $id = $this->usuarioId();
        $rol = $this->rol();
        $perfil = $this->repo->perfil($id, $rol) ?? [];
        $cuenta = $this->repo->cuenta($id);
        $direcciones = $this->repo->listarDirecciones($id);
        $principal = $direcciones[0] ?? [];

        $valores = $valores + [
            'nombre'      => (string) ($perfil['nombre_negocio'] ?? $perfil['nombre_comercial'] ?? $perfil['nombre'] ?? ''),
            'email'       => (string) ($cuenta['email'] ?? ''),
            'telefono'    => (string) ($cuenta['telefono'] ?? ''),
            'calle'       => (string) ($principal['calle'] ?? ''),
            'colonia'     => (string) ($principal['colonia'] ?? ''),
            'ciudad'      => (string) ($principal['ciudad'] ?? ''),
            'estado_mx'   => (string) ($principal['estado_mx'] ?? ''),
            'cp'          => (string) ($principal['cp'] ?? ''),
            'referencias' => (string) ($principal['referencias'] ?? ''),
        ];

        return $this->vista('perfil/editar', [
            'titulo'      => 'Editar cuenta',
            'rol'         => $rol,
            'errores'     => $errores,
            'valores'     => $valores,
            'avatarUrl'   => AvatarService::url($id),
            'tieneFoto'   => AvatarService::url($id) !== null,
            'latitud'     => $principal['latitud'] ?? '',
            'longitud'    => $principal['longitud'] ?? '',
            'mapa'        => true,
        ], $estado);
    }

    // ----------------------------------------------------------- privacidad

    /** GET /perfil/privacidad */
    public function privacidad(Request $peticion): Response
    {
        return $this->vista('perfil/privacidad', [
            'titulo'        => 'Privacidad',
            'preferencias'  => $this->repo->preferencias($this->usuarioId()),
            'guardado'      => $peticion->query('ok', '') !== '',
        ]);
    }

    /** POST /perfil/privacidad */
    public function guardarPrivacidad(Request $peticion): Response
    {
        $this->repo->guardarPreferencias($this->usuarioId(), [
            'notificaciones' => $peticion->input('notificaciones') !== null,
            'sms'       => $peticion->input('sms') !== null,
            'correo'    => $peticion->input('correo') !== null,
            'ubicacion' => $peticion->input('ubicacion') !== null,
        ]);

        return Response::redirect('/perfil/privacidad?ok=1');
    }

    // -------------------------------------------------------------- eliminar

    /** POST /perfil/eliminar */
    public function eliminar(Request $peticion): Response
    {
        $confirmacion = trim((string) $peticion->input('confirmacion', ''));

        if (mb_strtoupper($confirmacion) !== 'ELIMINAR') {
            return Response::redirect('/perfil?error=confirmacion');
        }

        $id = $this->usuarioId();
        $this->repo->eliminarCuenta($id, (string) $peticion->input('motivo', ''));
        $this->auditoria?->log('cuenta_eliminada', $id, 'usuario', (string) $id, null, $peticion->ip());

        Session::destruir();

        return Response::redirect('/login?cuenta=eliminada');
    }

    // -------------------------------------------------------------- ajustes

    /** GET /perfil/ajustes */
    public function ajustes(Request $peticion): Response
    {
        return $this->vista('perfil/ajustes', [
            'titulo' => 'Ajustes',
            'tema'   => $this->repo->tema($this->usuarioId()),
            'ok'     => $peticion->query('ok', '') !== '',
        ]);
    }

    /** GET /perfil/seguridad — contraseña y baja de la cuenta. */
    public function seguridad(Request $peticion): Response
    {
        return $this->vista('perfil/seguridad', [
            'titulo' => 'Seguridad',
        ]);
    }

    /** POST /perfil/password — cambio de contraseña desde Ajustes › Seguridad. */
    public function cambiarPassword(Request $peticion): Response
    {
        $id = $this->usuarioId();
        $cuenta = $this->repo->cuenta($id);

        $actual = (string) $peticion->input('password_actual', '');
        $nueva = (string) $peticion->input('password_nueva', '');
        $repetir = (string) $peticion->input('password_repetir', '');

        if (!Password::verificar($actual, (string) ($cuenta['password_hash'] ?? ''))) {
            return Response::redirect('/perfil/seguridad?error=actual');
        }

        if (mb_strlen($nueva) < Password::MINIMO) {
            return Response::redirect('/perfil/seguridad?error=longitud');
        }

        if ($nueva !== $repetir) {
            return Response::redirect('/perfil/seguridad?error=repetir');
        }

        $this->repo->cambiarPassword($id, Password::hash($nueva));
        Csrf::regenerar();

        return Response::redirect('/perfil/seguridad?ok=1');
    }

    /** GET /perfil/permisos — permisos del dispositivo. */
    public function permisos(Request $peticion): Response
    {
        return $this->vista('perfil/permisos', ['titulo' => 'Permisos del dispositivo']);
    }

    /** POST /perfil/tema */
    public function guardarTema(Request $peticion): Response
    {
        $this->repo->guardarTema($this->usuarioId(), (string) $peticion->input('tema', 'claro'));

        return Response::redirect('/perfil/ajustes?ok=1');
    }

    /** GET /ayuda */
    public function ayuda(Request $peticion): Response
    {
        return $this->vista('perfil/ayuda', ['titulo' => 'Ayuda']);
    }


    /** POST /perfil/notificaciones — interruptor maestro, desde dos pantallas. */
    public function notificaciones(Request $peticion): Response
    {
        $activo = (string) $peticion->input('notificaciones', '') === '1';
        $this->repo->guardarNotificaciones($this->usuarioId(), $activo);

        $volver = (string) $peticion->input('volver', '/perfil/privacidad');
        if (!str_starts_with($volver, '/') || str_starts_with($volver, '//')) {
            $volver = '/perfil/privacidad';
        }

        return Response::redirect($volver . (str_contains($volver, '?') ? '&' : '?') . 'ok=1');
    }

    // -------------------------------------------------------- calificaciones

    /** POST /calificar */
    public function calificar(Request $peticion): Response
    {
        $evaluador = $this->usuarioId();
        $evaluado  = (int) $peticion->input('evaluado_id', 0);
        $puntaje   = (int) $peticion->input('puntaje', 0);
        $pedido    = (int) $peticion->input('pedido_id', 0) ?: null;

        // Solo se vuelve a una ruta interna: evita que el formulario sirva de
        // puente para redirigir a otro sitio.
        $volver = (string) $peticion->input('volver', '/perfil');
        if (!str_starts_with($volver, '/') || str_starts_with($volver, '//')) {
            $volver = '/perfil';
        }

        $permitidos = ['comercio', 'mayorista', 'repartidor'];
        $rolEvaluador = $this->repo->rolDe($evaluador);
        $rolEvaluado  = $this->repo->rolDe($evaluado);

        $valido = $evaluado > 0
            && $evaluado !== $evaluador
            && $puntaje >= 1 && $puntaje <= 5
            && in_array($rolEvaluador, $permitidos, true)
            && in_array($rolEvaluado, $permitidos, true);

        if (!$valido) {
            return Response::redirect($volver . (str_contains($volver, '?') ? '&' : '?') . 'cal=error');
        }

        $this->repo->calificar(
            $evaluador,
            $evaluado,
            $puntaje,
            $pedido,
            (string) $peticion->input('comentario', ''),
        );

        return Response::redirect($volver . (str_contains($volver, '?') ? '&' : '?') . 'cal=ok');
    }
}
