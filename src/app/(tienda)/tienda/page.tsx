import type { Metadata } from 'next';
import Link from 'next/link';

import { Catalogo } from '@/components/tienda/Catalogo';
import { SECCIONES } from '@/lib/tienda-demo';

export const metadata: Metadata = {
  title: 'Distribuidora · Abarrotes, limpieza y herrería al mayoreo',
  description:
    'Surtido de abarrotes, productos de limpieza y herrería para tiendas y talleres. Precios de mayoreo, entrega en 24 a 48 horas y seguimiento del repartidor en el mapa.',
};

/* --------------------------- iconos de sección --------------------------- */
const ICONO: Record<string, React.ReactNode> = {
  abarrotes: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
      <path d="M5 8h14l-1.3 11a2 2 0 0 1-2 1.8H8.3a2 2 0 0 1-2-1.8z" />
      <path d="M9 8V6a3 3 0 0 1 6 0v2" />
    </svg>
  ),
  limpieza: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
      <path d="M9 9h6v10a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z" />
      <path d="M8.5 9h7M12 9V5h3.5" />
    </svg>
  ),
  herreria: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
      <path d="M13.5 6 6 13.5l4.5 4.5L18 10.5z" />
      <path d="M13.5 6 15 4.5l4.5 4.5L18 10.5" />
    </svg>
  ),
};

/* ------------------------- ilustración del mapa -------------------------- */
function MapaDemo() {
  return (
    <svg viewBox="0 0 480 360" role="img" aria-label="Vista previa del mapa de seguimiento del repartidor">
      <rect width="480" height="360" fill="#eef1ea" />

      {/* manzanas */}
      <g fill="#e3e7de">
        <rect x="18" y="26" width="118" height="76" rx="6" />
        <rect x="160" y="26" width="96" height="76" rx="6" />
        <rect x="280" y="26" width="182" height="76" rx="6" />
        <rect x="18" y="130" width="118" height="90" rx="6" />
        <rect x="280" y="130" width="86" height="90" rx="6" />
        <rect x="390" y="130" width="72" height="90" rx="6" />
        <rect x="18" y="248" width="96" height="86" rx="6" />
        <rect x="140" y="248" width="130" height="86" rx="6" />
        <rect x="296" y="248" width="166" height="86" rx="6" />
      </g>

      {/* parque */}
      <g>
        <rect x="160" y="130" width="96" height="90" rx="8" fill="#dbe8d8" />
        <circle cx="188" cy="158" r="9" fill="#c7dcbe" />
        <circle cx="222" cy="186" r="7" fill="#c7dcbe" />
      </g>

      {/* avenidas */}
      <g stroke="#ffffff" strokeWidth="10" strokeLinecap="round">
        <path d="M148 12v336" />
        <path d="M270 12v336" />
        <path d="M378 12v336" />
        <path d="M12 118h456" />
        <path d="M12 238h456" />
      </g>

      {/* ruta del repartidor */}
      <path
        d="M60 300 L60 238 L148 238 L148 118 L270 118 L270 60 L392 60 L392 150"
        fill="none"
        stroke="#0e5134"
        strokeWidth="5"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeDasharray="1 0"
        opacity="0.9"
      />

      {/* origen */}
      <circle cx="60" cy="300" r="9" fill="#0e5134" opacity="0.18" />
      <circle cx="60" cy="300" r="4.5" fill="#0e5134" />

      {/* destino */}
      <g transform="translate(392 150)">
        <path d="M0 0c-8 0-14 6.2-14 13.8C-14 24 0 36 0 36s14-12 14-22.2C14 6.2 8 0 0 0z" fill="#b07815" />
        <circle cx="0" cy="13" r="5" fill="#fff" />
      </g>

      {/* repartidor */}
      <g transform="translate(270 88)">
        <circle r="16" fill="#0e5134" opacity="0.14" />
        <circle r="10" fill="#0e5134" />
        <path d="M-3.4 -3.2h3.2l1.6 3.2h-4.6z" fill="#fff" />
        <circle cx="-2" cy="2.4" r="1.5" fill="#fff" />
        <circle cx="2" cy="2.4" r="1.5" fill="#fff" />
      </g>
    </svg>
  );
}

export default function TiendaPage() {
  return (
    <>
      {/* ------------------------------- HERO ------------------------------- */}
      <section className="t-hero">
        <div className="t-wrap">
          <div className="t-hero__grid">
            <div>
              <span className="t-eyebrow">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round">
                  <path d="M4 12h16M12 4v16" />
                </svg>
                Mayoreo para tiendas y talleres
              </span>

              <h1>
                Surtimos tu tienda, tu limpieza y tu <em>taller</em>.
              </h1>

              <p className="t-hero__sub">
                Un solo proveedor para las tres cosas que más te urgen: abarrotes,
                limpieza y herrería. Pides en línea, preparamos tu pedido y sigues
                al repartidor en el mapa hasta tu puerta.
              </p>

              <div className="t-hero__cta">
                <Link href="/tienda/catalogo" className="t-btn t-btn--primary">
                  Ver catálogo
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                  </svg>
                </Link>
                <a href="https://wa.me/525512345678" className="t-btn t-btn--ghost">
                  Pedir por WhatsApp
                </a>
              </div>

              <div className="t-hero__meta">
                <span>
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="8.5" />
                    <path d="M12 7.5V12l3 1.8" />
                  </svg>
                  Entrega en 24 a 48 h
                </span>
                <span>
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="m4 12.5 5 5L20 6.5" />
                  </svg>
                  Pedido mínimo $500
                </span>
                <span>
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M4 7h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                    <path d="M8 7V5h8v2" />
                  </svg>
                  Factura incluida
                </span>
              </div>
            </div>

            {/* tarjeta de seguimiento */}
            <div className="t-track">
              <div className="t-track__head">
                <span className="t-track__title">Seguimiento de entrega</span>
                <span className="t-track__folio">PED-000128</span>
              </div>

              <div className="t-map">
                <MapaDemo />
              </div>

              <div className="t-track__foot">
                <span className="t-pulse">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M3 7h11v9H3z" />
                    <path d="M14 10h4l3 3v3h-7z" />
                    <circle cx="7" cy="18" r="1.7" />
                    <circle cx="17.5" cy="18" r="1.7" />
                  </svg>
                </span>
                <div>
                  <strong>En camino</strong>
                  <span>Repartidor: Luis M. · Unidad 12</span>
                </div>
                <span className="t-track__pill">8 min</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ----------------------------- CATEGORÍAS ---------------------------- */}
      <section className="t-section" id="secciones">
        <div className="t-wrap">
          <div className="t-sechead">
            <div>
              <h2>Tres secciones, un mismo pedido</h2>
              <p>
                Puedes combinar abarrotes, limpieza y herrería en la misma compra:
                llega todo junto y pagas un solo envío.
              </p>
            </div>
            <Link href="/tienda/catalogo" className="t-sechead__link">
              Ver todo el catálogo
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M5 12h14M13 6l6 6-6 6" />
              </svg>
            </Link>
          </div>

          <div className="t-cats">
            {SECCIONES.map((s) => (
              <Link key={s.id} href={`/tienda/${s.slug}`} className="t-cat">
                <span className="t-cat__ico">{ICONO[s.id]}</span>
                <h3>{s.nombre}</h3>
                <p>{s.descripcion}</p>
                <div className="t-cat__foot">
                  <span>{s.conteo} productos</span>
                  <span className="t-cat__go">
                    Ver sección
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                  </span>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ----------------------------- DESTACADOS ---------------------------- */}
      <section className="t-section t-section--tight" style={{ background: '#fbfaf7', borderTop: '1px solid var(--t-line)' }}>
        <div className="t-wrap">
          <div className="t-sechead">
            <div>
              <h2>Lo que más se mueve esta semana</h2>
              <p>Precios de mayoreo por caja o bulto. La existencia se actualiza cada vez que alguien surte.</p>
            </div>
          </div>
          <Catalogo />
        </div>
      </section>

      {/* --------------------------- CÓMO FUNCIONA -------------------------- */}
      <section className="t-section t-how">
        <div className="t-wrap">
          <div className="t-sechead">
            <div>
              <h2>Pedir toma menos de cinco minutos</h2>
              <p>Sin llamadas, sin cotizaciones por WhatsApp a las 8 de la noche.</p>
            </div>
          </div>

          <div className="t-steps">
            <div className="t-step">
              <span className="t-step__n">1</span>
              <h3>Arma tu pedido</h3>
              <p>
                Entra a la sección que necesites, agrega por caja, bulto o pieza y
                revisa el total antes de confirmar.
              </p>
            </div>
            <div className="t-step">
              <span className="t-step__n">2</span>
              <h3>Nosotros lo preparamos</h3>
              <p>
                Verificamos existencia, te avisamos si algo se agotó y programamos
                la entrega en el horario que te acomoda.
              </p>
            </div>
            <div className="t-step">
              <span className="t-step__n">3</span>
              <h3>Sigue a tu repartidor</h3>
              <p>
                Cuando el pedido sale, ves el avance en el mapa y la hora estimada
                de llegada. Sin estar preguntando.
              </p>
            </div>
          </div>

          <div className="t-trust">
            <div className="t-trust__item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M3 7h11v9H3z" />
                <path d="M14 10h4l3 3v3h-7z" />
                <circle cx="7" cy="18" r="1.7" />
                <circle cx="17.5" cy="18" r="1.7" />
              </svg>
              <div>
                <strong>Entrega 24 a 48 h</strong>
                <span>CDMX y zona metropolitana</span>
              </div>
            </div>
            <div className="t-trust__item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M4 7h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                <path d="M8 7V5h8v2M4 12h16" />
              </svg>
              <div>
                <strong>Precios de mayoreo</strong>
                <span>Mientras más piezas, mejor precio</span>
              </div>
            </div>
            <div className="t-trust__item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M4 5h16v14H4z" />
                <path d="M8 9h8M8 13h5" />
              </svg>
              <div>
                <strong>Factura y remisión</strong>
                <span>Todos los pedidos facturados</span>
              </div>
            </div>
            <div className="t-trust__item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M12 3 4 6v6c0 4.6 3.4 8.3 8 9 4.6-.7 8-4.4 8-9V6z" />
                <path d="m8.5 12 2.5 2.5 4.5-5" />
              </svg>
              <div>
                <strong>Producto verificado</strong>
                <span>Cambios sin problema</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* -------------------------------- CTA ------------------------------- */}
      <section className="t-section">
        <div className="t-wrap">
          <div className="t-cta">
            <div>
              <h2>¿Es tu primera compra con nosotros?</h2>
              <p>
                Mándanos tu lista por WhatsApp y te cotizamos el mismo día. Si ya
                eres cliente, entra a tu cuenta y repite tu último pedido.
              </p>
            </div>
            <div className="t-cta__actions">
              <a href="https://wa.me/525512345678" className="t-btn t-btn--primary">
                Cotizar por WhatsApp
              </a>
              <Link href="/login" className="t-btn t-btn--ghost">
                Entrar a mi cuenta
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
