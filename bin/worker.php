#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Worker de tareas periódicas.
 *
 * Uso:  php bin/worker.php
 * Cron: * * * * * cd /ruta && php bin/worker.php >> storage/logs/worker.log 2>&1
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Modules\Jobs\Worker;
use App\Modules\Notificaciones\NotificacionService;
use App\Modules\Pedidos\PedidoService;
use App\Modules\Programados\ProgramadoService;

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$pdo = Database::nuevaConexion();

$notificaciones = new NotificacionService($pdo, __DIR__ . '/../storage/correos');

$pedidoService = new PedidoService($pdo, $notificaciones);
$asignacion = new \App\Modules\Logistica\AsignacionService(
    $pdo,
    new \App\Modules\Logistica\TarifaService($pdo),
    $pedidoService,
);

$worker = new Worker(
    $pdo,
    $pedidoService,
    $notificaciones,
    new ProgramadoService($pdo, new \App\Modules\Catalogo\PrecioService(), $notificaciones),
    $asignacion,
);

$inicio = microtime(true);
$resultados = $worker->ejecutar();
$total = (int) round((microtime(true) - $inicio) * 1000);

foreach ($resultados as $r) {
    printf("[%s] %-22s %-45s %4d ms\n", date('H:i:s'), $r['tarea'], $r['resultado'], $r['ms']);
}

printf("Total: %d ms\n", $total);
