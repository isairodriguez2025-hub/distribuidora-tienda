<?php
/**
 * Página de inicio: saludo y tarjeta de accesos según el tipo de cuenta.
 *
 * @var callable $e
 * @var string   $nombre
 * @var string   $rol
 * @var array    $principal
 * @var array    $extra
 * @var array    $resumen
 * @var array    $metricas
 */

use App\Core\Iconos;

$globo = static function (int $n): string {
    return $n > 0 ? '<span class="explorar-item__globo">' . ($n > 99 ? '99+' : $n) . '</span>' : '';
};

$botones = '';
foreach ($principal as [$href, $titulo, $descripcion]) {
    $insignia = '';

    if ($href === '/finanzas') {
        $insignia = $globo(0);
    } elseif ($href === '/portafolio') {
        $insignia = $globo((int) ($resumen['portafolio'] ?? 0));
    }

    $botones .= '<a class="explorar-item" href="' . $e($href) . '">'
        . '<span class="explorar-item__icono">' . Iconos::svg($href, 24) . $insignia . '</span>'
        . '<span class="explorar-item__texto"><strong>' . $e($titulo) . '</strong>'
        . '<span>' . $e($descripcion) . '</span></span>'
        . '<span class="explorar-item__flecha" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"></path></svg>'
        . '</span></a>';
}

$atajos = '';
foreach ($extra as [$href, $titulo, $descripcion, $clave]) {
    $icono = match ($clave) {
        'pos'        => Iconos::svg('/pos', 19),
        'inventario' => Iconos::svg('/pos/inventario', 19),
        'ventas'     => Iconos::svg('/pos/ventas', 19),
        'pedidos'    => Iconos::svg('/pedidos', 19),
        'mensajes'   => Iconos::svg('/mensajes', 19),
        default      => Iconos::svg('/buscar', 19),
    };

    $atajos .= '<a class="explorar-atajo" href="' . $e($href) . '" title="' . $e($descripcion) . '">'
        . $icono . '<span>' . $e($titulo) . '</span>'
        . ($clave === 'mensajes' ? $globo((int) ($resumen['mensajes'] ?? 0)) : '')
        . '</a>';
}

$bloqueAtajos = $atajos !== ''
    ? '<section class="explorar-atajos"><h2>Más herramientas</h2><div class="explorar-atajos__fila">' . $atajos . '</div></section>'
    : '';

$fichasMetricas = '';
foreach (($metricas ?? []) as $m) {
    $fichasMetricas .= '<a class="metrica" href="' . $e((string) $m['href']) . '">'
        . '<span class="metrica__valor">' . $e((string) $m['valor']) . '</span>'
        . '<span class="metrica__etiqueta">' . $e((string) $m['etiqueta']) . '</span>'
        . '<span class="metrica__nota">' . $e((string) ($m['nota'] ?? '')) . '</span>'
        . '</a>';
}

$bloqueMetricas = $fichasMetricas !== ''
    ? '<section class="explorar-metricas">' . $fichasMetricas . '</section>'
    : '';

$contenido = '<header class="explorar-saludo">'
    . '<h1>Hola, ' . $e($nombre) . '!</h1>'
    . '<p>¿Con qué empezamos hoy?</p>'
    . '</header>'

    . $bloqueMetricas

    . '<section class="explorar-tarjeta">'
    . '<div class="explorar-grid">' . $botones . '</div>'
    . '</section>'

    . $bloqueAtajos;

require __DIR__ . '/../layout.php';
