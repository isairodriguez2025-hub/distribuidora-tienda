<?php

declare(strict_types=1);

namespace App\Modules\Pedidos;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

final class PedidosController
{
    /** acción de la URL => estado destino */
    private const ACCIONES = [
        'aceptar'      => 'aceptado',
        'rechazar'     => 'rechazado',
        'preparando'   => 'preparando',
        'listo'        => 'listo_para_recoger',
        'buscar-reparto' => 'buscando_repartidor',
        'cancelar'     => 'cancelado',
        'en-camino'    => 'en_camino',
        'en-puerta'    => 'en_puerta',
    ];

    public function __construct(
        private readonly PedidoService $servicio,
        private readonly PDO $pdo,
        private readonly string $directorioVistas,
        private readonly ?\App\Modules\Pagos\PagoService $pagos = null,
        private readonly ?\App\Modules\Logistica\AsignacionService $asignacion = null,
        private readonly ?\App\Modules\Logistica\PruebaEntregaService $pruebas = null,
        private readonly ?\App\Modules\Confianza\ChatService $chat = null,
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

    /** @return string[] */
    private function roles(): array
    {
        $roles = Session::obtener('roles', []);

        return is_array($roles) ? array_values($roles) : [];
    }

    private function mayoristaId(): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM mayoristas_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$this->usuarioId()]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    private function repartidorId(): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM repartidores_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$this->usuarioId()]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** ¿El envío del pedido está asignado a este repartidor? */
    private function esSuEnvio(int $pedidoId): bool
    {
        $repartidorId = $this->repartidorId();

        if ($repartidorId === null) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT 1 FROM envios WHERE pedido_id = ? AND repartidor_id = ? LIMIT 1');
        $stmt->execute([$pedidoId, $repartidorId]);

        return $stmt->fetchColumn() !== false;
    }

    private function comercioId(): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM comercios_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$this->usuarioId()]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function pedidoPorUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, m.nombre_comercial AS mayorista, c.nombre_negocio AS comercio,
                    d.calle, d.colonia, d.ciudad, d.cp
             FROM pedidos p
             JOIN mayoristas_perfil m ON m.id = p.mayorista_id
             JOIN comercios_perfil c ON c.id = p.comercio_id
             JOIN direcciones d ON d.id = p.direccion_entrega_id
             WHERE p.uuid = ?
             LIMIT 1'
        );
        $stmt->execute([$uuid]);

        return $stmt->fetch() ?: null;
    }

    public function detallePedido(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM detalle_pedido WHERE pedido_id = ? ORDER BY id');
        $stmt->execute([$id]);

        return $stmt->fetchAll() ?: [];
    }

    /** GET /mayorista/pedidos — bandeja con contador regresivo. */
    public function bandeja(Request $peticion): Response
    {
        $mayoristaId = $this->mayoristaId();

        if ($mayoristaId === null) {
            return Response::redirect('/perfil');
        }

        $estado = (string) $peticion->query('estado', '');

        $sql = 'SELECT p.*, c.nombre_negocio AS comercio,
                       (SELECT COUNT(*) FROM detalle_pedido d WHERE d.pedido_id = p.id) AS lineas
                FROM pedidos p
                JOIN comercios_perfil c ON c.id = p.comercio_id
                WHERE p.mayorista_id = ?';
        $params = [$mayoristaId];

        // Los pedidos en borrador aún no se le presentan al mayorista.
        if ($estado !== '') {
            $sql .= ' AND p.estado = ?';
            $params[] = $estado;
        } else {
            $sql .= " AND p.estado <> 'borrador'";
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT 100';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $conteo = $this->pdo->prepare(
            'SELECT estado, COUNT(*) n FROM pedidos WHERE mayorista_id = ? AND estado <> \'borrador\' GROUP BY estado'
        );
        $conteo->execute([$mayoristaId]);

        $contadores = [];
        foreach ($conteo->fetchAll() ?: [] as $fila) {
            $contadores[$fila['estado']] = (int) $fila['n'];
        }

        return $this->vista('pedidos/bandeja', [
            'titulo'     => 'Pedidos recibidos',
            'pedidos'    => $stmt->fetchAll() ?: [],
            'contadores' => $contadores,
            'filtro'     => $estado,
        ]);
    }

    /** GET /pedidos — historial del comercio. */
    public function misPedidos(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        $stmt = $this->pdo->prepare(
            'SELECT p.*, m.nombre_comercial AS mayorista,
                    (SELECT COUNT(*) FROM detalle_pedido d WHERE d.pedido_id = p.id) AS lineas
             FROM pedidos p
             JOIN mayoristas_perfil m ON m.id = p.mayorista_id
             WHERE p.comercio_id = ?
             ORDER BY p.created_at DESC LIMIT 100'
        );
        $stmt->execute([$comercioId]);

        return $this->vista('pedidos/mis-pedidos', [
            'titulo'  => 'Mis pedidos',
            'pedidos' => $stmt->fetchAll() ?: [],
        ]);
    }

    /** GET /pedidos/{uuid} */
    public function detalle(Request $peticion, array $args): Response
    {
        $pedido = $this->pedidoPorUuid((string) $args['uuid']);

        if ($pedido === null) {
            return Response::json(['ok' => false, 'error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Pedido no encontrado.']], 404);
        }

        $esMayorista = $this->mayoristaId() === (int) $pedido['mayorista_id'];
        $esComercio = $this->comercioId() === (int) $pedido['comercio_id'];
        $esRepartidor = $this->esSuEnvio((int) $pedido['id']);

        if (!$esMayorista && !$esComercio && !$esRepartidor) {
            return Response::json(['ok' => false, 'error' => ['code' => 'PROHIBIDO', 'message' => 'Este pedido no es tuyo.']], 403);
        }

        $chatAbierto = $this->chat !== null && $this->chat->disponible((int) $pedido['id']);

        return $this->vista('pedidos/detalle', [
            'titulo'      => 'Pedido ' . substr((string) $pedido['uuid'], 0, 8),
            'mensajes'    => $chatAbierto ? $this->chat->mensajes((int) $pedido['id']) : [],
            'chatAbierto' => $chatAbierto,
            'pedido'      => $pedido,
            'lineas'      => $this->detallePedido((int) $pedido['id']),
            'eventos'     => $this->servicio->eventos((int) $pedido['id']),
            'esMayorista'  => $esMayorista,
            'esComercio'   => $esComercio,
            'esRepartidor' => $esRepartidor,
            'mensaje'     => $peticion->query('ok') !== null ? 'Pedido actualizado.' : null,
            'error'       => $peticion->query('error'),
            'calificacion' => $this->datosCalificacion($pedido, $esComercio, $esMayorista),
        ]);
    }

    /** POST /pedidos/{uuid}/{accion} */
    public function accion(Request $peticion, array $args): Response
    {
        $uuid = (string) $args['uuid'];
        $accion = (string) $args['accion'];
        $pedido = $this->pedidoPorUuid($uuid);

        if ($pedido === null || !isset(self::ACCIONES[$accion])) {
            return Response::json(['ok' => false, 'error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Acción o pedido inválido.']], 404);
        }

        // El rol no basta: hay que ser dueño del pedido. Sin esto, cualquier
        // mayorista podría aceptar o cancelar pedidos de otro.
        $esMayoristaDueno = $this->mayoristaId() === (int) $pedido['mayorista_id'];
        $esComercioDueno = $this->comercioId() === (int) $pedido['comercio_id'];
        $esRepartidorDueno = $this->esSuEnvio((int) $pedido['id']);

        if (!$esMayoristaDueno && !$esComercioDueno && !$esRepartidorDueno) {
            return Response::json([
                'ok'    => false,
                'error' => ['code' => 'PROHIBIDO', 'message' => 'Este pedido no es tuyo.'],
            ], 403);
        }

        $resultado = $this->servicio->transicionar(
            (int) $pedido['id'],
            self::ACCIONES[$accion],
            $this->usuarioId(),
            $this->roles(),
            $peticion->input('motivo') !== null ? (string) $peticion->input('motivo') : null,
        );

        // Efectos secundarios de la transición.
        if ($resultado['ok']) {
            $nuevo = self::ACCIONES[$accion];

            if ($nuevo === 'listo_para_recoger' && $this->pruebas !== null) {
                $this->pruebas->generarRecoleccion((int) $pedido['id']);
            }

            if ($nuevo === 'buscando_repartidor' && $this->asignacion !== null) {
                $this->asignacion->lanzarRonda((int) $pedido['id'], 1);
            }

            if ($nuevo === 'en_puerta' && $this->pruebas !== null) {
                $this->pruebas->generarEntrega((int) $pedido['id']);
            }
        }

        $volver = $this->mayoristaId() === (int) $pedido['mayorista_id'] ? '/mayorista/pedidos' : '/pedidos/' . $uuid;

        return Response::redirect($volver . ($resultado['ok'] ? '?ok=1' : '?error=' . urlencode($resultado['mensaje'])));
    }

    /** POST /pedidos/{uuid}/pagar — pago simulado (el real es el bloque 8). */
    public function pagar(Request $peticion, array $args): Response
    {
        $pedido = $this->pedidoPorUuid((string) $args['uuid']);

        if ($pedido === null) {
            return Response::redirect('/pedidos');
        }

        $id = (int) $pedido['id'];

        // Se marca la intención de pago y se manda a la pasarela. El pedido NO
        // avanza aquí: avanza cuando la pasarela confirma por webhook.
        $this->servicio->transicionar($id, 'pendiente_pago', $this->usuarioId(), $this->roles(), 'Confirmado por el comercio');

        if ($this->pagos !== null) {
            $intencion = $this->pagos->crearIntencion((int) $pedido['checkout_id']);

            if ($intencion['ok']) {
                return Response::redirect($intencion['intencion']['url_pago']);
            }
        }

        return Response::redirect('/pedidos/' . $pedido['uuid'] . '?error=' . urlencode('No se pudo iniciar el pago.'));
    }

    /** GET /pedidos/{uuid}/surtido — hoja imprimible. */
    public function surtido(Request $peticion, array $args): Response
    {
        $pedido = $this->pedidoPorUuid((string) $args['uuid']);

        if ($pedido === null) {
            return Response::redirect('/mayorista/pedidos');
        }

        return $this->vista('pedidos/surtido', [
            'titulo' => 'Hoja de surtido',
            'pedido' => $pedido,
            'lineas' => $this->detallePedido((int) $pedido['id']),
        ]);
    }

    /**
     * Datos para el bloque de calificación del detalle: a quién se puntúa y
     * qué puntaje puso ya este usuario, si lo hizo.
     *
     * @return array{evaluado: int, nombre: string, mio: ?int, puede: bool}
     */
    private function datosCalificacion(array $pedido, bool $esComercio, bool $esMayorista): array
    {
        $vacio = ['evaluado' => 0, 'nombre' => '', 'mio' => null, 'puede' => false];

        if (!$esComercio && !$esMayorista) {
            return $vacio;
        }

        // En pedidos, comercio_id y mayorista_id apuntan al perfil del rol,
        // no al usuario: hay que traducirlos para calificar a la persona.
        $tablaPerfil = $esComercio ? 'mayoristas_perfil' : 'comercios_perfil';
        $perfilId = $esComercio ? (int) $pedido['mayorista_id'] : (int) $pedido['comercio_id'];

        if ($perfilId <= 0) {
            return $vacio;
        }

        $stmt = $this->pdo->prepare("SELECT usuario_id FROM {$tablaPerfil} WHERE id = ? LIMIT 1");
        $stmt->execute([$perfilId]);
        $evaluado = (int) ($stmt->fetchColumn() ?: 0);

        if ($evaluado <= 0) {
            return $vacio;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(m.nombre_comercial, c.nombre_negocio, u.nombre, u.email) AS nombre
               FROM usuarios u
               LEFT JOIN mayoristas_perfil m ON m.usuario_id = u.id
               LEFT JOIN comercios_perfil c ON c.usuario_id = u.id
              WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$evaluado]);
        $nombre = (string) ($stmt->fetchColumn() ?: 'la otra parte');

        $stmt = $this->pdo->prepare('SELECT puntaje FROM calificaciones WHERE evaluador_id = ? AND evaluado_id = ? LIMIT 1');
        $stmt->execute([$this->usuarioId(), $evaluado]);
        $mio = $stmt->fetchColumn();

        return [
            'evaluado' => $evaluado,
            'nombre'   => $nombre,
            'mio'      => $mio === false ? null : (int) $mio,
            'puede'    => in_array((string) $pedido['estado'], ['recibido', 'entregado', 'completado'], true),
        ];
    }
}
