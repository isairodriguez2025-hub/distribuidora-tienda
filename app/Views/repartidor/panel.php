<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $repartidor */
/** @var array $ofertas */
/** @var array $ruta */
/** @var string|null $mensaje */
/** @var string|null $error */
$enLinea = (int) $repartidor['disponible'] === 1;

$ofertasHtml = '';
foreach ($ofertas as $o) {
    $ofertasHtml .= '<article style="background:#fff;border:1px solid #cfdcd3;border-radius:10px;padding:14px 16px;margin-bottom:10px">'
        . '<div style="display:flex;justify-content:space-between;align-items:baseline">'
        . '<strong style="font-size:15px">' . $e($o['mayorista']) . ' → ' . $e($o['comercio']) . '</strong>'
        . '<span style="font-size:18px;font-weight:700">$' . number_format((float) $o['tarifa'], 2) . '</span></div>'
        . '<p style="font-size:13px;color:#6b7280;margin:4px 0 10px">'
        . $e($o['distancia_km']) . ' km de recorrido · ' . $e($o['calle']) . ', ' . $e($o['ciudad']) . '</p>'
        . '<form method="post" action="/repartidor/ofertas/' . (int) $o['id'] . '/aceptar" style="margin:0">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<button type="submit" style="padding:8px 16px;font-size:14px">Aceptar envío</button>'
        . '<span class="cuenta" data-limite="' . $e((string) $o['expira_en']) . '" style="font-size:12px;color:#a56a12;margin-left:12px"></span>'
        . '</form></article>';
}
if ($ofertasHtml === '') {
    $ofertasHtml = '<p class="vacio">No tienes ofertas pendientes.</p>';
}

$rutaHtml = '';
foreach ($ruta as $p) {
    $rutaHtml .= '<article style="background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:14px 16px;margin-bottom:10px">'
        . '<div style="display:flex;justify-content:space-between;align-items:baseline">'
        . '<strong style="font-size:15px">' . $e($p['mayorista']) . ' → ' . $e($p['comercio']) . '</strong>'
        . '<span style="font-size:13px;color:#6b7280">' . $e($p['estado']) . '</span></div>'
        . '<p style="font-size:13px;color:#6b7280;margin:4px 0 10px">' . $e($p['calle']) . ', ' . $e($p['colonia']) . ', ' . $e($p['ciudad']) . '</p>'
        . '<a href="/pedidos/' . $e($p['uuid']) . '" style="font-size:13px">Abrir pedido</a>'
        . '</article>';
}
if ($rutaHtml === '') {
    $rutaHtml = '<p class="vacio">Sin entregas asignadas.</p>';
}

$contenido = '<h1>Mi ruta</h1>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . '<div style="display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:16px 18px;margin:18px 0">'
    . '<div><div style="font-weight:600">' . ($enLinea ? 'En línea' : 'Fuera de línea') . '</div>'
    . '<div style="font-size:13px;color:#6b7280">' . ($enLinea ? 'Recibes ofertas de envío.' : 'No recibes ofertas.') . '</div></div>'
    . '<form method="post" action="/repartidor/disponibilidad" style="margin:0">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<input type="hidden" name="disponible" value="' . ($enLinea ? '0' : '1') . '">'
    . '<button type="submit" class="' . ($enLinea ? 'boton--claro' : '') . '" style="padding:9px 16px;font-size:14px">'
    . ($enLinea ? 'Salir de línea' : 'Ponerme en línea') . '</button></form></div>'
    . '<h2>Ofertas</h2>' . $ofertasHtml
    . '<h2>Entregas de hoy</h2>' . $rutaHtml;

$enViaje = $ruta !== [];
$tokenCsrf = $csrf;
$snippet = '';

$scripts = <<<'JS'
document.querySelectorAll('.cuenta[data-limite]').forEach(el => {
    const limite = new Date(el.dataset.limite.replace(' ', 'T')).getTime();
    const pintar = () => {
        const resta = limite - Date.now();
        if (resta <= 0) { el.textContent = 'Oferta vencida'; return; }
        el.textContent = 'Quedan ' + Math.floor(resta / 1000) + ' s';
    };
    pintar();
    setInterval(pintar, 1000);
});

// Durante el viaje se envían posiciones del GPS cada ~12 segundos.
if (__ENVIAJE__ && navigator.geolocation) {
    let cola = [];

    navigator.geolocation.watchPosition(
        pos => {
            cola.push({
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
                precision_m: Math.round(pos.coords.accuracy || 0),
                capturado_en: new Date(pos.timestamp).toISOString()
            });
        },
        err => console.warn('GPS:', err.message),
        { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 }
    );

    setInterval(async () => {
        if (cola.length === 0) return;
        const lote = cola.splice(0, 20);

        try {
            await fetch('/tracking/posicion', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '__CSRF__' },
                body: JSON.stringify(lote)
            });
        } catch (e) {
            console.warn('No se pudo enviar la posición');
        }
    }, 12000);
}

JS;

$scripts = str_replace(
    ['__ENVIAJE__', '__CSRF__'],
    [$enViaje ? 'true' : 'false', $tokenCsrf],
    $scripts,
);

require __DIR__ . '/../layout.php';
