<?php
/**
 * Caja: catálogo a la izquierda, ticket a la derecha.
 *
 * @var callable $e
 * @var string   $csrf
 * @var array    $turno
 * @var array    $productos
 * @var array    $clientes
 * @var array    $ajustes
 * @var float    $vendido
 */
$ivaIncluido = (int) $ajustes['iva_incluido'] === 1;

// Clave de búsqueda sin acentos: "cafe" debe encontrar "Café molido".
$plano = static function (string $texto): string {
    return strtr(strtolower($texto), [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ]);
};

$tarjetas = '';

foreach ($productos as $p) {
    $agotado = (float) $p['stock'] <= 0;
    $bajo = (float) $p['stock'] <= (float) $p['stock_minimo'];

    $tarjetas .= '<button type="button" class="prod' . ($agotado ? ' prod--agotado' : '') . '"'
        . ' data-id="' . (int) $p['id'] . '"'
        . ' data-nombre="' . $e((string) $p['nombre']) . '"'
        . ' data-precio="' . number_format((float) $p['precio_venta'], 2, '.', '') . '"'
        . ' data-tasa="' . number_format((float) $p['iva_tasa'], 4, '.', '') . '"'
        . ' data-stock="' . number_format((float) $p['stock'], 3, '.', '') . '"'
        . ' data-unidad="' . $e((string) $p['unidad']) . '"'
        . ' data-clave="' . $e($plano((string) $p['nombre'] . ' ' . ($p['sku'] ?? '') . ' ' . ($p['codigo_barras'] ?? ''))) . '"'
        . ($agotado ? ' disabled' : '') . '>'
        . '<span class="prod__nombre">' . $e((string) $p['nombre']) . '</span>'
        . '<span class="prod__precio">$' . number_format((float) $p['precio_venta'], 2) . '</span>'
        . '<span class="prod__stock' . ($bajo ? ' prod__stock--bajo' : '') . '">'
        . ($agotado ? 'agotado' : number_format((float) $p['stock'], 2) . ' ' . $e((string) $p['unidad']))
        . '</span></button>';
}

if ($tarjetas === '') {
    $tarjetas = '<div class="pos__vacio">No hay productos todavía. '
        . 'Se agregan solos cuando recibes un pedido, o puedes capturarlos en '
        . '<a href="/pos/productos">Inventario</a>.</div>';
}

$opcionesCliente = '<option value="">Público general</option>';

foreach ($clientes as $c) {
    $opcionesCliente .= '<option value="' . (int) $c['id'] . '">' . $e((string) $c['nombre']) . '</option>';
}

$contenido = '<div class="pos" data-iva-incluido="' . ($ivaIncluido ? '1' : '0') . '" data-csrf="' . $e($csrf) . '">'
    . '<section class="pos__catalogo">'
    . '<div class="pos__barra">'
    . '<div><h1 style="margin:0">Caja</h1>'
    . '<p class="silencio" style="margin:2px 0 0">Turno abierto · vendido hoy $' . number_format($vendido, 2) . '</p></div>'
    . '<input type="search" id="pos-buscar" placeholder="Buscar producto o código…" autocomplete="off">'
    . '</div>'
    . '<div class="pos__sin-buscar" id="pos-vacio">'
    . '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">'
    . '<circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.6-3.6"></path></svg>'
    . '<strong>Busca un producto</strong>'
    . '<span>Escribe el nombre o la clave, o pasa el lector de código de barras.</span>'
    . '</div>'
    . '<div class="pos__grid" id="pos-grid" hidden>' . $tarjetas . '</div>'
    . '<p class="pos__resultados" id="pos-resultados" hidden></p>'
    . '<div class="pos__sin-buscar" id="pos-sin" hidden>'
    . '<strong>Sin resultados</strong>'
    . '<span>No hay productos que coincidan. Prueba con otra palabra.</span>'
    . '</div>'
    . '</section>'

    . '<aside class="pos__ticket">'
    . '<div class="pos__ticket-head"><strong>Ticket</strong>'
    . '<span class="pista" id="pos-conteo">0 artículos</span></div>'
    . '<div class="pos__lineas" id="pos-lineas">'
    . '<p class="silencio" style="text-align:center;margin:24px 0">Toca un producto para agregarlo.</p></div>'

    . '<div class="pos__totales">'
    . '<div><span>Subtotal</span><span id="pos-subtotal">$0.00</span></div>'
    . '<div><span>IVA</span><span id="pos-iva">$0.00</span></div>'
    . '<div class="pos__total"><span>Total</span><span id="pos-total">$0.00</span></div>'
    . '</div>'

    . '<div class="pos__pago">'
    . '<label class="campo"><span>Método</span><select id="pos-metodo">'
    . '<option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option>'
    . '<option value="transferencia">Transferencia</option><option value="credito">Crédito</option>'
    . '</select></label>'
    . '<label class="campo" id="pos-recibido-campo"><span>Recibido</span>'
    . '<input type="number" id="pos-recibido" min="0" step="0.01" inputmode="decimal" placeholder="0.00"></label>'
    . '<label class="campo"><span>Cliente</span><select id="pos-cliente">' . $opcionesCliente . '</select></label>'
    . '<div class="pos__cambio" id="pos-cambio" hidden><span>Cambio</span><strong>$0.00</strong></div>'
    . '</div>'

    . '<button type="button" id="pos-cobrar" class="pos__cobrar" disabled>Cobrar</button>'
    . '<button type="button" id="pos-limpiar" class="boton--claro" style="width:100%;margin-top:8px">Limpiar ticket</button>'
    . '<div style="display:flex;gap:8px;margin-top:12px">'
    . '<a href="/pos/ventas" class="boton--claro" style="flex:1;text-align:center">Ventas</a>'
    . '<a href="/pos/productos" class="boton--claro" style="flex:1;text-align:center">Inventario</a>'
    . '</div>'
    . '<form method="post" action="/pos/turno/cerrar" style="margin-top:10px" '
    . 'onsubmit="return confirm(\'¿Cerrar la caja? Se calculará el corte del turno.\')">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<input type="hidden" name="efectivo_contado" value="0">'
    . '<button type="submit" class="boton--claro" style="width:100%">Cerrar caja</button></form>'
    . '</aside></div>';

$contenido .= '<div id="pos-modal" class="pos-modal" hidden><div class="pos-modal__caja">'
    . '<div class="pos-modal__icono">✓</div>'
    . '<h2 id="pos-modal-titulo">Venta registrada</h2>'
    . '<p class="silencio" id="pos-modal-detalle"></p>'
    . '<div style="display:grid;gap:8px;margin-top:18px">'
    . '<a id="pos-modal-ticket" class="boton--claro" target="_blank" rel="noopener" style="text-align:center">Ver ticket</a>'
    . '<button type="button" onclick="document.getElementById(\'pos-modal\').hidden=true;location.reload()">Nueva venta</button>'
    . '</div></div></div>';

$scripts = <<<'JS'
(function () {
    var caja = document.querySelector('.pos');
    if (!caja) return;

    var csrf = caja.dataset.csrf;
    var ivaIncluido = caja.dataset.ivaIncluido === '1';
    var carrito = [];
    var grid = document.getElementById('pos-grid');
    var cont = document.getElementById('pos-lineas');
    var buscador = document.getElementById('pos-buscar');
    var metodo = document.getElementById('pos-metodo');
    var recibido = document.getElementById('pos-recibido');
    var campoRecibido = document.getElementById('pos-recibido-campo');
    var cambioBox = document.getElementById('pos-cambio');
    var cobrar = document.getElementById('pos-cobrar');
    var modal = document.getElementById('pos-modal');

    function dinero(n) { return '$' + n.toFixed(2); }

    function totales() {
        var subtotal = 0, iva = 0, total = 0;
        carrito.forEach(function (l) {
            var bruto = l.precio * l.cantidad;
            if (ivaIncluido) { total += bruto; subtotal += bruto / (1 + l.tasa); }
            else { subtotal += bruto; total += bruto * (1 + l.tasa); }
            iva = total - subtotal;
        });
        return { subtotal: subtotal, iva: iva, total: total };
    }

    function pintar() {
        var t = totales();
        document.getElementById('pos-subtotal').textContent = dinero(t.subtotal);
        document.getElementById('pos-iva').textContent = dinero(t.iva);
        document.getElementById('pos-total').textContent = dinero(t.total);

        var articulos = carrito.reduce(function (a, l) { return a + l.cantidad; }, 0);
        document.getElementById('pos-conteo').textContent = articulos + (articulos === 1 ? ' artículo' : ' artículos');

        if (!carrito.length) {
            cont.innerHTML = '<p class="silencio" style="text-align:center;margin:24px 0">Toca un producto para agregarlo.</p>';
        } else {
            cont.innerHTML = '';
            carrito.forEach(function (l, i) {
                var fila = document.createElement('div');
                fila.className = 'pos-linea';
                fila.innerHTML =
                    '<div class="pos-linea__info"><strong></strong><span></span></div>' +
                    '<div class="pos-linea__ctrl">' +
                    '<button type="button" data-accion="menos" data-i="' + i + '">−</button>' +
                    '<span>' + l.cantidad + '</span>' +
                    '<button type="button" data-accion="mas" data-i="' + i + '">+</button>' +
                    '<button type="button" class="pos-linea__quitar" data-accion="quitar" data-i="' + i + '">×</button>' +
                    '</div><div class="pos-linea__total">' + dinero(l.precio * l.cantidad) + '</div>';
                fila.querySelector('strong').textContent = l.nombre;
                fila.querySelector('span').textContent = dinero(l.precio) + ' · ' + l.unidad;
                cont.appendChild(fila);
            });
        }

        var efectivo = metodo.value === 'efectivo';
        campoRecibido.hidden = !efectivo;
        var pago = efectivo ? parseFloat(recibido.value || '0') : t.total;
        var falta = t.total - pago;

        if (efectivo) {
            cambioBox.hidden = !(pago > 0);
            cambioBox.querySelector('strong').textContent = dinero(Math.max(0, -falta));
        } else {
            cambioBox.hidden = true;
        }

        cobrar.disabled = !carrito.length || (efectivo && pago + 0.001 < t.total);
        cobrar.textContent = carrito.length ? 'Cobrar ' + dinero(t.total) : 'Cobrar';
    }

    function agregar(el) {
        var id = Number(el.dataset.id);
        var stock = Number(el.dataset.stock);
        var existente = carrito.find(function (l) { return l.id === id; });

        if (existente) {
            if (existente.cantidad + 1 > stock) { alert('Solo quedan ' + stock + ' de ' + el.dataset.nombre); return; }
            existente.cantidad++;
        } else {
            carrito.push({ id: id, nombre: el.dataset.nombre, precio: Number(el.dataset.precio),
                           tasa: Number(el.dataset.tasa), unidad: el.dataset.unidad, cantidad: 1, stock: stock });
        }
        pintar();
    }

    grid.addEventListener('click', function (ev) {
        var el = ev.target.closest('.prod');
        if (el && !el.disabled) agregar(el);
    });

    cont.addEventListener('click', function (ev) {
        var b = ev.target.closest('button[data-accion]');
        if (!b) return;
        var i = Number(b.dataset.i);
        var accion = b.dataset.accion;

        if (accion === 'mas') {
            if (carrito[i].cantidad + 1 > carrito[i].stock) { alert('No hay más stock.'); return; }
            carrito[i].cantidad++;
        } else if (accion === 'menos') {
            carrito[i].cantidad--;
            if (carrito[i].cantidad <= 0) carrito.splice(i, 1);
        } else {
            carrito.splice(i, 1);
        }
        pintar();
    });

    // El catálogo no se despliega solo: aparece cuando se escribe algo.
    var vacio = document.getElementById('pos-vacio');
    var sin = document.getElementById('pos-sin');
    var resultados = document.getElementById('pos-resultados');
    var TOPE = 24;

    function plano(t) {
        return t.toLowerCase()
            .replace(/[áàâä]/g, 'a').replace(/[éèêë]/g, 'e').replace(/[íìîï]/g, 'i')
            .replace(/[óòôö]/g, 'o').replace(/[úùûü]/g, 'u').replace(/ñ/g, 'n').replace(/ç/g, 'c');
    }

    function filtrar() {
        var q = plano(buscador.value.trim());
        var total = 0;
        var visibles = 0;

        [].forEach.call(grid.querySelectorAll('.prod'), function (el) {
            var clave = el.dataset.clave || el.dataset.nombre.toLowerCase();
            var coincide = q !== '' && clave.indexOf(q) !== -1;

            if (coincide) {
                total++;
            }

            var mostrar = coincide && visibles < TOPE;
            el.hidden = !mostrar;

            if (mostrar) {
                visibles++;
            }
        });

        grid.hidden = visibles === 0;
        vacio.hidden = q !== '';
        sin.hidden = q === '' || total > 0;

        if (total === 0) {
            resultados.hidden = true;
            resultados.textContent = '';
        } else {
            resultados.hidden = false;
            resultados.textContent = total > TOPE
                ? 'Mostrando ' + TOPE + ' de ' + total + ' · escribe más para afinar'
                : total + (total === 1 ? ' producto' : ' productos');
        }
    }

    buscador.addEventListener('input', filtrar);
    buscador.addEventListener('search', filtrar);
    filtrar();

    metodo.addEventListener('change', pintar);
    recibido.addEventListener('input', pintar);
    document.getElementById('pos-limpiar').addEventListener('click', function () { carrito = []; recibido.value = ''; pintar(); });

    cobrar.addEventListener('click', function () {
        var t = totales();
        cobrar.disabled = true;
        cobrar.textContent = 'Cobrando…';

        fetch('/pos/vender', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({
                items: carrito.map(function (l) { return { id: l.id, cantidad: l.cantidad }; }),
                metodo: metodo.value,
                recibido: metodo.value === 'efectivo' ? Number(recibido.value || 0) : t.total,
                cliente_id: document.getElementById('pos-cliente').value || null
            })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) { alert(d.mensaje || 'No se pudo cobrar.'); pintar(); return; }
            document.getElementById('pos-modal-titulo').textContent = 'Venta ' + d.folio;
            document.getElementById('pos-modal-detalle').textContent =
                'Total ' + dinero(Number(d.total)) + (Number(d.cambio) > 0 ? ' · Cambio ' + dinero(Number(d.cambio)) : '');
            document.getElementById('pos-modal-ticket').href = '/pos/ticket/' + d.uuid;
            modal.hidden = false;
        }).catch(function () {
            alert('Error de red. Revisa la conexión e inténtalo otra vez.');
            pintar();
        });
    });

    recibido.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); cobrar.click(); } });
    pintar();
})();
JS;

require __DIR__ . '/../layout.php';
