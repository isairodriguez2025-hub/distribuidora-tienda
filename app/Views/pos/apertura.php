<?php
/** @var callable $e */ /** @var string $csrf */ /** @var array $ajustes */ /** @var int $productos */ /** @var string|null $mensaje */

$contenido = '<div class="tarjeta" style="max-width:520px;margin:60px auto;padding:32px">'
    . '<h1 style="margin:0 0 6px">Abrir caja</h1>'
    . '<p class="silencio" style="margin:0 0 22px">Antes de cobrar necesitas declarar con cuánto efectivo empiezas el turno.</p>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . '<div class="grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">'
    . '<div class="tarjeta" style="padding:14px"><div class="pista">Productos en inventario</div>'
    . '<div style="font-size:26px;font-weight:700">' . (int) $productos . '</div></div>'
    . '<div class="tarjeta" style="padding:14px"><div class="pista">Margen de venta</div>'
    . '<div style="font-size:26px;font-weight:700">' . number_format((float) $ajustes['margen_venta'], 0) . '%</div></div>'
    . '</div>'
    . '<form method="post" action="/pos/turno/abrir">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span>Fondo inicial en efectivo</span>'
    . '<input type="number" name="fondo_inicial" min="0" step="0.01" value="0" inputmode="decimal" required autofocus></label>'
    . '<button type="submit" style="width:100%;margin-top:16px">Abrir caja</button></form>'
    . '<p class="silencio" style="margin:18px 0 0;font-size:13px">Si tu inventario está vacío, se llena solo con los pedidos que ya te entregaron.</p>'
    . '</div>';

require __DIR__ . '/../layout.php';
