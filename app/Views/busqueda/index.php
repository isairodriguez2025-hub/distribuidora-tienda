<?php
/** @var callable $e */
/** @var string $nonce */
/** @var string $csrf */
/** @var array|null $data */
/** @var string|null $error */
/** @var array $filtros */
/** @var float $lat */
/** @var float $lng */
/** @var array $categorias */

$resultados = $data['resultados'] ?? [];
$total = $data['total'] ?? 0;
$paginas = $data['paginas'] ?? 0;
$page = $data['page'] ?? 1;

$opcionesCategoria = '<option value="">Todas las categorías</option>';
foreach ($categorias as $c) {
    $sel = ((int) ($_GET['categoria_id'] ?? 0) === (int) $c['id']) ? ' selected' : '';
    $opcionesCategoria .= '<option value="' . (int) $c['id'] . '"' . $sel . '>' . $e($c['nombre']) . '</option>';
}

$opcionesRadio = '';
foreach ([2, 5, 10, 20, 30] as $km) {
    $sel = (float) $filtros['radio'] === (float) $km ? ' selected' : '';
    $opcionesRadio .= '<option value="' . $km . '"' . $sel . '>' . $km . ' km</option>';
}

$opcionesOrden = '';
foreach (['distancia' => 'Más cercano', 'precio_asc' => 'Menor precio', 'precio_desc' => 'Mayor precio', 'reputacion' => 'Mejor calificado'] as $valor => $texto) {
    $sel = $filtros['orden'] === $valor ? ' selected' : '';
    $opcionesOrden .= '<option value="' . $valor . '"' . $sel . '>' . $e($texto) . '</option>';
}

// Desplegable propio: el <select> nativo se conserva oculto para que el
// formulario siga funcionando sin JavaScript, y encima se dibuja el menú.
$combo = static function (string $nombre, string $opciones, string $estilo): string {
    return '<div class="combo" style="' . $estilo . '">'
        . '<select name="' . $nombre . '" class="combo__nativo" tabindex="-1" aria-hidden="true">' . $opciones . '</select>'
        . '<button type="button" class="combo__boton" aria-haspopup="listbox" aria-expanded="false">'
        . '<span class="combo__texto"></span>'
        . '<svg class="combo__flecha" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>'
        . '</button>'
        . '<ul class="combo__lista" role="listbox" hidden></ul>'
        . '</div>';
};

$puedeComprar = \App\Core\Session::obtener('user_id', null) !== null
    && in_array('comercio', (array) \App\Core\Session::obtener('roles', []), true);

$fichas = '';
foreach ($resultados as $i => $r) {
    $dist = number_format((float) $r['distancia_km'], 1);
    $stock = (int) $r['stock_disponible'];
    $minimo = (int) $r['cantidad_minima'];
    $paso = max(1, (int) $r['multiplo_venta']);

    $fichas .= '<article class="ficha" data-i="' . $i . '" data-lat="' . $e((string) $r['mayorista']['latitud']) . '" data-lng="' . $e((string) $r['mayorista']['longitud']) . '">'
        . '<div class="ficha__izq">'
        . '<h3>' . $e($r['nombre']) . '</h3>'
        . '<p class="ficha__prov">' . $e($r['mayorista']['nombre'])
        . ' <span class="badge">' . $e($r['categoria']) . '</span>'
        . '<a class="ficha__ver" href="/mayoristas/' . $e($r['mayorista']['uuid']) . '">Ver mayorista</a></p>'
        . '<div class="ficha__precio">$' . number_format((float) $r['precio_base'], 2)
        . '<span>+ IVA · ' . $e($r['unidad_venta']) . ' de ' . $e($r['contenido']) . '</span></div>'
        . '</div>'
        . '<div class="ficha__der">'
        . '<span class="ficha__dist">' . $dist . ' km</span>'
        . '<span class="ficha__stock">' . $stock . ' disponibles</span>';

    if ($puedeComprar) {
        $fichas .= '<form method="post" action="/carrito/agregar" class="ficha__compra">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<input type="hidden" name="producto" value="' . $e($r['uuid']) . '">'
            . '<input type="hidden" name="volver" value="/buscar">'
            . '<div class="stepper">'
            . '<button type="button" class="stepper__menos" aria-label="Quitar uno">&minus;</button>'
            . '<input type="number" name="cantidad" value="' . $minimo . '" min="' . $minimo . '" step="' . $paso . '" max="' . $stock . '" aria-label="Cantidad">'
            . '<button type="button" class="stepper__mas" aria-label="Agregar uno">+</button>'
            . '</div>'
            . '<button type="submit" class="btn-agregar">Agregar</button>'
            . '</form>';
    }

    $fichas .= '</div></article>';
}

if ($fichas === '') {
    $fichas = '<p class="vacio">No hay productos en este radio. Prueba ampliando la distancia o cambiando el texto.</p>';
}

$paginacion = '';
if ($paginas > 1) {
    $base = '/buscar?' . http_build_query([
        'q' => $filtros['q'], 'radio' => $filtros['radio'], 'orden' => $filtros['orden'],
        'lat' => $lat, 'lng' => $lng,
    ]);
    if ($page > 1) {
        $paginacion .= '<a class="boton boton--claro" href="' . $e($base . '&page=' . ($page - 1)) . '">Anterior</a>';
    }
    $paginacion .= '<span style="font-size:14px;color:#6b7280">Página ' . (int) $page . ' de ' . (int) $paginas . '</span>';
    if ($page < $paginas) {
        $paginacion .= '<a class="boton boton--claro" href="' . $e($base . '&page=' . ($page + 1)) . '">Siguiente</a>';
    }
}

$filtrosJson = json_encode([
    'q' => $filtros['q'] ?? '',
    'categoria_id' => (int) ($filtros['categoria_id'] ?? 0),
    'radio' => (float) ($filtros['radio'] ?? 10),
    'orden' => (string) ($filtros['orden'] ?? 'distancia'),
], JSON_UNESCAPED_UNICODE) ?: '{}';

// Las fichas que se pintan por AJAX necesitan el token y el permiso de compra
// en el ámbito de JavaScript; antes se usaban como variables globales
// inexistentes y el botón "Agregar" fallaba tras la primera búsqueda.
$csrfJson = json_encode((string) $csrf);
$puedeComprarJson = $puedeComprar ? 'true' : 'false';

$json = json_encode(array_map(static fn (array $r): array => [
    'nombre'   => $r['nombre'],
    'lat'      => $r['mayorista']['latitud'],
    'lng'      => $r['mayorista']['longitud'],
    'precio'   => $r['precio_base'],
    'dist'     => $r['distancia_km'],
    'mayo'     => $r['mayorista']['nombre'],
    'categoria'=> $r['categoria'],
], $resultados), JSON_UNESCAPED_UNICODE) ?: '[]';

// Categorías para los marcadores generales del mapa (sin búsqueda activa).
$jsonCategorias = json_encode(array_map(static fn (array $c): array => [
    'id'   => (int) $c['id'],
    'nombre' => $c['nombre'],
], $categorias), JSON_UNESCAPED_UNICODE) ?: '[]';

$mapa = true;
$anchoCompleto = true;

$contenido = '<h1>Buscar productos</h1>'
    . '<p>' . (int) $total . ' producto' . ($total === 1 ? '' : 's') . ' cerca de ti.</p>'
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . '<form method="get" action="/buscar" class="filtros">'
    . '<input type="hidden" name="lat" id="f-lat" value="' . $e((string) $lat) . '">'
    . '<input type="hidden" name="lng" id="f-lng" value="' . $e((string) $lng) . '">'
    . '<input name="q" value="' . $e($filtros['q']) . '" placeholder="¿Qué buscas? café, cloro, tornillo…" style="flex:2;min-width:200px">'
    . $combo('categoria_id', $opcionesCategoria, 'flex:1;min-width:170px')
    . $combo('radio', $opcionesRadio, 'flex:0 0 120px')
    . $combo('orden', $opcionesOrden, 'flex:0 0 170px')
    . '<button type="submit">Buscar</button>'
    . '</form>'
    . '<div class="buscador">'
    . '<div class="buscador__mapa"><div id="mapa" class="mapa"></div></div>'
    . '<div class="buscador__resultados">' . $fichas
    . ($paginacion !== '' ? '<div class="paginacion">' . $paginacion . '</div>' : '')
    . '</div></div>';

$estilos = <<<'CSS'
.filtros {
    display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:20px 0; width:100%;
    background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:12px;
}
.filtros input,
.filtros select {
    height:44px; padding:0 14px; font-size:14px; color:#111827; background:#fff;
    border:1px solid #E5E7EB; border-radius:10px;
    transition:border-color .18s ease, box-shadow .18s ease;
}
.filtros input::placeholder { color:#9ca3af; }
.filtros input:focus,
.filtros select:focus {
    outline:none; border-color:#1f4d3a; box-shadow:0 0 0 3px rgba(31,77,58,.10);
}
.filtros select {
    appearance:none; -webkit-appearance:none; -moz-appearance:none; cursor:pointer;
    padding-right:38px; background-repeat:no-repeat; background-position:right 14px center;
    background-image:url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
}
.filtros > button {
    height:44px; padding:0 22px; font-size:14px; font-weight:500; color:#fff;
    background:#1f4d3a; border:0; border-radius:10px; cursor:pointer; white-space:nowrap;
    transition:background .18s ease, transform .1s ease;
}
.filtros > button:hover { background:#17392b; }
.filtros > button:active { transform:translateY(1px); }


/* --- Desplegable propio (reemplaza el <select> nativo) ------------------- */
.combo { position: relative; }
.combo__nativo {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0; pointer-events: none;
}
.combo__boton {
    width: 100%; height: 44px; padding: 0 14px;
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    background: #fff; border: 1px solid #E5E7EB; border-radius: 10px;
    font: inherit; font-size: 14px; color: #111827; text-align: left; cursor: pointer;
    transition: border-color .18s ease, box-shadow .18s ease;
}
.combo__boton:hover { border-color: #d1d5db; }
.combo__boton:focus-visible,
.combo--abierto .combo__boton {
    outline: none; border-color: #1f4d3a; box-shadow: 0 0 0 3px rgba(31,77,58,.10);
}
.combo__texto { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.combo__flecha { flex: 0 0 auto; color: #6b7280; transition: transform .18s ease; }
.combo--abierto .combo__flecha { transform: rotate(180deg); }

.combo__lista {
    position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 60;
    margin: 0; padding: 6px; list-style: none;
    background: #fff; border: 1px solid #E5E7EB; border-radius: 12px;
    box-shadow: 0 12px 32px rgba(17,24,39,.14), 0 2px 8px rgba(17,24,39,.06);
    max-height: 280px; overflow-y: auto;
}
.combo__opcion {
    padding: 8px 16px; border-radius: 8px;
    font-size: 14px; color: #374151; cursor: pointer;
    transition: background .12s ease, color .12s ease;
}
.combo__opcion:hover,
.combo__opcion.activa { background: #f3f4f6; color: #111827; }
.combo__opcion[aria-selected="true"] { background: #eaf1ec; color: #1f4d3a; font-weight: 500; }

/* Dos columnas solo en computadora */
.buscador { display:grid; grid-template-columns:1fr; gap:18px; align-items:start; width:100%; }
.buscador__mapa { position:relative; }
.mapa {
    height:340px; width:100%; border-radius:14px; overflow:hidden;
    box-shadow:0 4px 18px rgba(17,24,39,.07); border:1px solid #E5E7EB; z-index:1;
}
@media (min-width:992px) {
    /* 2/3 y 1/3, pero la columna de fichas nunca baja de 360 px: en monitores
       muy anchos el mapa se queda con el espacio sobrante. */
    .buscador { grid-template-columns: minmax(0, 2fr) minmax(360px, 1fr); gap:22px; }
    .buscador__mapa { position:sticky; top:76px; }
    .mapa { height:calc(100vh - 150px); min-height:520px; }
    .buscador__resultados {
        max-height:calc(100vh - 150px); overflow-y:auto; padding-right:6px;
        container-type:inline-size;
    }
    .buscador__resultados::-webkit-scrollbar { width:8px; }
    .buscador__resultados::-webkit-scrollbar-thumb { background:#d9d7d0; border-radius:8px; }
    .buscador__resultados::-webkit-scrollbar-track { background:transparent; }
}
.fichas { display:grid; grid-template-columns:1fr; gap:12px; width:100%; }
.ficha {
    display:flex; justify-content:space-between; align-items:flex-start; gap:24px;
    background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px 20px;
    cursor:pointer; transition:border-color .15s, box-shadow .15s, transform .15s;
}
.ficha:hover { border-color:#c9d6cd; box-shadow:0 2px 10px rgba(17,24,39,.05); }
.ficha.activa { border-color:#1f4d3a; box-shadow:0 0 0 3px rgba(31,77,58,.09); }
.ficha__izq { flex:1; min-width:0; }
.ficha h3 { font-size:18px; font-weight:600; letter-spacing:-.01em; margin:0 0 4px; }
.ficha__prov { font-size:13px; color:#6b7280; margin:0 0 10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.badge {
    font-size:11px; font-weight:500; color:#6b7280; background:#f3f4f6;
    padding:2px 9px; border-radius:999px; letter-spacing:0; white-space:nowrap;
}
.ficha__precio { font-size:22px; font-weight:700; letter-spacing:-.02em; color:#111827; }
.ficha__precio span { font-size:12px; font-weight:400; color:#9ca3af; letter-spacing:0; margin-left:6px; }
.ficha__der { display:flex; flex-direction:column; align-items:flex-end; gap:8px; flex-shrink:0; }
.ficha__dist { font-size:12px; color:#9ca3af; }
.ficha__stock {
    font-size:12px; font-weight:500; color:#1f4d3a; background:#eaf1ec;
    padding:3px 10px; border-radius:999px; white-space:nowrap;
}
.ficha__compra { display:flex; align-items:center; gap:8px; margin:2px 0 0; }
.stepper { display:flex; align-items:center; border:1px solid #E5E7EB; border-radius:8px; overflow:hidden; }
.stepper button {
    width:30px; height:34px; padding:0; background:#fff; border:0; color:#4b5563;
    font-size:16px; line-height:1; cursor:pointer;
}
.stepper button:hover { background:#f9fafb; }
.stepper input {
    width:46px; height:34px; padding:0; border:0; border-left:1px solid #E5E7EB; border-right:1px solid #E5E7EB;
    text-align:center; font-size:14px; border-radius:0;
}
.stepper input:focus { outline:none; box-shadow:none; }
.stepper input::-webkit-outer-spin-button,
.stepper input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
.btn-agregar { height:34px; padding:0 16px; font-size:13px; font-weight:500; background:#1f4d3a; color:#fff;
               border:0; border-radius:8px; cursor:pointer; white-space:nowrap; }
.btn-agregar:hover { background:#17392b; }
.ficha__ver { font-size:12px; color:#6b7280; text-decoration:none; border-bottom:1px solid #e5e7eb; margin-left:2px; }
.ficha__ver:hover { color:#1f4d3a; border-bottom-color:#1f4d3a; }
/* Cuando la columna de resultados es angosta, la ficha se acomoda sola:
   se consulta el ancho del contenedor, no el de la ventana. */
@container (max-width: 520px) {
    .ficha { flex-direction:column; gap:12px; }
    .ficha__der {
        flex-direction:row; align-items:center; justify-content:flex-start;
        flex-wrap:wrap; width:100%; gap:10px;
    }
    .ficha__compra { margin-left:auto; }
}
@media (max-width:560px) {
    .ficha { flex-direction:column; gap:14px; padding:14px 16px; }
    .ficha__der { align-items:flex-start; width:100%; }
    .ficha__compra { width:100%; margin-left:0; }
}
.paginacion { display:flex; gap:12px; align-items:center; justify-content:center; margin-top:28px; }

/* Marcadores personalizados del mapa */
.marker-cat {
    background: none !important;
    border: none !important;
    font-size: 28px;
    text-align: center;
    line-height: 1;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,.3));
}
.marker-producto {
    background: none !important;
    border: none !important;
    font-size: 24px;
    text-align: center;
    line-height: 1;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,.3));
}
.marker-default {
    background: none !important;
    border: none !important;
    font-size: 24px;
    text-align: center;
    line-height: 1;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,.3));
}

/* Leyenda de categorías en el mapa */
.leaflet-control.legend {
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 13px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    line-height: 1.6;
}
.leaflet-control.legend strong {
    display: block;
    margin-bottom: 4px;
    font-size: 13px;
    color: #1f4d3a;
}
.leaflet-control.legend span {
    display: inline-block;
    width: 18px;
    text-align: center;
    margin-right: 4px;
}
CSS;

$scripts = <<<JS
const datos = {$json};
const categorias = {$jsonCategorias};
const filtros = {$filtrosJson};
const csrf = {$csrfJson};
const puedeComprar = {$puedeComprarJson};
const mapa = L.map('mapa', { scrollWheelZoom: false }).setView([{$lat}, {$lng}], 12);
window.mapa = mapa;
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
}).addTo(mapa);

// --- Marcadores: capa única, agrupación por cuadrícula y repintado diferencial.
// Antes se destruían y recreaban todos los marcadores en cada búsqueda, lo que
// producía parpadeo y trabajo inútil. Ahora se guarda la lista de puntos y solo
// se redibuja cuando cambia el zoom o el conjunto visible.
const capa = L.layerGroup().addTo(mapa);
let puntos = [];
let firmaActual = '';
const mapaDeMarcadores = new Map();
let peticionActual = null;

function iconoCluster(n) {
    return L.divIcon({ className: 'marker-cluster', html: '<span>' + n + '</span>', iconSize: [42, 42], iconAnchor: [21, 21] });
}

function pintarPuntos() {
    if (!puntos.length) { capa.clearLayers(); firmaActual = 'vacio'; return; }

    const z = mapa.getZoom();
    const centro = mapa.project(mapa.getCenter(), z);
    const celda = 64;
    const grupos = new Map();

    puntos.forEach((p, i) => {
        const pt = mapa.project([p.lat, p.lng], z);
        const k = Math.floor((pt.x - centro.x) / celda) + ':' + Math.floor((pt.y - centro.y) / celda);
        if (!grupos.has(k)) grupos.set(k, []);
        grupos.get(k).push(i);
    });

    const firma = z + '|' + [...grupos.entries()].map(([k, v]) => k + '=' + v.join('.')).sort().join(';');
    if (firma === firmaActual) return;
    firmaActual = firma;

    capa.clearLayers();
    mapaDeMarcadores.clear();

    grupos.forEach((idxs) => {
        if (idxs.length === 1) {
            const p = puntos[idxs[0]];
            const m = L.marker([p.lat, p.lng], { icon: p.icono, keyboard: false, riseOnHover: true });
            m.bindPopup(p.popup);
            if (p.idx >= 0) { mapaDeMarcadores.set(p.idx, m); m.on('click', () => resaltar(p.idx, false)); }
            capa.addLayer(m);
            return;
        }
        const lat = idxs.reduce((t, i) => t + puntos[i].lat, 0) / idxs.length;
        const lng = idxs.reduce((t, i) => t + puntos[i].lng, 0) / idxs.length;
        const m = L.marker([lat, lng], { icon: iconoCluster(idxs.length), keyboard: false });
        const nombres = idxs.slice(0, 4).map(i => puntos[i].titulo).join(' · ');
        m.bindTooltip(nombres + (idxs.length > 4 ? ' y ' + (idxs.length - 4) + ' más' : ''), { direction: 'top', offset: [0, -14] });
        m.on('click', () => mapa.setView([lat, lng], Math.min(18, mapa.getZoom() + 2), { animate: true }));
        capa.addLayer(m);
    });
}

function fijarPuntos(nuevos) { puntos = nuevos; firmaActual = ''; pintarPuntos(); }

function normalizarProducto(d) {
    // El JSON inicial y la respuesta AJAX no tienen la misma forma: el primero
    // trae lat/lng planos y el segundo los anida en d.mayorista. Se aceptan
    // ambas para que el marcador no caiga en (NaN, NaN).
    const m = d.mayorista || {};
    const lat = d.lat !== undefined ? d.lat : (m.latitud !== undefined ? m.latitud : d.latitud);
    const lng = d.lng !== undefined ? d.lng : (m.longitud !== undefined ? m.longitud : d.longitud);

    return {
        lat: Number(lat),
        lng: Number(lng),
        nombre: d.nombre || '',
        mayo: d.mayo || m.nombre || '',
        precio: d.precio !== undefined ? d.precio : (d.precio_base !== undefined ? Number(d.precio_base).toFixed(2) : ''),
        dist: d.dist !== undefined ? d.dist : (d.distancia_km !== undefined ? Number(d.distancia_km).toFixed(1) : ''),
        categoria: d.categoria || ''
    };
}

function puntosProductos(lista) {
    return lista.map((d, i) => {
        const n = normalizarProducto(d);
        return {
            lat: n.lat, lng: n.lng, idx: i, titulo: String(n.nombre), icono: iconoProducto,
            popup: '<strong>' + esc(n.nombre) + '</strong><br>' + esc(n.mayo) + '<br>$' + n.precio + ' · ' + n.dist + ' km<br><small>' + esc(n.categoria) + '</small>'
        };
    }).filter(p => Number.isFinite(p.lat) && Number.isFinite(p.lng));
}

function puntosCategorias() {
    return categorias.map(cat => ({
        lat: Number($lat), lng: Number($lng), idx: -1, titulo: String(cat.nombre),
        icono: iconosCategoria[cat.nombre] || iconoDefault,
        popup: '<strong>' + esc(cat.nombre) + '</strong><br>Productos disponibles en esta categoría'
    }));
}

// Iconos por categoría para los marcadores generales del mapa.
const iconosCategoria = {
    'Abarrotes': L.divIcon({ className: 'marker-cat', html: '🛒', iconSize: [32, 32], iconAnchor: [16, 32] }),
    'Limpieza': L.divIcon({ className: 'marker-cat', html: '🧹', iconSize: [32, 32], iconAnchor: [16, 32] }),
    'Ferretería': L.divIcon({ className: 'marker-cat', html: '🔧', iconSize: [32, 32], iconAnchor: [16, 32] }),
    'Perecederos': L.divIcon({ className: 'marker-cat', html: '🥬', iconSize: [32, 32], iconAnchor: [16, 32] }),
    'Inflamables': L.divIcon({ className: 'marker-cat', html: '⚠️', iconSize: [32, 32], iconAnchor: [16, 32] }),
};

// Icono por defecto para productos cuando hay búsqueda activa.
const iconoProducto = L.divIcon({ className: 'marker-producto', html: '📦', iconSize: [28, 28], iconAnchor: [14, 28] });

// Icono para mayoristas sin productos visibles.
const iconoDefault = L.divIcon({ className: 'marker-default', html: '📍', iconSize: [28, 28], iconAnchor: [14, 28] });

// Leyenda de categorías (solo visible cuando no hay búsqueda activa).
const leyenda = L.control({ position: 'bottomleft' });
leyenda.onAdd = function () {
    const div = L.DomUtil.create('div', 'leaflet-control legend');
    div.innerHTML = '<strong>Categorías</strong>' +
        categorias.map(c => {
            const icono = iconosCategoria[c.nombre] || '';
            return '<span>' + icono + '</span> ' + c.nombre;
        }).join('<br>');
    return div;
};

if (datos.length === 0 && categorias.length > 0) {
    leyenda.addTo(mapa);
    fijarPuntos(puntosCategorias());
} else {
    fijarPuntos(puntosProductos(datos));
}

function resaltar(i, centrar) {
    document.querySelectorAll('.ficha').forEach(f => f.classList.remove('activa'));
    const ficha = document.querySelector('.ficha[data-i="' + i + '"]');
    if (ficha) {
        ficha.classList.add('activa');
        if (centrar) {
            ficha.scrollIntoView({ behavior: 'smooth', block: 'center' });
            mapa.setView([parseFloat(ficha.dataset.lat), parseFloat(ficha.dataset.lng)], 14);
        }
    }
    const marcador = mapaDeMarcadores.get(i);
    if (marcador) marcador.openPopup();
}

document.querySelectorAll('.ficha').forEach(f => {
    f.addEventListener('click', () => resaltar(parseInt(f.dataset.i, 10), true));
});

// Botones − y + del selector de cantidad.
document.querySelectorAll('.stepper').forEach(st => {
    const inp = st.querySelector('input');
    const paso = parseInt(inp.step, 10) || 1;
    const min = parseInt(inp.min, 10) || 1;
    const max = parseInt(inp.max, 10) || 9999;

    st.querySelector('.stepper__menos').addEventListener('click', () => {
        const v = (parseInt(inp.value, 10) || min) - paso;
        inp.value = Math.max(min, v);
    });

    st.querySelector('.stepper__mas').addEventListener('click', () => {
        const v = (parseInt(inp.value, 10) || min) + paso;
        inp.value = Math.min(max, v);
    });
});

// El formulario de compra no debe centrar el mapa al hacer clic.
document.querySelectorAll('.ficha__compra, .ficha__ver').forEach(el => {
    el.addEventListener('click', e => e.stopPropagation());
});


// --- Desplegable propio ---------------------------------------------------
// El <select> sigue en el formulario (oculto) para que funcione sin JS; el
// menú visible es solo una capa encima que sincroniza su valor.
document.querySelectorAll('.combo').forEach(combo => {
    const select = combo.querySelector('.combo__nativo');
    const boton = combo.querySelector('.combo__boton');
    const texto = combo.querySelector('.combo__texto');
    const lista = combo.querySelector('.combo__lista');

    const opciones = Array.from(select.options);
    let indice = Math.max(0, select.selectedIndex);

    texto.textContent = opciones[indice] ? opciones[indice].text : '';

    opciones.forEach((op, i) => {
        const li = document.createElement('li');
        li.className = 'combo__opcion';
        li.setAttribute('role', 'option');
        li.dataset.valor = op.value;
        li.textContent = op.text;
        li.setAttribute('aria-selected', i === indice ? 'true' : 'false');

        li.addEventListener('click', () => elegir(i));
        lista.appendChild(li);
    });

    function pintarActiva() {
        Array.from(lista.children).forEach((li, i) => {
            li.classList.toggle('activa', i === indice);
            li.setAttribute('aria-selected', i === indice ? 'true' : 'false');
        });
    }

    function abrir() {
        document.querySelectorAll('.combo--abierto').forEach(c => {
            if (c !== combo) {
                c.classList.remove('combo--abierto');
                c.querySelector('.combo__lista').hidden = true;
                c.querySelector('.combo__boton').setAttribute('aria-expanded', 'false');
            }
        });

        lista.hidden = false;
        combo.classList.add('combo--abierto');
        boton.setAttribute('aria-expanded', 'true');
        pintarActiva();

        const activa = lista.children[indice];
        if (activa) activa.scrollIntoView({ block: 'nearest' });
    }

    function cerrar() {
        lista.hidden = true;
        combo.classList.remove('combo--abierto');
        boton.setAttribute('aria-expanded', 'false');
    }

    function elegir(i) {
        indice = i;
        select.selectedIndex = i;
        texto.textContent = opciones[i].text;
        cerrar();
        // Dispara el listener que envía el formulario.
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    boton.addEventListener('click', () => {
        lista.hidden ? abrir() : cerrar();
    });

    boton.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (lista.hidden) { abrir(); return; }
            indice = e.key === 'ArrowDown'
                ? Math.min(opciones.length - 1, indice + 1)
                : Math.max(0, indice - 1);
            pintarActiva();
            lista.children[indice].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            lista.hidden ? abrir() : elegir(indice);
        } else if (e.key === 'Escape') {
            cerrar();
        } else if (e.key === 'Home') {
            e.preventDefault(); indice = 0; pintarActiva();
        } else if (e.key === 'End') {
            e.preventDefault(); indice = opciones.length - 1; pintarActiva();
        }
    });

    document.addEventListener('click', e => {
        if (!combo.contains(e.target)) cerrar();
    });
});

// Los desplegables aplican el filtro al cambiar. Se hace con listeners y no
// con onchange en el HTML porque el CSP no permite manejadores en línea.
document.querySelectorAll('.filtros select').forEach(sel => {
    sel.addEventListener('change', () => document.querySelector('.filtros').submit());
});

// --- Búsqueda AJAX sin recargar la página -------------------------------
function buscarAJAX(filtros) {
    const params = filtros instanceof URLSearchParams ? filtros.toString() : new URLSearchParams(filtros).toString();

    // Cancelar la petición anterior: si el usuario mueve el mapa rápido, la
    // respuesta vieja ya no puede pisar a la nueva.
    if (peticionActual) peticionActual.abort();
    const control = new AbortController();
    peticionActual = control;

    marcarCargando(true);

    return fetch('/busqueda/productos?' + params, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: control.signal
    }).then(r => r.json()).then(d => {
        if (control.signal.aborted) return null;
        // Fuera de cobertura no se muestra nada flotante: se conservan los
        // resultados anteriores y el usuario sigue viendo su búsqueda. El aviso
        // emergente tapaba el mapa cada vez que se movía.
        if (!d.ok) {
            return null;
        }
        return d.data;
    }).catch(() => null).finally(() => {
        if (peticionActual === control) { peticionActual = null; marcarCargando(false); }
    });
}

function marcarCargando(activo) {
    const cont = document.querySelector('.buscador__resultados');
    if (cont) cont.classList.toggle('cargando', activo);
    const mapaEl = document.getElementById('mapa');
    if (mapaEl) mapaEl.classList.toggle('cargando', activo);
}

function paramsFiltros(lat, lng) {
    const p = new URLSearchParams();
    p.set('q', filtros.q);
    p.set('categoria_id', filtros.categoria_id);
    p.set('radio', filtros.radio);
    p.set('orden', filtros.orden);
    p.set('lat', lat);
    p.set('lng', lng);
    return p;
}

function actualizarMapaYResultados(data) {
    if (!data) return;


    // Actualizar contador.
    const contador = document.querySelector('.buscador__contador');
    if (contador) {
        contador.textContent = data.resultados.length + ' producto' + (data.resultados.length === 1 ? '' : 's');
    }

    // Actualizar fichas.
    const contenedor = document.querySelector('.buscador__resultados');
    if (contenedor) {
        const fichas = data.resultados.map((r, i) => {
            const dist = Number(r.distancia_km).toFixed(1);
            const stock = parseInt(r.stock_disponible, 10) || 0;
            const minimo = parseInt(r.cantidad_minima, 10) || 1;
            const paso = Math.max(1, parseInt(r.multiplo_venta, 10) || 1);
            let html = '<article class="ficha" data-i="' + i + '" data-lat="' + esc(r.mayorista.latitud) + '" data-lng="' + esc(r.mayorista.longitud) + '">'
                + '<div class="ficha__izq">'
                + '<h3>' + esc(r.nombre) + '</h3>'
                + '<p class="ficha__prov">' + esc(r.mayorista.nombre)
                + ' <span class="badge">' + esc(r.categoria) + '</span>'
                + '<a class="ficha__ver" href="/mayoristas/' + esc(r.mayorista.uuid) + '">Ver mayorista</a></p>'
                + '<div class="ficha__precio">$' + Number(r.precio_base).toFixed(2)
                + '<span>+ IVA · ' + esc(r.unidad_venta) + ' de ' + esc(r.contenido) + '</span></div>'
                + '</div>'
                + '<div class="ficha__der">'
                + '<span class="ficha__dist">' + dist + ' km</span>'
                + '<span class="ficha__stock">' + stock + ' disponibles</span>';
            if (puedeComprar) {
                html += '<form method="post" action="/carrito/agregar" class="ficha__compra">'
                    + '<input type="hidden" name="_csrf" value="' + esc(csrf) + '">'
                    + '<input type="hidden" name="producto" value="' + esc(r.uuid) + '">'
                    + '<input type="hidden" name="volver" value="/buscar">'
                    + '<div class="stepper">'
                    + '<button type="button" class="stepper__menos" aria-label="Quitar uno">&minus;</button>'
                    + '<input type="number" name="cantidad" value="' + minimo + '" min="' + minimo + '" step="' + paso + '" max="' + stock + '" aria-label="Cantidad">'
                    + '<button type="button" class="stepper__mas" aria-label="Agregar uno">+</button>'
                    + '</div>'
                    + '<button type="submit" class="btn-agregar">Agregar</button>'
                    + '</form>';
            }
            html += '</div></article>';
            return html;
        }).join('');

        if (fichas === '') {
            contenedor.innerHTML = '<p class="vacio">No hay productos en este radio. Prueba ampliando la distancia o cambiando el texto.</p>';
        } else {
            contenedor.innerHTML = fichas;
        }

        // Re-enlazar eventos de las fichas.
        contenedor.querySelectorAll('.ficha').forEach(f => {
            f.addEventListener('click', () => resaltar(parseInt(f.dataset.i, 10), true));
        });
        contenedor.querySelectorAll('.ficha__compra, .ficha__ver').forEach(el => {
            el.addEventListener('click', e => e.stopPropagation());
        });
        // Re-enlazar botones de stepper.
        contenedor.querySelectorAll('.stepper').forEach(st => {
            const inp = st.querySelector('input');
            const paso = parseInt(inp.step, 10) || 1;
            const min = parseInt(inp.min, 10) || 1;
            const max = parseInt(inp.max, 10) || 9999;
            st.querySelector('.stepper__menos').addEventListener('click', () => {
                inp.value = Math.max(min, (parseInt(inp.value, 10) || min) - paso);
            });
            st.querySelector('.stepper__mas').addEventListener('click', () => {
                inp.value = Math.min(max, (parseInt(inp.value, 10) || min) + paso);
            });
        });
    }

    // Actualizar marcadores del mapa (reutiliza la capa existente).
    if (data.resultados.length > 0) {
        leyenda.remove();
        fijarPuntos(puntosProductos(data.resultados));
    } else if (categorias.length > 0) {
        if (!mapa.hasLayer(leyenda)) leyenda.addTo(mapa);
        fijarPuntos(puntosCategorias());
    } else {
        fijarPuntos([]);
    }
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function number_format(num, decimals) {
    return Number(num).toFixed(decimals);
}

// Interceptar submit del formulario de filtros (sin recargar la página).
document.querySelector('.filtros').addEventListener('submit', function (e) {
    e.preventDefault();
    const form = this;
    const params = new URLSearchParams(new FormData(form));
    params.set('lat', document.getElementById('f-lat').value);
    params.set('lng', document.getElementById('f-lng').value);
    buscarAJAX(params).then(data => {
        if (data) actualizarMapaYResultados(data);
    });
});

// Interceptar cambio de desplegable (sin recargar la página).
document.querySelectorAll('.filtros select').forEach(sel => {
    sel.addEventListener('change', () => {
        const form = document.querySelector('.filtros');
        const params = new URLSearchParams(new FormData(form));
        params.set('lat', document.getElementById('f-lat').value);
        params.set('lng', document.getElementById('f-lng').value);
        buscarAJAX(params).then(data => {
            if (data) actualizarMapaYResultados(data);
        });
    });
});

// Al mover el mapa se actualizan las coordenadas y se busca con AJAX,
// sin recargar la página.
let temporizador = null;
let centroPrevio = mapa.getCenter();

function distanciaKm(a, b) {
    const R = 6371, rad = Math.PI / 180;
    const dLat = (b.lat - a.lat) * rad;
    const dLng = (b.lng - a.lng) * rad;
    const h = Math.sin(dLat / 2) ** 2 +
              Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
}

mapa.on('moveend', () => {
    const c = mapa.getCenter();
    const movido = distanciaKm(centroPrevio, c);

    document.getElementById('f-lat').value = c.lat.toFixed(6);
    document.getElementById('f-lng').value = c.lng.toFixed(6);

    if (movido < 0.3) {
        return;
    }

    centroPrevio = c;
    clearTimeout(temporizador);
    temporizador = setTimeout(() => {
        buscarAJAX(paramsFiltros(c.lat.toFixed(6), c.lng.toFixed(6))).then(data => {
            if (data) actualizarMapaYResultados(data);
        });
    }, 420);
});

// Al cambiar el zoom se reagrupan los marcadores sin pedir datos de nuevo.
mapa.on('zoomend', () => { firmaActual = ''; pintarPuntos(); });
JS;

require __DIR__ . '/../layout.php';
