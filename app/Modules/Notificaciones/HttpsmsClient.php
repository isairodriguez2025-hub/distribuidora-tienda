<?php

declare(strict_types=1);

namespace App\Modules\Notificaciones;

/**
 * Cliente de HTTP SMS (httpsms.com).
 *
 * HTTPSMS manda el SMS desde un teléfono Android vinculado: la app del
 * teléfono consulta la cola del servicio y envía el mensaje. Por eso la API
 * solo encola y responde 202; la entrega real ocurre en el teléfono.
 *
 * Configuración por entorno:
 *   HTTPSMS_API_KEY   clave de la cuenta (x-api-key)
 *   HTTPSMS_FROM      número emisor, formato +52...
 *   HTTPSMS_BASE      opcional, para pruebas contra un mock local
 *
 * Sin HTTPSMS_API_KEY el cliente entra en modo simulado: registra el intento
 * y devuelve ok, para que el flujo de la app no se rompa en desarrollo.
 */
final class HttpsmsClient
{
    private const BASE_POR_OMISION = 'https://api.httpsms.com';

    public function __construct(
        private readonly string $clave,
        private readonly string $remitente,
        private readonly string $base = self::BASE_POR_OMISION,
    ) {
    }

    public static function desdeEntorno(): self
    {
        return new self(
            (string) ($_ENV['HTTPSMS_API_KEY'] ?? ''),
            (string) ($_ENV['HTTPSMS_FROM'] ?? ''),
            (string) ($_ENV['HTTPSMS_BASE'] ?? self::BASE_POR_OMISION),
        );
    }

    public function configurado(): bool
    {
        return $this->clave !== '' && $this->remitente !== '';
    }

    /**
     * @return array{ok:bool, simulado:bool, mensaje:string}
     */
    public function enviar(string $destino, string $texto): array
    {
        if (!$this->configurado()) {
            return [
                'ok'       => true,
                'simulado' => true,
                'mensaje'  => 'SMS simulado: falta HTTPSMS_API_KEY o HTTPSMS_FROM en .env',
            ];
        }

        $cuerpo = json_encode([
            'from'    => $this->remitente,
            'to'      => $destino,
            'content' => $texto,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(rtrim($this->base, '/') . '/v1/messages/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->clave,
            ],
        ]);

        $respuesta = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            return ['ok' => false, 'simulado' => false, 'mensaje' => 'Sin conexión con HTTPSMS: ' . $error];
        }

        if ($codigo < 200 || $codigo >= 300) {
            return [
                'ok'       => false,
                'simulado' => false,
                'mensaje'  => 'HTTPSMS respondió ' . $codigo . ': ' . mb_substr((string) $respuesta, 0, 200),
            ];
        }

        return ['ok' => true, 'simulado' => false, 'mensaje' => 'Encolado en HTTPSMS'];
    }
}
