import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';

import { Catalogo } from '@/components/tienda/Catalogo';
import { porSeccion, SECCIONES, SECCION_POR_SLUG, type Seccion } from '@/lib/tienda-demo';

export const dynamicParams = false;

export function generateStaticParams() {
  return SECCIONES.map((s) => ({ seccion: s.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ seccion: string }>;
}): Promise<Metadata> {
  const { seccion } = await params;
  const sec = SECCION_POR_SLUG(seccion);
  if (!sec) return { title: 'Sección no encontrada' };
  return {
    title: `${sec.titulo} · Distribuidora`,
    description: sec.descripcion,
  };
}

export default async function SeccionPage({
  params,
}: {
  params: Promise<{ seccion: string }>;
}) {
  const { seccion } = await params;
  const sec = SECCION_POR_SLUG(seccion);
  if (!sec) notFound();

  const productos = porSeccion(sec.id as Seccion);
  const otras = SECCIONES.filter((s) => s.slug !== sec.slug);

  return (
    <>
      <section className="t-sechero">
        <div className="t-wrap">
          <nav className="t-crumb" aria-label="Ruta">
            <Link href="/tienda">Inicio</Link>
            <span>/</span>
            <span aria-current="page">{sec.nombre}</span>
          </nav>

          <div className="t-sechero__grid">
            <div>
              <h1>{sec.titulo}</h1>
              <p>{sec.descripcionLarga}</p>
            </div>
            <div className="t-sechero__meta">
              <div>
                <b>{sec.conteo}</b>
                <span>productos en catálogo</span>
              </div>
              <div>
                <b>24–48 h</b>
                <span>tiempo de entrega</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="t-section t-section--tight">
        <div className="t-wrap">
          <Catalogo productos={productos} mostrarFiltros={false} />

          <div className="t-otras">
            <h2>Las otras secciones</h2>
            <div className="t-otras__row">
              {otras.map((s) => (
                <Link key={s.id} href={`/tienda/${s.slug}`} className="t-otras__item">
                  <strong>{s.nombre}</strong>
                  <span>{s.descripcion}</span>
                  <em>Ver sección →</em>
                </Link>
              ))}
              <Link href="/tienda" className="t-otras__item">
                <strong>Volver al inicio</strong>
                <span>Portada con lo más vendido y el seguimiento de entregas.</span>
                <em>Ver portada →</em>
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
