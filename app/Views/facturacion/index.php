<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array|null $datosFiscales */
/** @var array $regimenes */
/** @var array $usos */
/** @var array $facturas */
/** @var array $pedidosFacturables */
/** @var bool $esComercio */
/** @var string|null $mensaje */
/** @var string|null $error */

$dinero = static fn ($v): string => '$' . number_format((float) $v, 2);

// --- Formulario de datos fiscales ------------------------------------------
$opcionesRegimen = '';
foreach ($regimenes as $r) {
    $sel = ($datosFiscales !== null && $datosFiscales['regimen_fiscal'] === $r['clave']) ? ' selected' : '';
    $opcionesRegimen .= '<option value="' . $e($r['clave']) . '"' . $sel . '>' . $e($r['clave'] . ' · ' . $r['nombre']) . '</option>';
}

$opcionesUso = '';
foreach ($usos as $u) {
    $sel = ($datosFiscales !== null && $datosFiscales['uso_cfdi'] === $u['clave']) ? ' selected' : '';
    $opcionesUso .= '<option value="' . $e($u['clave']) . '"' . $sel . '>' . $e($u['clave'] . ' · ' . $u['nombre']) . '</option>';
}

$formDatos = '<div style="background:#fff;border:1px solid #e8e6df;border-radius:12px;padding:18px;max-width:640px">'
    . '<h2 style="margin-top:0">' . ($datosFiscales === null ? 'Registra tus datos fiscales' : 'Tus datos fiscales') . '</h2>'
    . '<p style="font-size:13px;color:#6b7280;margin:0 0 14px">'
    . 'Son necesarios para emitir facturas CFDI 4.0. El RFC se valida con el formato oficial.</p>'
    . '<form method="post" action="/facturacion/datos">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">'
    . '<label><span>RFC</span><input name="rfc" value="' . $e($datosFiscales['rfc'] ?? '') . '" placeholder="XAXX010101000" maxlength="13" required pattern="[A-Za-zÑñ&]{3,4}[0-9]{6}[A-Za-z0-9]{3}"></label>'
    . '<label><span>Razón social</span><input name="razon_social" value="' . $e($datosFiscales['razon_social'] ?? '') . '" required maxlength="200"></label>'
    . '<label><span>Régimen fiscal</span><select name="regimen_fiscal" required>' . $opcionesRegimen . '</select></label>'
    . '<label><span>Código postal</span><input name="cp" value="' . $e($datosFiscales['cp'] ?? '') . '" placeholder="01000" maxlength="5" required pattern="[0-9]{5}"></label>'
    . '<label><span>Uso de CFDI</span><select name="uso_cfdi" required>' . $opcionesUso . '</select></label>'
    . '<label><span>Correo de recepción</span><input type="email" name="correo_receptor" value="' . $e($datosFiscales['correo_receptor'] ?? '') . '" placeholder="facturas@correo.com"></label>'
    . '</div>'
    . '<button type="submit" style="margin-top:14px">Guardar datos</button>'
    . '</form></div>';

// --- Pedidos facturables (solo comercio) ------------------------------------
$listaFacturables = '';
if ($esComercio) {
    foreach ($pedidosFacturables as $p) {
        $listaFacturables .= '<tr><td>' . $e(substr((string) $p['uuid'], 0, 8)) . '</td>'
            . '<td>' . $e((string) $p['mayorista']) . '</td>'
            . '<td style="text-align:right">' . $dinero($p['total']) . '</td>'
            . '<td style="text-align:right">'
            . '<form method="post" action="/pedidos/' . $e($p['uuid']) . '/factura" style="margin:0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" class="boton boton--claro" style="padding:6px 12px;font-size:13px">Timbrar</button>'
            . '</form></td></tr>';
    }

    if ($listaFacturables === '') {
        $listaFacturables = '<tr><td colspan="4" style="color:#9ca3af;text-align:center">Sin pedidos completados pendientes de facturar.</td></tr>';
    }

    $bloqueFacturables = '<div style="background:#fff;border:1px solid #e8e6df;border-radius:12px;padding:18px;margin-top:16px">'
        . '<h2 style="margin-top:0">Pedidos listos para facturar</h2>'
        . '<table><thead><tr><th>Pedido</th><th>Mayorista</th><th style="text-align:right">Total</th><th></th></tr></thead>'
        . '<tbody>' . $listaFacturables . '</tbody></table></div>';
} else {
    $bloqueFacturables = '';
}

// --- Listado de facturas ----------------------------------------------------
$filasFacturas = '';
foreach ($facturas as $f) {
    $filasFacturas .= '<tr><td style="font-variant-numeric:tabular-nums">' . $e($f['serie']) . '-' . (int) $f['folio'] . '</td>'
        . '<td>' . $e($f['rfc_emisor']) . '</td>'
        . '<td>' . $e($f['rfc_receptor']) . '</td>'
        . '<td style="text-align:right">' . $dinero($f['total']) . '</td>'
        . '<td>' . $e(substr((string) $f['uuid_timbrado'], 0, 8)) . '…</td>'
        . '<td><span class="aviso aviso--ok" style="padding:2px 8px;font-size:12px;display:inline-block">' . $e($f['estado']) . '</span></td>'
        . '<td style="text-align:right"><a class="boton boton--claro" style="padding:6px 12px;font-size:13px;text-decoration:none" href="/facturas/' . $e($f['uuid']) . '/xml">XML</a></td>'
        . '</tr>';
}

if ($filasFacturas === '') {
    $filasFacturas = '<tr><td colspan="7" style="color:#9ca3af;text-align:center">Todavía no hay facturas.</td></tr>';
}

$bloqueFacturas = '<div style="background:#fff;border:1px solid #e8e6df;border-radius:12px;padding:18px;margin-top:16px">'
    . '<h2 style="margin-top:0">Facturas</h2>'
    . '<table><thead><tr><th>Folio</th><th>Emisor RFC</th><th>Receptor RFC</th><th style="text-align:right">Total</th><th>UUID timbre</th><th>Estado</th><th></th></tr></thead>'
    . '<tbody>' . $filasFacturas . '</tbody></table></div>';

$aviso = '';
if ($mensaje !== null) {
    $aviso = '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>';
} elseif ($error !== null) {
    $aviso = '<div class="aviso aviso--error">' . $e($error) . '</div>';
}

$contenido = '<h1>Facturación</h1>'
    . $aviso
    . $formDatos
    . $bloqueFacturables
    . $bloqueFacturas;

require __DIR__ . '/../layout.php';
