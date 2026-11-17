<?php

declare(strict_types=1);

namespace App\Modules\Perfil;

use PDO;
use RuntimeException;

/**
 * Foto de perfil.
 *
 * Se guarda recortada en cuadrado y reducida a 256 px para que el header no
 * cargue una foto de 4 MB en cada página. El archivo vive fuera de public/ y
 * se sirve por /avatar/{id}, de modo que no se puede listar el directorio ni
 * subir un .php que luego se ejecute.
 */
final class AvatarService
{
    private const LADO   = 256;
    private const MAXIMO = 2 * 1024 * 1024; // 2 MB
    private const TIPOS  = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $directorio,
    ) {
    }

    /**
     * Procesa la subida. Devuelve el mensaje para el usuario o lanza
     * RuntimeException con el motivo real.
     */
    public function guardar(int $usuarioId, array $archivo): void
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Selecciona una imagen.');
        }

        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la imagen (código ' . (int) $archivo['error'] . ').');
        }

        if (($archivo['size'] ?? 0) > self::MAXIMO) {
            throw new RuntimeException('La imagen no puede pesar más de 2 MB.');
        }

        if (!is_uploaded_file((string) ($archivo['tmp_name'] ?? ''))) {
            throw new RuntimeException('Archivo no válido.');
        }

        $info = @getimagesize((string) $archivo['tmp_name']);

        if ($info === false || !isset(self::TIPOS[$info['mime'] ?? ''])) {
            throw new RuntimeException('Formato no admitido. Usa JPG, PNG o WebP.');
        }

        $origen = $this->abrir((string) $archivo['tmp_name'], $info['mime']);

        if ($origen === null) {
            throw new RuntimeException('No se pudo leer la imagen.');
        }

        // En PHP 8+ imagedestroy() es innecesario y en 8.5 está deprecado
        // (el ErrorHandler lo convertiría en excepción).
        $destino = $this->recortar($origen, (int) $info[0], (int) $info[1]);

        if (!is_dir($this->directorio) && !mkdir($this->directorio, 0o750, true) && !is_dir($this->directorio)) {
            throw new RuntimeException('No se pudo preparar el almacén de imágenes.');
        }

        $ruta = $this->directorio . '/' . $usuarioId . '.webp';
        $ok = imagewebp($destino, $ruta, 88);

        if (!$ok) {
            $ruta = $this->directorio . '/' . $usuarioId . '.jpg';
            $ok = imagejpeg($destino, $ruta, 88);
        }

        if (!$ok) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        $this->borrarAnterior($usuarioId, $ruta);

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET avatar_ruta = ?, avatar_actualizado_at = NOW() WHERE id = ?'
        );
        $stmt->execute([basename($ruta), $usuarioId]);
    }

    public function quitar(int $usuarioId): void
    {
        $this->borrarAnterior($usuarioId, '');

        $this->pdo->prepare('UPDATE usuarios SET avatar_ruta = NULL, avatar_actualizado_at = NULL WHERE id = ?')
            ->execute([$usuarioId]);
    }

    /** Ruta absoluta del avatar, o null si no tiene. */
    public function ruta(int $usuarioId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT avatar_ruta FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $nombre = $stmt->fetchColumn();

        if (!is_string($nombre) || $nombre === '') {
            return null;
        }

        $ruta = $this->directorio . '/' . basename($nombre);

        return is_file($ruta) ? $ruta : null;
    }

    /**
     * URL pública del avatar, o null si el usuario no tiene foto.
     * La versión en la query invalida la caché al cambiar la imagen.
     */
    public static function url(int $usuarioId): ?string
    {
        try {
            $stmt = \App\Config\Database::conexion()->prepare(
                'SELECT avatar_ruta, avatar_actualizado_at FROM usuarios WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$usuarioId]);
            $fila = $stmt->fetch();
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($fila) || empty($fila['avatar_ruta'])) {
            return null;
        }

        $version = empty($fila['avatar_actualizado_at']) ? 0 : (int) strtotime((string) $fila['avatar_actualizado_at']);

        return '/avatar/' . $usuarioId . '?v=' . $version;
    }

    public static function iniciales(string $nombre): string
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            return '?';
        }

        $partes = preg_split('/\s+/u', $nombre) ?: [];
        $primera = mb_substr($partes[0] ?? '', 0, 1);

        if (count($partes) > 1) {
            return mb_strtoupper($primera . mb_substr($partes[1], 0, 1));
        }

        return mb_strtoupper($primera);
    }

    private function abrir(string $ruta, string $mime): \GdImage|null
    {
        $imagen = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($ruta),
            'image/png'  => @imagecreatefrompng($ruta),
            'image/webp' => @imagecreatefromwebp($ruta),
            default      => false,
        };

        return $imagen instanceof \GdImage ? $imagen : null;
    }

    private function recortar(\GdImage $origen, int $ancho, int $alto): \GdImage
    {
        $lado = min($ancho, $alto);
        $x = (int) (($ancho - $lado) / 2);
        $y = (int) (($alto - $lado) / 2);

        $destino = imagecreatetruecolor(self::LADO, self::LADO);

        // Fondo blanco: los PNG con transparencia quedan legibles.
        $blanco = imagecolorallocate($destino, 255, 255, 255);
        imagefilledrectangle($destino, 0, 0, self::LADO, self::LADO, $blanco);

        imagecopyresampled($destino, $origen, 0, 0, $x, $y, self::LADO, self::LADO, $lado, $lado);

        return $destino;
    }

    private function borrarAnterior(int $usuarioId, string $excepto): void
    {
        foreach (glob($this->directorio . '/' . $usuarioId . '.*') ?: [] as $viejo) {
            if ($viejo !== $excepto && is_file($viejo)) {
                @unlink($viejo);
            }
        }
    }
}
