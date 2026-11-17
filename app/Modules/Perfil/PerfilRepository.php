<?php

declare(strict_types=1);

namespace App\Modules\Perfil;

use PDO;

final class PerfilRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Crea el perfil que corresponde al rol, si no existe. */
    public function asegurarPerfil(int $usuarioId, string $rol, string $nombreNegocio): void
    {
        $tabla = match ($rol) {
            'comercio'   => 'comercios_perfil',
            'mayorista'  => 'mayoristas_perfil',
            'repartidor' => 'repartidores_perfil',
            default      => null,
        };

        if ($tabla === null) {
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM {$tabla} WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);

        if ($stmt->fetchColumn() !== false) {
            return;
        }

        if ($tabla === 'comercios_perfil') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO comercios_perfil (usuario_id, nombre_negocio) VALUES (?, ?)'
            );
            $stmt->execute([$usuarioId, $nombreNegocio]);

            return;
        }

        if ($tabla === 'mayoristas_perfil') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO mayoristas_perfil (uuid, usuario_id, nombre_comercial) VALUES (UUID(), ?, ?)'
            );
            $stmt->execute([$usuarioId, $nombreNegocio]);

            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO repartidores_perfil (usuario_id) VALUES (?)');
        $stmt->execute([$usuarioId]);
    }

    public function perfil(int $usuarioId, string $rol): ?array
    {
        $tabla = match ($rol) {
            'comercio'   => 'comercios_perfil',
            'mayorista'  => 'mayoristas_perfil',
            'repartidor' => 'repartidores_perfil',
            default      => null,
        };

        if ($tabla === null) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$tabla} WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);

        return $stmt->fetch() ?: null;
    }

    public function guardarDireccion(int $usuarioId, array $datos): int
    {
        $this->pdo->beginTransaction();

        try {
            // Solo una dirección principal por usuario.
            if (!empty($datos['es_principal'])) {
                $stmt = $this->pdo->prepare('UPDATE direcciones SET es_principal = 0 WHERE usuario_id = ?');
                $stmt->execute([$usuarioId]);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO direcciones
                 (usuario_id, alias, calle, colonia, ciudad, estado_mx, cp, latitud, longitud, referencias, es_principal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $usuarioId,
                $datos['alias'] ?? null,
                $datos['calle'],
                $datos['colonia'] ?? null,
                $datos['ciudad'],
                $datos['estado_mx'] ?? null,
                $datos['cp'] ?? null,
                $datos['latitud'],
                $datos['longitud'],
                $datos['referencias'] ?? null,
                !empty($datos['es_principal']) ? 1 : 0,
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    public function listarDirecciones(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM direcciones WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY es_principal DESC, id DESC'
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll() ?: [];
    }

    /** ¿La coordenada cae en alguna zona de cobertura activa? */
    public function dentroDeCobertura(float $lat, float $lng): bool
    {
        $stmt = $this->pdo->query('SELECT poligono FROM zonas_cobertura WHERE activa = 1');
        $zonas = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($zonas as $json) {
            $poligono = json_decode((string) $json, true);

            if (is_array($poligono) && \App\Core\Geo::puntoEnPoligono($lat, $lng, $poligono)) {
                return true;
            }
        }

        return false;
    }
}
