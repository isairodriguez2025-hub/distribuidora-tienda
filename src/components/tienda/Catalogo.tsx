'use client';

/**
 * Cuadrícula de productos con filtro por sección.
 * Se usa en la portada (destacados) y en las páginas de sección (sin filtro).
 */

import { useMemo, useState } from 'react';

import { ProductCard } from '@/components/tienda/ProductCard';
import { DESTACADOS, SECCIONES, type Producto, type Seccion } from '@/lib/tienda-demo';

type Filtro = 'todos' | Seccion;

export function Catalogo({
  productos = DESTACADOS,
  mostrarFiltros = true,
}: {
  productos?: Producto[];
  mostrarFiltros?: boolean;
}) {
  const [filtro, setFiltro] = useState<Filtro>('todos');

  const visibles = useMemo(
    () => (filtro === 'todos' ? productos : productos.filter((p) => p.seccion === filtro)),
    [filtro, productos],
  );

  return (
    <>
      {mostrarFiltros && (
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
      )}

      <div className="t-grid">
        {visibles.map((p) => (
          <ProductCard key={p.id} producto={p} />
        ))}
      </div>
    </>
  );
}
