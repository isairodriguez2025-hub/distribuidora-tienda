'use client';

import { useTienda } from '@/components/tienda/TiendaShell';

/**
 * Botón de compra de la ficha de producto.
 * Vive aparte porque la ficha se renderiza en el servidor y necesita un
 * componente cliente para tocar el carrito.
 */
export function AgregarButton({ nombre }: { nombre: string }) {
  const { agregar } = useTienda();

  return (
    <div className="t-product__cta">
      <button type="button" className="t-btn t-btn--primary" onClick={() => agregar(nombre)}>
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <circle cx="9" cy="20" r="1.5" />
          <circle cx="18" cy="20" r="1.5" />
          <path d="M2 3h2.2l2.3 12.2a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L21 7H6" />
        </svg>
        Agregar al pedido
      </button>
      <a href="https://wa.me/525512345678" className="t-btn t-btn--ghost">
        Cotizar mayoreo
      </a>
    </div>
  );
}
