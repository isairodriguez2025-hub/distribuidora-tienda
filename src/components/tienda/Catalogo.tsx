'use client';

/**
 * Cuadrícula de productos destacados con filtro por sección.
 * Los datos son de demostración (src/lib/tienda-demo.ts): cuando exista el
 * endpoint público de productos, se reemplaza por un fetch al servidor y la
 * cuadrícula no cambia.
 */

import { useMemo, useState } from 'react';

import { useTienda } from '@/components/tienda/TiendaShell';
import { mxn, PRODUCTOS_DESTACADOS, SECCIONES, type Seccion } from '@/lib/tienda-demo';

type Filtro = 'todos' | Seccion;

const ICONO_POR_SECCION: Record<Seccion, React.ReactNode> = {
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

export function Catalogo() {
  const [filtro, setFiltro] = useState<Filtro>('todos');
  const { agregar } = useTienda();

  const productos = useMemo(
    () =>
      filtro === 'todos'
        ? PRODUCTOS_DESTACADOS
        : PRODUCTOS_DESTACADOS.filter((p) => p.seccion === filtro),
    [filtro],
  );

  const nombreSeccion = (id: Seccion) => SECCIONES.find((s) => s.id === id)?.nombre ?? '';

  return (
    <>
      <div className="t-tabs" role="tablist" aria-label="Filtrar productos por sección">
        <button
          type="button"
          role="tab"
          aria-selected={filtro === 'todos'}
          className={`t-tab${filtro === 'todos' ? ' is-active' : ''}`}
          onClick={() => setFiltro('todos')}
        >
          Todos
        </button>
        {SECCIONES.map((s) => (
          <button
            key={s.id}
            type="button"
            role="tab"
            aria-selected={filtro === s.id}
            className={`t-tab${filtro === s.id ? ' is-active' : ''}`}
            onClick={() => setFiltro(s.id)}
          >
            {s.nombre}
          </button>
        ))}
      </div>

      <div className="t-grid">
        {productos.map((p) => {
          const bajo = p.stock <= 8;
          const unidad = `por ${p.presentacion.split(' ')[0].toLowerCase()}`;
          return (
            <article key={p.id} className="t-card">
              <div className="t-card__media" data-seccion={p.seccion} aria-hidden="true">
                {ICONO_POR_SECCION[p.seccion]}
                {p.etiqueta && <span className="t-card__tag">{p.etiqueta}</span>}
                <span className={`t-card__stock${bajo ? ' is-low' : ''}`}>
                  {bajo ? `Quedan ${p.stock}` : `${p.stock} disponibles`}
                </span>
              </div>

              <div className="t-card__body">
                <span className="t-card__cat">{nombreSeccion(p.seccion)}</span>
                <h3 className="t-card__name">{p.nombre}</h3>
                <span className="t-card__pres">{p.presentacion}</span>

                <div className="t-card__price">
                  <b>{mxn.format(p.precio)}</b>
                  <small>{unidad}</small>
                </div>

                <button
                  type="button"
                  className="t-btn t-btn--ghost t-btn--sm t-btn--wide"
                  onClick={() => agregar(p.nombre)}
                >
                  Agregar al pedido
                </button>
              </div>
            </article>
          );
        })}
      </div>
    </>
  );
}
