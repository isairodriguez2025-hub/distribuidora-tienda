'use client';

/**
 * Tarjeta de producto reutilizable: portada, sección y ficha de producto.
 * La imagen se sustituye por un icono por sección sobre fondo tintado hasta
 * que exista la carga real de fotografías.
 */

import Link from 'next/link';

import { useTienda } from '@/components/tienda/TiendaShell';
import { mxn, type Producto, type Seccion } from '@/lib/tienda-demo';

const ICONO: Record<Seccion, React.ReactNode> = {
  abarrotes: (
    <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M10 16h28l-2.6 22a3 3 0 0 1-3 2.6H15.6a3 3 0 0 1-3-2.6z" />
      <path d="M17 16V12a7 7 0 0 1 14 0v4" />
    </svg>
  ),
  limpieza: (
    <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M19 18h10v20a3 3 0 0 1-3 3h-4a3 3 0 0 1-3-3z" />
      <path d="M18 18h12M24 18V9h7" />
      <circle cx="14" cy="14" r="2.4" />
    </svg>
  ),
  herreria: (
    <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M27 12 12 27l9 9 15-15z" />
      <path d="M27 12l4-4 9 9-4 4" />
      <path d="M17 22l4 4" />
    </svg>
  ),
};

export function ProductCard({ producto, mostrarSeccion = true }: { producto: Producto; mostrarSeccion?: boolean }) {
  const { agregar } = useTienda();
  const bajo = producto.stock <= 8;
  const unidad = `por ${producto.presentacion.split(' ')[0].toLowerCase()}`;

  return (
    <article className="t-card">
      <Link href={`/tienda/producto/${producto.slug}`} className="t-card__link" aria-label={producto.nombre}>
        <div className="t-card__media" data-seccion={producto.seccion} aria-hidden="true">
          {ICONO[producto.seccion]}
          {producto.etiqueta && <span className="t-card__tag">{producto.etiqueta}</span>}
          <span className={`t-card__stock${bajo ? ' is-low' : ''}`}>
            {bajo ? `Quedan ${producto.stock}` : `${producto.stock} disponibles`}
          </span>
        </div>

        <div className="t-card__body">
          {mostrarSeccion && (
            <span className="t-card__cat">{producto.marca}</span>
          )}
          <h3 className="t-card__name">{producto.nombre}</h3>
          <span className="t-card__pres">{producto.presentacion}</span>
          <div className="t-card__price">
            <b>{mxn.format(producto.precio)}</b>
            <small>{unidad}</small>
          </div>
        </div>
      </Link>

      <div className="t-card__cta">
        <button
          type="button"
          className="t-btn t-btn--ghost t-btn--sm t-btn--wide"
          onClick={() => agregar(producto.nombre)}
        >
          Agregar al pedido
        </button>
      </div>
    </article>
  );
}
