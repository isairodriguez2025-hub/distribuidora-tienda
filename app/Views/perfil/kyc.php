<?php
/** @var callable $e */
/** @var string $csrf */
/** @var string $rol */
/** @var array $kyc */
/** @var string[] $tipos */
/** @var string|null $mensaje */
/** @var string|null $error */
$filas = '';
foreach ($kyc['documentos'] as $d) {
    $color = match ($d['estado']) {
        'aprobado'  => '#1f4d3a',
        'rechazado' => '#b91c1c',
        default     => '#a56a12',
    };
    $filas .= '<tr><td>' . $e($d['tipo']) . '</td><td style="color:' . $color . '">' . $e($d['estado']) . '</td>'
        . '<td>' . $e(substr((string) $d['created_at'], 0, 10)) . '</td>'
        . '<td>' . ($d['motivo_rechazo'] ? $e($d['motivo_rechazo']) : '—') . '</td></tr>';
}
if ($filas === '') {
    $filas = '<tr><td colspan="4" style="color:#6b7280">Sin documentos todavía.</td></tr>';
}

$opciones = '';
foreach ($tipos as $t) {
    $opciones .= '<option value="' . $e($t) . '">' . $e($t) . '</option>';
}

$requeridos = $kyc['requeridos'] === []
    ? '<p style="color:#6b7280">Tu rol no requiere documentos.</p>'
    : '<p>Documentos obligatorios: <strong>' . $e(implode(', ', $kyc['requeridos'])) . '</strong></p>';

$contenido = '<h1>Verificación de identidad</h1>'
    . '<p class="pista" style="margin:-4px 0 14px"><a href="/perfil">Mi perfil</a></p>'
    . '<p>Sube tus documentos en JPG, PNG o PDF (máximo 10 MB cada uno).</p>'
    . $requeridos
    . ($mensaje !== null ? '<p style="background:#eaf1ec;border:1px solid #cfdcd3;padding:10px 12px;border-radius:8px">' . $e($mensaje) . '</p>' : '')
    . ($error !== null ? '<p style="color:#b91c1c;background:#fef2f2;border:1px solid #f6d5d5;padding:10px 12px;border-radius:8px">' . $e($error) . '</p>' : '')
    . '<form method="post" action="/kyc" enctype="multipart/form-data" style="margin:18px 0">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label style="display:block;margin-bottom:10px"><span style="font-size:13px;color:#4b5563">Tipo de documento</span>'
    . '<select name="tipo" style="width:100%;height:40px;padding:0 12px;border:1px solid #e6e4dd;border-radius:8px;font:inherit">' . $opciones . '</select></label>'
    . '<label style="display:block;margin-bottom:14px"><span style="font-size:13px;color:#4b5563">Archivo</span>'
    . '<input type="file" name="documento" accept=".jpg,.jpeg,.png,.pdf" required style="font:inherit"></label>'
    . '<button type="submit" style="height:42px;padding:0 18px;background:#1f4d3a;color:#fff;border:0;border-radius:8px;font:inherit;cursor:pointer">Subir documento</button>'
    . '</form>'
    . '<h2 style="font-size:18px;margin-top:28px">Mis documentos</h2>'
    . '<table style="width:100%;border-collapse:collapse;font-size:14px">'
    . '<thead><tr style="text-align:left;border-bottom:1px solid #e6e4dd"><th>Tipo</th><th>Estado</th><th>Fecha</th><th>Motivo</th></tr></thead>'
    . '<tbody>' . $filas . '</tbody></table>';

require __DIR__ . '/../layout.php';
