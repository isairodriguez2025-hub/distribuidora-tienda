<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Respuesta HTTP. Aplica las cabeceras de seguridad en un solo lugar para que
 * ninguna ruta pueda olvidarlas.
 */
final class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    /** Nonce para la política CSP: se genera por petición en el middleware. */
    private ?string $nonce = null;

    public function __construct(
        private string $body = '',
        private int $status = 200,
    ) {
    }

    public static function json(array $data, int $status = 200): self
    {
        $r = new self((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status);
        $r->headers['Content-Type'] = 'application/json; charset=utf-8';

        return $r;
    }

    public static function html(string $html, int $status = 200): self
    {
        $r = new self($html, $status);
        $r->headers['Content-Type'] = 'text/html; charset=utf-8';

        return $r;
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $r = new self('', $status);
        $r->headers['Location'] = $url;

        return $r;
    }

    /** Descarga con nombre de archivo (plantillas CSV, exportaciones). */
    public static function descarga(string $nombre, string $contenido, string $mime = 'text/csv; charset=UTF-8'): self
    {
        $respuesta = new self($contenido, 200);
        $respuesta->headers['Content-Type'] = $mime;
        $respuesta->headers['Content-Disposition'] = 'attachment; filename="' . $nombre . '"';
        $respuesta->headers['Cache-Control'] = 'no-store';

        return $respuesta;
    }

    /** Respuesta binaria desde un archivo del disco (assets estáticos). */
    public static function archivo(string $ruta, string $mime, int $status = 200): self
    {
        $contenido = @file_get_contents($ruta);

        if ($contenido === false) {
            return self::json(['ok' => false, 'error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Archivo no encontrado.']], 404);
        }

        $respuesta = new self($contenido, $status);
        $respuesta->headers['Content-Type'] = $mime;
        $respuesta->headers['Cache-Control'] = 'public, max-age=86400';

        return $respuesta;
    }

    public function withNonce(string $nonce): self
    {
        $this->nonce = $nonce;

        return $this;
    }

    public function nonce(): ?string
    {
        return $this->nonce;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    /** Envía la respuesta al navegador con las cabeceras de seguridad. */
    public function send(): void
    {
        if (headers_sent()) {
            echo $this->body;

            return;
        }

        http_response_code($this->status);

        $seguridad = [
            'X-Frame-Options'         => 'DENY',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'geolocation=(self)',
            'Cross-Origin-Opener-Policy'  => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'X-Download-Options'          => 'noopen',
            'X-Content-Type-Options'      => 'nosniff',
            // El nonce va también en style-src: sin él, el <style> de la plantilla
            // se bloquea y la página sale sin diseño. style-src-attr permite los
            // atributos style="…" de las vistas, que no pueden ejecutar código.
            'Content-Security-Policy' => $this->nonce === null
                ? "default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org; style-src 'self'; style-src-attr 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'"
                : "default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org; style-src 'self' 'nonce-{$this->nonce}'; style-src-attr 'unsafe-inline'; script-src 'self' 'nonce-{$this->nonce}'; frame-ancestors 'none'",
        ];

        // HSTS solo tiene sentido sobre HTTPS.
        if (($this->headers['X-Forwarded-Proto'] ?? '') === 'https'
            || (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')) {
            $seguridad['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($seguridad + $this->headers as $nombre => $valor) {
            header($nombre . ': ' . $valor);
        }

        echo $this->body;
    }
}
