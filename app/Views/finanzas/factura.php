<?php
/**
 * Hoja de la factura emitida por el mayorista.
 *
 * @var callable $e
 * @var array    $factura
 * @var array    $lineas
 */

$dinero = static fn (float $v): string => '$' . number_format($v, 2);

$folio = $factura['serie'] . '-' . str_pad((string) $factura['folio'], 6, '0', STR_PAD_LEFT);
$fecha = strtotime((string) ($factura['fecha_timbrado'] ?? $factura['created_at']));

$conceptos = '';
$subtotalLineas = 0.0;

foreach ($lineas as $l) {
    $importe = (float) ($l['subtotal'] ?? ((float) $l['cantidad'] * (float) $l['precio_unitario']));
    $subtotalLineas += $importe;

    $conceptos .= '<tr>'
        . '<td>' . $e((string) $l['nombre_producto']) . '</td>'
        . '<td class="num">' . (float) $l['cantidad'] . '</td>'
        . '<td class="num">' . $dinero((float) $l['precio_unitario']) . '</td>'
        . '<td class="num">' . $dinero($importe) . '</td>'
        . '</tr>';
}

if ($conceptos === '') {
    $conceptos = '<tr><td>Suministro de mercancía según el pedido</td>'
        . '<td class="num">1</td><td class="num">' . $dinero((float) $factura['subtotal']) . '</td>'
        . '<td class="num">' . $dinero((float) $factura['subtotal']) . '</td></tr>';
}

$estadoEtiqueta = ['timbrada' => 'Timbrada', 'generada' => 'Generada', 'cancelada' => 'Cancelada'][(string) $factura['estado']] ?? (string) $factura['estado'];

$contenido = '<div class="factura-hoja-acciones">'
    . '<a class="boton boton--claro" href="/finanzas?tab=facturacion">Volver</a>'
    . '<button type="button" class="boton" onclick="window.print()">Imprimir o guardar PDF</button>'
    . '</div>'

    . '<article class="factura-hoja">'
    . '<header class="factura-hoja__cabeza">'
    . '<div class="factura-hoja__emisor">'
    . '<strong>' . $e((string) $factura['razon_social_emisor']) . '</strong>'
    . '<span>RFC ' . $e((string) $factura['rfc_emisor']) . '</span>'
    . '<span>Régimen fiscal ' . $e((string) $factura['regimen_fiscal_emisor']) . '</span>'
    . '<span>Lugar de expedición ' . $e((string) $factura['lugar_expedicion']) . '</span>'
    . '</div>'
    . '<div class="factura-hoja__folio">'
    . '<span class="factura-hoja__tipo">Factura</span>'
    . '<strong>' . $e($folio) . '</strong>'
    . '<span class="factura-hoja__estado factura-hoja__estado--' . $e((string) $factura['estado']) . '">' . $e($estadoEtiqueta) . '</span>'
    . '</div>'
    . '</header>'

    . '<section class="factura-hoja__partes">'
    . '<div><h3>Receptor</h3>'
    . '<strong>' . $e((string) $factura['razon_social_receptor']) . '</strong>'
    . '<span>RFC ' . $e((string) $factura['rfc_receptor']) . '</span>'
    . '<span>CP ' . $e((string) $factura['cp_receptor']) . ' · Uso CFDI ' . $e((string) $factura['uso_cfdi_receptor']) . '</span>'
    . '</div>'
    . '<div><h3>Datos del comprobante</h3>'
    . '<span>Fecha de emisión: ' . date('d/m/Y H:i', $fecha) . '</span>'
    . '<span>Método de pago: ' . $e((string) $factura['metodo_pago']) . '</span>'
    . '<span>Forma de pago: ' . $e((string) $factura['forma_pago']) . '</span>'
    . '</div>'
    . '</section>'

    . '<table class="factura-hoja__tabla tabla-recibo tabla-ancha">'
    . '<thead><tr><th>Concepto</th><th class="num">Cantidad</th><th class="num">Precio unitario</th><th class="num">Importe</th></tr></thead>'
    . '<tbody>' . $conceptos . '</tbody>'
    . '</table>'

    . '<div class="factura-hoja__totales">'
    . '<div><span>Subtotal</span><b>' . $dinero((float) $factura['subtotal']) . '</b></div>'
    . '<div><span>IVA</span><b>' . $dinero((float) $factura['iva']) . '</b></div>'
    . '<div class="factura-hoja__total"><span>Total</span><b>' . $dinero((float) $factura['total']) . '</b></div>'
    . '</div>'

    . '<footer class="factura-hoja__pie">'
    . '<span>Este documento es el comprobante fiscal que emitió el mayorista por tu pedido.</span>'
    . ($factura['uuid_timbrado'] !== null
        ? '<span class="factura-hoja__uuid">Folio fiscal ' . $e((string) $factura['uuid_timbrado']) . '</span>'
        : '')
    . '</footer>'
    . '</article>';

require __DIR__ . '/../layout.php';
