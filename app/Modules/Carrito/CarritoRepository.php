<?php

declare(strict_types=1);

namespace App\Modules\Carrito;

use App\Modules\Auth\UsuarioRepository;
use PDO;

final class CarritoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Devuelve el carrito del comercio, creándolo si es la primera vez. */
    public function carritoDe(int $comercioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM carritos WHERE comercio_id = ? LIMIT 1');
        $stmt->execute([$comercioId]);
        $carrito = $stmt->fetch();

        if ($carrito !== false) {
            return $carrito;
        }

        $this->pdo->prepare('INSERT INTO carritos (uuid, comercio_id) VALUES (?, ?)')
            ->execute([UsuarioRepository::uuid4(), $comercioId]);

        $stmt = $this->pdo->prepare('SELECT * FROM carritos WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $this->pdo->lastInsertId()]);

        return $stmt->fetch() ?: [];
    }

    /** Items con los datos del producto y su mayorista. */
    public function items(int $carritoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ci.id, ci.cantidad, ci.producto_id,
                    p.uuid, p.nombre, p.precio_base, p.iva_tasa, p.unidad_venta, p.contenido,
                    p.cantidad_minima, p.multiplo_venta, p.stock_disponible, p.activo, p.deleted_at,
                    m.id AS mayorista_id, m.uuid AS mayorista_uuid, m.nombre_comercial,
                    m.monto_minimo, m.estado_kyc, m.activo AS mayorista_activo
             FROM carrito_items ci
             JOIN productos p ON p.id = ci.producto_id
             JOIN mayoristas_perfil m ON m.id = p.mayorista_id
             WHERE ci.carrito_id = ?
             ORDER BY m.nombre_comercial, p.nombre'
        );
        $stmt->execute([$carritoId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Suma a la cantidad existente: volver a agregar el mismo producto no debe
     * borrar lo que ya había en el carrito.
     */
    public function agregar(int $carritoId, int $productoId, int $cantidad): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)'
        );
        $stmt->execute([$carritoId, $productoId, $cantidad]);
    }

    /** Unidades del producto que ya están en el carrito (0 si no está). */
    public function cantidadDe(int $carritoId, int $productoId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(cantidad, 0) FROM carrito_items WHERE carrito_id = ? AND producto_id = ? LIMIT 1'
        );
        $stmt->execute([$carritoId, $productoId]);

        return (int) $stmt->fetchColumn();
    }

    /** Quita del carrito los productos indicados (los que ya se convirtieron en pedido). */
    public function quitarProductos(int $carritoId, array $productoIds): void
    {
        if ($productoIds === []) {
            return;
        }

        $marcas = implode(',', array_fill(0, count($productoIds), '?'));
        $this->pdo->prepare("DELETE FROM carrito_items WHERE carrito_id = ? AND producto_id IN ($marcas)")
            ->execute([$carritoId, ...array_map('intval', $productoIds)]);
    }

    public function quitar(int $carritoId, int $productoId): void
    {
        $this->pdo->prepare('DELETE FROM carrito_items WHERE carrito_id = ? AND producto_id = ?')
            ->execute([$carritoId, $productoId]);
    }

    public function vaciar(int $carritoId): void
    {
        $this->pdo->prepare('DELETE FROM carrito_items WHERE carrito_id = ?')->execute([$carritoId]);
    }

    public function actualizar(int $carritoId, int $productoId, int $cantidad): void
    {
        if ($cantidad < 1) {
            $this->quitar($carritoId, $productoId);
            return;
        }
        $this->pdo->prepare('UPDATE carrito_items SET cantidad = ? WHERE carrito_id = ? AND producto_id = ?')
            ->execute([$cantidad, $carritoId, $productoId]);
    }

    public function productoPorUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, m.id AS mayorista_id, m.nombre_comercial, m.monto_minimo,
                    m.estado_kyc, m.activo AS mayorista_activo
             FROM productos p
             JOIN mayoristas_perfil m ON m.id = p.mayorista_id
             WHERE p.uuid = ? AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$uuid]);

        return $stmt->fetch() ?: null;
    }

    public function escalasDe(int $productoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM escala_descuentos WHERE producto_id = ? ORDER BY cantidad_minima DESC'
        );
        $stmt->execute([$productoId]);

        return $stmt->fetchAll() ?: [];
    }
}
