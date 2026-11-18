<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\AuditLogger;
use PDO;

/**
 * Acceso a datos de usuarios. Todo con sentencias preparadas: aquí no se
 * concatena SQL con nada que venga del usuario.
 */
final class UsuarioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([mb_strtolower(trim($email))]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Busca por teléfono comparando solo los últimos 10 dígitos, para que
     * "+52 55 1234 5678", "5512345678" y "55-1234-5678" encuentren lo mismo.
     */
    public function buscarPorTelefono(string $telefono): ?array
    {
        $digitos = preg_replace('/\D+/', '', $telefono) ?? '';

        if (strlen($digitos) < 10) {
            return null;
        }

        $ultimos = substr($digitos, -10);

        $stmt = $this->pdo->prepare(
            "SELECT * FROM usuarios
              WHERE deleted_at IS NULL
                AND RIGHT(REGEXP_REPLACE(telefono, '[^0-9]', ''), 10) = ?
              LIMIT 1"
        );
        $stmt->execute([$ultimos]);

        return $stmt->fetch() ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @return string[] */
    public function roles(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.nombre FROM usuario_roles ur
             JOIN roles r ON r.id = ur.rol_id
             WHERE ur.usuario_id = ?'
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function crear(string $email, string $hash, string $telefono, string $rol): int
    {
        $uuid = self::uuid4();

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO usuarios (uuid, email, password_hash, telefono, estado)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$uuid, mb_strtolower(trim($email)), $hash, $telefono, 'pendiente']);
            $id = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('INSERT INTO usuario_roles (usuario_id, rol_id) SELECT ?, id FROM roles WHERE nombre = ?');
            $stmt->execute([$id, $rol]);

            $this->pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    public function actualizarUltimoLogin(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios SET ultimo_login_at = NOW(), estado = IF(estado = 'pendiente', 'activo', estado) WHERE id = ?"
        );
        $stmt->execute([$usuarioId]);
    }

    public function marcarEmailVerificado(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios SET email_verificado_at = NOW(), estado = 'activo' WHERE id = ?"
        );
        $stmt->execute([$usuarioId]);
    }

    public function actualizarPassword(int $usuarioId, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $usuarioId]);
    }

    // --- Intentos de login (rate limit por cuenta e IP) ---------------------

    public function registrarIntento(string $email, string $ip, bool $exitoso): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO intentos_login (email_hash, ip, exitoso) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            hash('sha256', mb_strtolower(trim($email))),
            AuditLogger::ipBinaria($ip) ?? inet_pton('0.0.0.0'),
            $exitoso ? 1 : 0,
        ]);
    }

    /** Cuenta fallos recientes por correo, por IP, o por ambos. */
    public function contarFallos(int $minutos, ?string $email = null, ?string $ip = null): int
    {
        $sql = 'SELECT COUNT(*) FROM intentos_login
                WHERE exitoso = 0 AND creado_en > (NOW() - INTERVAL ? MINUTE)';
        $params = [$minutos];

        if ($email !== null) {
            $sql .= ' AND email_hash = ?';
            $params[] = hash('sha256', mb_strtolower(trim($email)));
        }

        if ($ip !== null) {
            $sql .= ' AND ip = ?';
            $params[] = AuditLogger::ipBinaria($ip) ?? inet_pton('0.0.0.0');
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    // --- Tokens de un solo uso ---------------------------------------------

    public function crearToken(int $usuarioId, string $tipo, string $tokenHash, int $minutos): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tokens_verificacion (usuario_id, tipo, token_hash, expira_en)
             VALUES (?, ?, ?, NOW() + INTERVAL ? MINUTE)'
        );
        $stmt->execute([$usuarioId, $tipo, $tokenHash, $minutos]);
    }

    public function buscarToken(string $tokenHash, string $tipo): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM tokens_verificacion
             WHERE token_hash = ? AND tipo = ? AND usado_at IS NULL AND expira_en > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash, $tipo]);

        return $stmt->fetch() ?: null;
    }

    public function marcarTokenUsado(int $tokenId): void
    {
        $stmt = $this->pdo->prepare('UPDATE tokens_verificacion SET usado_at = NOW() WHERE id = ?');
        $stmt->execute([$tokenId]);
    }

    public function invalidarTokens(int $usuarioId, string $tipo): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tokens_verificacion SET usado_at = NOW() WHERE usuario_id = ? AND tipo = ? AND usado_at IS NULL'
        );
        $stmt->execute([$usuarioId, $tipo]);
    }

    public function registrarSesion(int $usuarioId, ?string $ip, ?string $userAgent): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sesiones_log (usuario_id, ip, user_agent) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $usuarioId,
            AuditLogger::ipBinaria($ip),
            mb_substr($userAgent ?? '', 0, 500),
        ]);
    }

    public static function uuid4(): string
    {
        $datos = random_bytes(16);
        $datos[6] = chr((ord($datos[6]) & 0x0f) | 0x40);
        $datos[8] = chr((ord($datos[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($datos), 4));
    }
}
