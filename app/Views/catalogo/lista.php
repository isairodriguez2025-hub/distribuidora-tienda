<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $productos */
/** @var string $busqueda */
/** @var bool $puede */
/** @var string|null $mensaje */
$filas = '';
foreach ($productos as $p) {
    $activo = (int) $p['activo'] === 1;
    $insignia = $activo
        ? '<span class="chip chip--activo">activo</span>'
        : '<span class="chip chip--inactivo">inactivo</span>';
    $stock = (int) $p['stock_disponible'];
    $stockColor = $stock === 0 ? '#b91c1c' : ($stock < 10 ? '#a56a12' : 'inherit');
    $unidad = trim((string) ($p['unidad_venta'] ?? ''));
    $contenido = trim((string) ($p['contenido'] ?? ''));
    $presentacion = $unidad !== ''
        ? $unidad . ($contenido !== '' ? ' de ' . $contenido : '')
        : '—';

    $filas .= '<tr>'
        . '<td><div class="prod-nombre">' . $e($p['nombre']) . '</div>'
        . '<div class="prod-cat">' . $e($p['categoria']) . '</div></td>'
        . '<td class="num">$' . number_format((float) $p['precio_base'], 2) . '</td>'
        . '<td class="prod-unidad">' . $e($presentacion) . '</td>'
        . '<td class="num" style="color:' . $stockColor . '">' . $stock . '</td>'
        . '<td>' . $insignia . '</td>'
        . '<td class="prod-acciones"><a class="boton boton--mini boton--claro" href="/mayorista/productos/' . $e($p['uuid']) . '/editar">Editar</a></td>'
        . '</tr>';
}
if ($filas === '') {
    $filas = '<tr><td colspan="6"><div class="vacio">Todavía no tienes productos. Empieza con <strong>Nuevo producto</strong> o importa tu inventario desde un archivo.</div></td></tr>';
}

$aviso = $puede
    ? ''
    : '<div class="aviso aviso--alerta">Tu cuenta aún no está verificada. Puedes capturar productos, pero no se publicarán hasta que el KYC esté aprobado. <a href="/kyc">Completar verificación</a></div>';

$contenido = '<h1>Mis productos</h1>'
    . '<p>' . count($productos) . ' producto' . (count($productos) === 1 ? '' : 's') . ' en tu catálogo.</p>'
    . $aviso
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . '<div style="display:flex;gap:10px;margin:20px 0;flex-wrap:wrap;align-items:center">'
    . '<a class="boton" href="/mayorista/productos/nuevo">Nuevo producto</a>'
    . '<form method="get" action="/mayorista/productos" class="filtro-campo">'
    . '<input name="q" value="' . $e($busqueda) . '" placeholder="Buscar por nombre o descripción…" aria-label="Buscar productos">'
    . '<button type="submit" class="filtro-campo__btn" aria-label="Buscar">'
    . '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>'
    . '</button></form></div>'
    . '<table class="tabla-productos"><thead><tr><th>Producto</th><th>Precio</th><th>Unidad</th><th>Stock</th><th>Estado</th><th></th></tr></thead>'
    . '<tbody>' . $filas . '</tbody></table>'
    // ------------------------------------------------- importar inventario
    . '<section class="importar" aria-labelledby="importar-titulo">'
    . '<header class="importar__cab">'
    . '<h2 id="importar-titulo">Importar inventario</h2>'
    . '<p>Da de alta muchos productos de una vez con un archivo CSV. Sirve para montar el '
    . 'catálogo la primera vez o para cargar surtido nuevo sin capturar producto por producto.</p>'
    . '</header>'

    . '<ol class="pasos">'
    . '<li><span class="pasos__num" aria-hidden="true">1</span>'
    . '<strong>Descarga la plantilla</strong>'
    . '<span>Trae los encabezados exactos y dos filas de ejemplo para que veas el formato.</span></li>'
    . '<li><span class="pasos__num" aria-hidden="true">2</span>'
    . '<strong>Llena una fila por producto</strong>'
    . '<span>No cambies el nombre de las columnas y borra las filas de ejemplo antes de subir.</span></li>'
    . '<li><span class="pasos__num" aria-hidden="true">3</span>'
    . '<strong>Sube el archivo</strong>'
    . '<span>Te decimos cuántos productos entraron y, si alguno falla, en qué línea está el problema.</span></li>'
    . '</ol>'

    . '<details class="importar__columnas">'
    . '<summary>Qué va en cada columna</summary>'
    . '<table><thead><tr><th>Columna</th><th>Qué es</th><th>Ejemplo</th></tr></thead><tbody>'
    . '<tr><td><code>sku</code></td><td>Clave única de tu producto. No se puede repetir.</td><td>AZU-50</td></tr>'
    . '<tr><td><code>nombre</code></td><td>Cómo lo verá el comercio en el catálogo.</td><td>Azúcar estándar 50 kg</td></tr>'
    . '<tr><td><code>precio_base</code></td><td>Precio por unidad de venta, sin IVA. Solo números y punto decimal.</td><td>1250.00</td></tr>'
    . '<tr><td><code>stock_disponible</code></td><td>Piezas que tienes hoy. Si es 0 aparece como agotado.</td><td>20</td></tr>'
    . '<tr><td><code>peso_kg</code></td><td>Peso de una unidad, para calcular el envío.</td><td>50</td></tr>'
    . '<tr><td><code>volumen_m3</code></td><td>Volumen de una unidad, para el espacio en el camión.</td><td>0.05</td></tr>'
    . '<tr><td><code>unidad_venta</code></td><td>Cómo se vende: Caja, Pieza, Bulto, Costal…</td><td>Caja</td></tr>'
    . '<tr><td><code>contenido</code></td><td>Qué lleva cada unidad de venta.</td><td>12 pzas</td></tr>'
    . '<tr><td><code>cantidad_minima</code></td><td>Mínimo que puede pedir el comercio.</td><td>1</td></tr>'
    . '<tr><td><code>multiplo_venta</code></td><td>De cuánto en cuánto se puede pedir.</td><td>1</td></tr>'
    . '</tbody></table></details>'

    . '<p class="importar__nota">Archivo CSV separado por comas · máximo 5 MB y 5 000 filas · '
    . 'un SKU repetido se reporta como error en su línea. Todos los productos entran en tu primera '
    . 'categoría; puedes cambiarla después al editarlos.</p>'

    . '<div class="importar__acciones">'
    . '<a class="boton boton--claro" href="/mayorista/productos/plantilla" download>'
    . '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M4 21h16"></path></svg>'
    . 'Descargar plantilla</a>'
    . '<form method="post" action="/mayorista/productos/csv" enctype="multipart/form-data" class="importar__form">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="importar__archivo">'
    . '<span class="visualmente-oculto">Archivo CSV</span>'
    . '<input type="file" name="csv" accept=".csv,text/csv" required>'
    . '</label>'
    . '<button type="submit" class="boton">Importar inventario</button>'
    . '</form>'
    . '</div>'
    . '</section>';

require __DIR__ . '/../layout.php';
