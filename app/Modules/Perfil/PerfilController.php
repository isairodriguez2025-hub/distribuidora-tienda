<?php

declare(strict_types=1);

namespace App\Modules\Perfil;

use App\Core\Csrf;
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
}
