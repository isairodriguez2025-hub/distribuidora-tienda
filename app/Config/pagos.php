<?php

declare(strict_types=1);

/**
 * Parámetros de comisiones y pasarela (RN-09).
 *
 * Los porcentajes se leen de .env para poder ajustarlos sin tocar código.
 */
return [
    // Comisión que se queda la plataforma sobre el subtotal de productos.
    'comision_mayorista_pct' => (float) ($_ENV['COMISION_MAYORISTA_PCT'] ?? 8),
    // Comisión sobre el envío.
    'comision_logistica_pct'  => (float) ($_ENV['COMISION_LOGISTICA_PCT'] ?? 12),
    // Cargo fijo de servicio por pedido (MXN).
    'cargo_servicio'          => (float) ($_ENV['CARGO_SERVICIO'] ?? 15),
    // Lo que cobra la pasarela por transacción.
    'comision_pasarela_pct'   => (float) ($_ENV['COMISION_PASARELA_PCT'] ?? 3.49),
    // Secreto para firmar y verificar webhooks. Sin valor por defecto: un
    // secreto conocido convierte /pagos/webhook en un bypass de pago.
    'webhook_secret'          => (static function (): string {
        $secreto = (string) ($_ENV['PAGOS_WEBHOOK_SECRET'] ?? '');

        if (strlen($secreto) < 32) {
            throw new RuntimeException('Falta PAGOS_WEBHOOK_SECRET (mínimo 32 caracteres) en el entorno.');
        }

        return $secreto;
    })(),
];
