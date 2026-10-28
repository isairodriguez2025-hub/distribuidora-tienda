import Link from 'next/link';

/** Bloque 404 compartido por la página /tienda/404 y por el not-found del sitio. */
export function NoEncontrado() {
  return (
    <section className="t-section">
      <div className="t-wrap">
        <div className="t-404">
          <span className="t-404__code">404</span>
          <h1>No encontramos esta página</h1>
          <p>
            Puede que el producto ya no esté en catálogo o que el enlace esté mal
            escrito. Desde el inicio llegas a las tres secciones en un clic.
          </p>
          <div className="t-404__acciones">
            <Link href="/tienda" className="t-btn t-btn--primary">Ir al inicio</Link>
            <Link href="/tienda/abarrotes" className="t-btn t-btn--ghost">Ver abarrotes</Link>
          </div>
        </div>
      </div>
    </section>
  );
}
