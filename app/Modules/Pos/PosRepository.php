<?php

declare(strict_types=1);

namespace App\Modules\Pos;

use PDO;

/**
 * Persistencia del punto de venta del comercio.
 *
 * Todo está filtrado por usuario_id: el inventario, las ventas y los turnos de
 * un comercio no son visibles para otro ni para un mayorista.
 */
final class PosRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Ajustes del comercio; se crean con valores por defecto la primera vez. */
    public function ajustes(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_ajustes WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch();

        if (is_array($fila)) {
            return $fila;
        }

        $this->pdo->prepare('INSERT IGNORE INTO pos_ajustes (usuario_id) VALUES (?)')->execute([$usuarioId]);

        return ['usuario_id' => $usuarioId, 'margen_venta' => 30.00, 'reorden_auto' => 1, 'iva_incluido' => 1];
    }

    public function guardarAjustes(int $usuarioId, float $margen, bool $reorden, bool $ivaIncluido): void
    {
        $this->pdo->prepare(
            'INSERT INTO pos_ajustes (usuario_id, margen_venta, reorden_auto, iva_incluido)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE margen_venta = VALUES(margen_venta),
                                     reorden_auto = VALUES(reorden_auto),
                                     iva_incluido = VALUES(iva_incluido)'
        )->execute([$usuarioId, $margen, $reorden ? 1 : 0, $ivaIncluido ? 1 : 0]);
    }

    /** @return array<int, array<string, mixed>> */
    public function productos(int $usuarioId, string $busqueda = '', bool $soloBajo = false): array
    {
        $sql = 'SELECT * FROM pos_productos WHERE usuario_id = ? AND activo = 1';
        $params = [$usuarioId];

        if ($busqueda !== '') {
            $sql .= ' AND (nombre LIKE ? OR sku LIKE ? OR codigo_barras LIKE ?)';
            $like = '%' . $busqueda . '%';
            array_push($params, $like, $like, $like);
        }

        if ($soloBajo) {
            $sql .= ' AND stock <= stock_minimo';
        }

        $sql .= ' ORDER BY nombre LIMIT 300';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function producto(int $usuarioId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_productos WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$id, $usuarioId]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function guardarProducto(int $usuarioId, array $d, ?int $id = null): int
    {
        if ($id !== null) {
            $this->pdo->prepare(
                'UPDATE pos_productos
                    SET nombre = ?, sku = ?, codigo_barras = ?, precio_compra = ?, precio_venta = ?,
                        iva_tasa = ?, stock = ?, stock_minimo = ?, unidad = ?, activo = ?
                  WHERE id = ? AND usuario_id = ?'
            )->execute([
                $d['nombre'], $d['sku'], $d['codigo_barras'], $d['precio_compra'], $d['precio_venta'],
                $d['iva_tasa'], $d['stock'], $d['stock_minimo'], $d['unidad'], $d['activo'] ? 1 : 0,
                $id, $usuarioId,
            ]);

            return $id;
        }

        $this->pdo->prepare(
            'INSERT INTO pos_productos (usuario_id, producto_id, nombre, sku, codigo_barras, precio_compra,
                                        precio_venta, iva_tasa, stock, stock_minimo, unidad, activo)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $usuarioId, $d['producto_id'] ?? null, $d['nombre'], $d['sku'], $d['codigo_barras'],
            $d['precio_compra'], $d['precio_venta'], $d['iva_tasa'], $d['stock'], $d['stock_minimo'],
            $d['unidad'], $d['activo'] ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function turnoAbierto(int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pos_turnos WHERE usuario_id = ? AND estado = 'abierto' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function abrirTurno(int $usuarioId, float $fondo): int
    {
        $this->pdo->prepare(
            "INSERT INTO pos_turnos (usuario_id, fondo_inicial, estado) VALUES (?,?, 'abierto')"
        )->execute([$usuarioId, $fondo]);

        return (int) $this->pdo->lastInsertId();
    }

    public function turno(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_turnos WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function ultimoTurno(int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_turnos WHERE usuario_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function cerrarTurno(int $id, float $esperado, float $contado, string $notas): void
    {
        $this->pdo->prepare(
            "UPDATE pos_turnos
                SET estado = 'cerrado', cerrado_at = NOW(), efectivo_esperado = ?, efectivo_contado = ?,
                    diferencia = ?, notas = ?
              WHERE id = ?"
        )->execute([$esperado, $contado, round($contado - $esperado, 2), $notas, $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function ventas(int $usuarioId, int $limite = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*, t.fondo_inicial, c.nombre AS cliente
               FROM pos_ventas v
          LEFT JOIN pos_turnos t ON t.id = v.turno_id
          LEFT JOIN pos_clientes c ON c.id = v.cliente_id
              WHERE v.usuario_id = ?
              ORDER BY v.id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function ventaPorUuid(int $usuarioId, string $uuid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_ventas WHERE uuid = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$uuid, $usuarioId]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function lineas(int $ventaId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_venta_lineas WHERE venta_id = ? ORDER BY id');
        $stmt->execute([$ventaId]);

        return $stmt->fetchAll() ?: [];
    }

    public function pagos(int $ventaId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_pagos WHERE venta_id = ? ORDER BY id');
        $stmt->execute([$ventaId]);

        return $stmt->fetchAll() ?: [];
    }

    public function clientes(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_clientes WHERE usuario_id = ? ORDER BY nombre LIMIT 200');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll() ?: [];
    }

    public function crearCliente(int $usuarioId, string $nombre, string $telefono, string $email, string $rfc): int
    {
        $this->pdo->prepare(
            'INSERT INTO pos_clientes (usuario_id, nombre, telefono, email, rfc) VALUES (?,?,?,?,?)'
        )->execute([$usuarioId, $nombre, $telefono, $email, $rfc]);

        return (int) $this->pdo->lastInsertId();
    }

    public function movimientos(int $turnoId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pos_movimientos WHERE turno_id = ? ORDER BY id');
        $stmt->execute([$turnoId]);

        return $stmt->fetchAll() ?: [];
    }

    public function registrarMovimiento(int $turnoId, string $tipo, float $monto, string $concepto, ?string $referencia = null): void
    {
        $this->pdo->prepare(
            'INSERT INTO pos_movimientos (turno_id, tipo, monto, concepto, referencia) VALUES (?,?,?,?,?)'
        )->execute([$turnoId, $tipo, $monto, $concepto, $referencia]);
    }

    /** Totales del turno por método de pago. */
    public function resumenTurno(int $turnoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.metodo, COUNT(DISTINCT v.id) AS ventas, SUM(p.monto) AS total
               FROM pos_pagos p
               JOIN pos_ventas v ON v.id = p.venta_id
              WHERE v.turno_id = ? AND v.estado = 'pagada'
              GROUP BY p.metodo"
        );
        $stmt->execute([$turnoId]);

        return $stmt->fetchAll() ?: [];
    }

    public function totalVendido(int $turnoId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(total),0) FROM pos_ventas WHERE turno_id = ? AND estado = 'pagada'"
        );
        $stmt->execute([$turnoId]);

        return (float) $stmt->fetchColumn();
    }

    /** Último pedido entregado del comercio, para saber a qué mayorista reponer. */
    public function mayoristaHabitual(int $usuarioId): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.mayorista_id
               FROM pedidos p
               JOIN comercios_perfil c ON c.id = p.comercio_id
              WHERE c.usuario_id = ?
                AND p.estado IN ('entregado','completado')
              ORDER BY p.id DESC LIMIT 1"
        );
        $stmt->execute([$usuarioId]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function direccionHabitual(int $usuarioId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.id FROM direcciones d
               JOIN comercios_perfil c ON c.id = d.comercio_id
              WHERE c.usuario_id = ? AND d.activa = 1
              ORDER BY d.es_principal DESC, d.id ASC LIMIT 1'
        );
        $stmt->execute([$usuarioId]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }
}
