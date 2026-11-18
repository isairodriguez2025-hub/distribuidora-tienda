<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Iconos de la navegación dibujados con trazo. Heredan el color, así sirven
 * igual en tema claro y oscuro sin archivos extra.
 */
final class Iconos
{
    private const DIBUJOS = [
        '/explorar'            => '<path d="M12 3v3M12 18v3M3 12h3M18 12h3"></path><circle cx="12" cy="12" r="4.2"></circle>',
        '/buscar'              => '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path>',
        '/pedidos'             => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><path d="M14 3v5h5"></path><path d="M9 13h6M9 17h4"></path>',
        '/mayorista/pedidos'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><path d="M14 3v5h5"></path><path d="M9 13h6M9 17h4"></path>',
        '/favoritos'           => '<path d="m12 3.6 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.8l5.9-.9Z"></path>',
        '/portafolio'          => '<path d="m12 3.6 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.8l5.9-.9Z"></path>',
        '/programados'         => '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 11h18"></path>',
        '/agendar'             => '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 11h18"></path><path d="M12 15v3"></path>',
        '/mayorista/productos' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"></path><path d="M3 8v8l9 5 9-5V8"></path><path d="M12 13v8"></path>',
        '/finanzas'            => '<path d="M4 19V10M10 19V5M16 19v-6M22 19H2"></path>',
        '/facturacion'         => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"></path><path d="M9 8h6M9 12h6"></path>',
        '/admin'               => '<path d="M12 2.5 4.5 5.5v5.9c0 5 3.2 9.1 7.5 10.6 4.3-1.5 7.5-5.6 7.5-10.6V5.5Z"></path>',
        '/pos/inventario'      => '<path d="M3 7h18v13H3z"></path><path d="M3 7l2-4h14l2 4"></path><path d="M9 12h6"></path>',
        '/pos'                 => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 7h8M8 11h8M8 15h4"></path>',
        '/pos/ventas'          => '<path d="M4 4h16v16H4z"></path><path d="M8 9h8M8 13h5"></path>',
        '/admin/kyc'           => '<circle cx="12" cy="12" r="9"></circle><path d="m8.5 12 2.4 2.4 4.6-4.6"></path>',
        '/mensajes'            => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9 9 0 0 1-3.9-.9L3 21l1.9-4.6A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5Z"></path>',
        '/perfil'              => '<circle cx="12" cy="8" r="3.6"></circle><path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path>',
    ];

    public static function svg(string $ruta, int $tamano = 21): string
    {
        $dibujo = self::DIBUJOS[$ruta] ?? '<circle cx="12" cy="12" r="8"></circle>';

        return '<svg viewBox="0 0 24 24" width="' . $tamano . '" height="' . $tamano . '" fill="none" stroke="currentColor"'
            . ' stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . $dibujo . '</svg>';
    }
}
