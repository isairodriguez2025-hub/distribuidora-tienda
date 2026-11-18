<?php

declare(strict_types=1);

namespace App\Modules\Finanzas;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

/**
 * Finanzas del comercio: estado de gastos con gráfica y facturación con las
 * facturas que emite el mayorista.
 */
final class FinanzasController
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    private const ESTADOS = [
        'pendiente_aceptacion' => 'Por aceptar',
        'buscando_repartidor'  => 'Buscando repartidor',
        'en_camino'            => 'En camino',
        'recibido'             => 'Recibido',
        'cancelado'            => 'Cancelado',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $directorioVistas,
    ) {
    }

    public function index(Request $peticion): Response
    {
        $usuarioId = $this->usuarioId();
        $tab = (string) $peticion->query('tab', 'gastos');

        if (!in_array($tab, ['gastos', 'facturacion'], true)) {
            $tab = 'gastos';
        }

        $periodo = (string) $peticion->query('periodo', date('Y'));
        $periodo = preg_match('/^\d{4}(-\d{2})?$/', $periodo) === 1 ? $periodo : date('Y');

        return $this->vista('finanzas/index', [
            'titulo'    => 'Finanzas',
            'tab'       => $tab,
            'periodo'   => $periodo,
            'gastos'    => $this->gastos($usuarioId, $periodo),
            'facturas'  => $this->facturas($usuarioId),
        ]);
    }

    /** Hoja de la factura emitida por el mayorista. */
    public function factura(Request $peticion, array $args): Response
    {
        $uuid = (string) ($args['uuid'] ?? '');

        $stmt = $this->pdo->prepare(
            'SELECT f.*, p.uuid AS pedido_uuid
               FROM facturas f
               JOIN pedidos p ON p.id = f.pedido_id
              WHERE f.uuid = ? AND f.receptor_usuario_id = ?
              LIMIT 1'
        );
        $stmt->execute([$uuid, $this->usuarioId()]);
        $factura = $stmt->fetch();

        if ($factura === false) {
            return Response::redirect('/finanzas?tab=facturacion');
        }

        $lineas = $this->lineas((int) $factura['pedido_id']);

        return $this->vista('finanzas/factura', [
            'titulo'  => 'Factura ' . $factura['serie'] . '-' . str_pad((string) $factura['folio'], 6, '0', STR_PAD_LEFT),
            'factura' => $factura,
            'lineas'  => $lineas,
        ]);
    }

    // ------------------------------------------------------------------ datos

    private function gastos(int $comercioId, string $periodo): array
    {
        $esMes = str_contains($periodo, '-');
        $anio = (int) substr($periodo, 0, 4);
        $mes = $esMes ? (int) substr($periodo, 5, 2) : null;

        $sql = 'SELECT p.uuid, p.total, p.estado, p.created_at, m.nombre_comercial AS mayorista
                  FROM pedidos p
             LEFT JOIN mayoristas_perfil m ON m.usuario_id = p.mayorista_id
                 WHERE p.comercio_id = :comercio AND YEAR(p.created_at) = :anio';
        $parametros = ['comercio' => $comercioId, 'anio' => $anio];

        if ($esMes) {
            $sql .= ' AND MONTH(p.created_at) = :mes';
            $parametros['mes'] = $mes;
        }

        $sql .= ' ORDER BY p.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        $pedidos = $stmt->fetchAll();

        $total = 0.0;
        foreach ($pedidos as $p) {
            $total += (float) $p['total'];
        }

        return [
            'pedidos'  => $pedidos,
            'total'    => $total,
            'serie'    => $this->serie($pedidos, $anio, $mes),
            'esMes'    => $esMes,
            'etiqueta' => $esMes
                ? sprintf('%s de %d', ucfirst(self::MESES[$mes - 1]), $anio)
                : sprintf('Año %d', $anio),
            'estados'  => self::ESTADOS,
        ];
    }

    /**
     * Agrupa los pedidos en meses (vista anual) o en días (vista mensual).
     * Devuelve etiqueta, valor y el alto relativo para pintar la gráfica.
     */
    private function serie(array $pedidos, int $anio, ?int $mes): array
    {
        $cubetas = [];

        if ($mes === null) {
            for ($i = 1; $i <= 12; $i++) {
                $cubetas[$i] = ['etiqueta' => self::MESES[$i - 1], 'valor' => 0.0];
            }
            foreach ($pedidos as $p) {
                $m = (int) date('n', strtotime((string) $p['created_at']));
                $cubetas[$m]['valor'] += (float) $p['total'];
            }
        } else {
            $dias = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $mes)));
            for ($i = 1; $i <= $dias; $i++) {
                $cubetas[$i] = ['etiqueta' => (string) $i, 'valor' => 0.0];
            }
            foreach ($pedidos as $p) {
                $d = (int) date('j', strtotime((string) $p['created_at']));
                $cubetas[$d]['valor'] += (float) $p['total'];
            }
        }

        $maximo = 0.0;
        foreach ($cubetas as $c) {
            $maximo = max($maximo, $c['valor']);
        }

        $serie = [];
        foreach ($cubetas as $c) {
            $serie[] = [
                'etiqueta' => $c['etiqueta'],
                'valor'    => $c['valor'],
                'alto'     => $maximo > 0 ? max(2, (int) round($c['valor'] / $maximo * 100)) : 2,
            ];
        }

        return $serie;
    }

    private function facturas(int $comercioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.uuid, f.serie, f.folio, f.total, f.estado, f.fecha_timbrado, f.created_at,
                    f.razon_social_emisor, p.uuid AS pedido_uuid
               FROM facturas f
               JOIN pedidos p ON p.id = f.pedido_id
              WHERE f.receptor_usuario_id = ?
              ORDER BY COALESCE(f.fecha_timbrado, f.created_at) DESC'
        );
        $stmt->execute([$comercioId]);

        return $stmt->fetchAll();
    }

    private function lineas(int $pedidoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT nombre_producto, cantidad, precio_unitario, subtotal
               FROM detalle_pedido WHERE pedido_id = ? ORDER BY id'
        );
        $stmt->execute([$pedidoId]);

        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------------- apoyo

    private function usuarioId(): int
    {
        return (int) Session::obtener('user_id', 0);
    }

    private function vista(string $plantilla, array $datos, int $estado = 200): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $vista = new View($this->directorioVistas, $nonce);

        $datos['nonce'] ??= $nonce;
        $datos['csrf'] ??= Csrf::token();

        return Response::html($vista->render($plantilla, $datos), $estado)->withNonce($nonce);
    }
}
