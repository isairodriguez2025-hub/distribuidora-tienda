import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';

import { AgregarButton } from '@/components/tienda/AgregarButton';
import { ProductCard } from '@/components/tienda/ProductCard';
import {
  buscar,
  mxn,
  PRODUCTOS,
  SECCION_POR_SLUG,
  porSeccion,
  type Seccion,
} from '@/lib/tienda-demo';

export const dynamicParams = false;

export function generateStaticParams() {
  return PRODUCTOS.map((p) => ({ slug: p.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const p = buscar(slug);
  if (!p) return { title: 'Producto no encontrado' };
  return { title: `${p.nombre} · Distribuidora`, description: p.descripcion };
}

export default async function ProductoPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const producto = buscar(slug);
  if (!producto) notFound();

  const seccion = SECCION_POR_SLUG(producto.seccion);
  const relacionados = porSeccion(producto.seccion as Seccion)
    .filter((p) => p.id !== producto.id)
    .slice(0, 3);

  const bajo = producto.stock <= 8;
  const unidad = `por ${producto.presentacion.split(' ')[0].toLowerCase()}`;

  return (
    <>
      <div className="t-wrap">
        <nav className="t-crumb" aria-label="Ruta">
          <Link href="/tienda">Inicio</Link>
          <span>/</span>
          <Link href={`/tienda/${seccion?.slug}`}>{seccion?.nombre}</Link>
          <span>/</span>
          <span aria-current="page">{producto.nombre}</span>
        </nav>
      </div>

      <section className="t-section t-section--tight">
        <div className="t-wrap">
          <div className="t-product">
            <div className="t-product__media" data-seccion={producto.seccion}>
              <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">
                {producto.seccion === 'abarrotes' && (
                  <>
                    <path d="M10 16h28l-2.6 22a3 3 0 0 1-3 2.6H15.6a3 3 0 0 1-3-2.6z" />
                    <path d="M17 16V12a7 7 0 0 1 14 0v4" />
                  </>
                )}
                {producto.seccion === 'limpieza' && (
                  <>
                    <path d="M19 18h10v20a3 3 0 0 1-3 3h-4a3 3 0 0 1-3-3z" />
                    <path d="M18 18h12M24 18V9h7" />
                    <circle cx="14" cy="14" r="2.4" />
                  </>
                )}
                {producto.seccion === 'herreria' && (
                  <>
                    <path d="M27 12 12 27l9 9 15-15z" />
                    <path d="M27 12l4-4 9 9-4 4" />
                    <path d="M17 22l4 4" />
                  </>
                )}
              </svg>
              <span className="t-product__hint">Foto del producto pendiente</span>
            </div>

            <div className="t-product__info">
              <span className="t-card__cat">{producto.marca}</span>
              <h1>{producto.nombre}</h1>
              <p className="t-product__desc">{producto.descripcion}</p>

              <div className="t-product__price">
                <b>{mxn.format(producto.precio)}</b>
                <span>{unidad} · {producto.presentacion}</span>
              </div>

              <div className={`t-product__stock${bajo ? ' is-low' : ''}`}>
                <span className="t-dot" />
                {bajo ? `Quedan ${producto.stock} piezas` : `${producto.stock} disponibles`}
              </div>

              <AgregarButton nombre={producto.nombre} />

              <ul className="t-list">
                {producto.incluye.map((i) => (
                  <li key={i}>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                      <path d="m4 12.5 5 5L20 6.5" />
                    </svg>
                    {i}
                  </li>
                ))}
              </ul>

              <dl className="t-specs">
                <div><dt>Código</dt><dd>{producto.id.toUpperCase()}-{producto.slug.slice(0, 6).toUpperCase()}</dd></div>
                <div><dt>Sección</dt><dd>{seccion?.nombre}</dd></div>
                <div><dt>Unidad de venta</dt><dd>{producto.presentacion}</dd></div>
                <div><dt>Entrega</dt><dd>24 a 48 h · CDMX y zona metropolitana</dd></div>
              </dl>

              <p className="t-product__note">
                Precios de mayoreo. Para volúmenes mayores a 10 cajas, pide cotización por WhatsApp.
              </p>
            </div>
          </div>
        </div>
      </section>

      {relacionados.length > 0 && (
        <section className="t-section t-section--tight" style={{ borderTop: '1px solid var(--t-line)' }}>
          <div className="t-wrap">
            <div className="t-sechead">
              <div>
                <h2>Del mismo pasillo</h2>
                <p>Lo que suelen pedir junto con este producto.</p>
              </div>
            </div>
            <div className="t-grid">
              {relacionados.map((p) => (
                <ProductCard key={p.id} producto={p} />
              ))}
            </div>
          </div>
        </section>
      )}
    </>
  );
}
