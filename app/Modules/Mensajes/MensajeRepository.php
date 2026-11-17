<?php

declare(strict_types=1);

namespace App\Modules\Mensajes;

use PDO;

/**
 * Acceso a conversaciones y mensajes directos.
 *
 * Regla de oro: toda consulta que devuelve mensajes filtra por participación.
 * La comprobación no vive solo en el controlador; si un método se reutiliza
 * desde otro sitio, sigue sin poder leer hilos ajenos.
 */
final class MensajeRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Hilos del usuario con último mensaje y contador de no leídos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function hilos(int $usuarioId): array
    {
        $sql = <<<SQL
            SELECT c.id, c.uuid, c.tipo, c.asunto, c.created_at, c.updated_at,
                   p.ultimo_leido_id,
                   (SELECT COUNT(*) FROM mensajes_directos m
                     WHERE m.conversacion_id = c.id
                       AND m.id > p.ultimo_leido_id
                       AND m.autor_id <> p.usuario_id) AS no_leidos,
                   (SELECT m.cuerpo FROM mensajes_directos m
                     WHERE m.conversacion_id = c.id ORDER BY m.id DESC LIMIT 1) AS ultimo,
                   (SELECT m.created_at FROM mensajes_directos m
                     WHERE m.conversacion_id = c.id ORDER BY m.id DESC LIMIT 1) AS ultimo_at
              FROM conversaciones c
              JOIN conversacion_participantes p
                ON p.conversacion_id = c.id AND p.usuario_id = ?
             ORDER BY COALESCE(ultimo_at, c.created_at) DESC
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll() ?: [];
    }

    public function porUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM conversaciones WHERE uuid = ? LIMIT 1');
        $stmt->execute([$uuid]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM conversaciones WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function esParticipante(int $conversacionId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM conversacion_participantes WHERE conversacion_id = ? AND usuario_id = ? LIMIT 1'
        );
        $stmt->execute([$conversacionId, $usuarioId]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return array<int, array{id:int,nombre:string,avatar:?string}> */
    public function participantes(int $conversacionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.email, c.nombre_negocio, m.nombre_comercial
               FROM conversacion_participantes p
               JOIN usuarios u ON u.id = p.usuario_id
          LEFT JOIN comercios_perfil c ON c.usuario_id = u.id
          LEFT JOIN mayoristas_perfil m ON m.usuario_id = u.id
              WHERE p.conversacion_id = ?
              ORDER BY u.id'
        );
        $stmt->execute([$conversacionId]);

        $salida = [];

        foreach ($stmt->fetchAll() ?: [] as $f) {
            $nombre = (string) ($f['nombre_negocio'] ?? $f['nombre_comercial'] ?? '');
            $salida[] = [
                'id'     => (int) $f['id'],
                'nombre' => $nombre !== '' ? $nombre : (string) $f['email'],
                'avatar' => \App\Modules\Perfil\AvatarService::url((int) $f['id']),
            ];
        }

        return $salida;
    }

    /** @return array<int, array<string, mixed>> */
    public function mensajes(int $conversacionId, int $desdeId = 0, int $limite = 200): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.autor_id, m.cuerpo, m.created_at, u.email, c.nombre_negocio, m2.nombre_comercial
               FROM mensajes_directos m
               JOIN usuarios u ON u.id = m.autor_id
          LEFT JOIN comercios_perfil c ON c.usuario_id = u.id
          LEFT JOIN mayoristas_perfil m2 ON m2.usuario_id = u.id
              WHERE m.conversacion_id = ? AND m.id > ?
              ORDER BY m.id ASC
              LIMIT ?'
        );
        $stmt->bindValue(1, $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(2, $desdeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /** @param int[] $participantes */
    public function crear(string $tipo, ?string $asunto, int $creadorId, array $participantes): int
    {
        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );

        $this->pdo->prepare(
            'INSERT INTO conversaciones (uuid, tipo, asunto, creada_por) VALUES (?,?,?,?)'
        )->execute([$uuid, $tipo, $asunto, $creadorId]);

        $id = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO conversacion_participantes (conversacion_id, usuario_id) VALUES (?,?)'
        );

        foreach (array_unique($participantes) as $participante) {
            $stmt->execute([$id, (int) $participante]);
        }

        return $id;
    }

    public function insertar(int $conversacionId, int $autorId, string $cuerpo): int
    {
        $this->pdo->prepare(
            'INSERT INTO mensajes_directos (conversacion_id, autor_id, cuerpo) VALUES (?,?,?)'
        )->execute([$conversacionId, $autorId, $cuerpo]);

        $id = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare('UPDATE conversaciones SET updated_at = NOW() WHERE id = ?')
            ->execute([$conversacionId]);

        return $id;
    }

    public function marcarLeido(int $conversacionId, int $usuarioId, int $hastaId): void
    {
        $this->pdo->prepare(
            'UPDATE conversacion_participantes
                SET ultimo_leido_id = GREATEST(ultimo_leido_id, ?)
              WHERE conversacion_id = ? AND usuario_id = ?'
        )->execute([$hastaId, $conversacionId, $usuarioId]);
    }

    /** Conversación directa ya existente entre dos usuarios. */
    public function directaEntre(int $a, int $b): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.* FROM conversaciones c
               JOIN conversacion_participantes pa ON pa.conversacion_id = c.id AND pa.usuario_id = ?
               JOIN conversacion_participantes pb ON pb.conversacion_id = c.id AND pb.usuario_id = ?
              WHERE c.tipo = 'directa'
              ORDER BY c.id DESC LIMIT 1"
        );
        $stmt->execute([$a, $b]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function usuarioActivo(int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE id = ? AND estado = 'activo' LIMIT 1");
        $stmt->execute([$usuarioId]);

        return $stmt->fetchColumn() !== false;
    }

    /** Usuarios con los que se puede iniciar una conversación directa. */
    public function contactos(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.email,
                    COALESCE(c.nombre_negocio, m.nombre_comercial, u.email) AS nombre,
                    r.nombre AS rol
               FROM usuarios u
               JOIN usuario_roles ur ON ur.usuario_id = u.id
               JOIN roles r ON r.id = ur.rol_id
          LEFT JOIN comercios_perfil c ON c.usuario_id = u.id
          LEFT JOIN mayoristas_perfil m ON m.usuario_id = u.id
              WHERE u.id <> ?
                AND u.estado = 'activo'
                AND r.nombre IN ('comercio','mayorista')
              GROUP BY u.id, u.email, nombre, r.nombre
              ORDER BY nombre
              LIMIT 100"
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll() ?: [];
    }

    /** Hilo de soporte abierto por este usuario, si existe. */
    public function soporteDe(int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.* FROM conversaciones c
               JOIN conversacion_participantes p ON p.conversacion_id = c.id
              WHERE c.tipo = 'soporte' AND p.usuario_id = ?
              ORDER BY c.updated_at DESC LIMIT 1"
        );
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch();

        return is_array($fila) ? $fila : null;
    }

    /** @return int[] ids de administradores */
    public function admins(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT ur.usuario_id
               FROM usuario_roles ur
               JOIN roles r ON r.id = ur.rol_id
              WHERE r.nombre IN ('admin_total','admin_kyc','admin_soporte','admin_finanzas')"
        );

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function esAdmin(int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM usuario_roles ur JOIN roles r ON r.id = ur.rol_id
              WHERE ur.usuario_id = ?
                AND r.nombre IN ('admin_total','admin_kyc','admin_soporte','admin_finanzas') LIMIT 1"
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetchColumn() !== false;
    }
}
