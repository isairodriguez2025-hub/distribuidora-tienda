'use client';

/**
 * Shell del sitio público (tienda).
 * Mantiene el estado del carrito y el aviso flotante, y dibuja la estructura
 * común: barra de avisos, header pegajoso, barra de secciones en móvil,
 * footer y toast. El contenido de cada página entra por `children`.
 *
 * Nota: el POS vive en su propio layout oscuro; este shell solo se usa dentro
 * de la ruta /tienda para no mezclar los dos sistemas de diseño.
 */

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';

import { SECCIONES } from '@/lib/tienda-demo';

type TiendaCtx = {
  items: number;
  agregar: (nombre: string) => void;
};

const Ctx = createContext<TiendaCtx>({ items: 0, agregar: () => {} });

export function useTienda() {
  return useContext(Ctx);
}

/* --------------------------------- iconos -------------------------------- */
const Ico = {
  search: (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
      <circle cx="11" cy="11" r="7" />
      <path d="m20 20-3.2-3.2" />
    </svg>
  ),
  cart: (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="9" cy="20" r="1.5" />
      <circle cx="18" cy="20" r="1.5" />
      <path d="M2 3h2.2l2.3 12.2a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L21 7H6" />
    </svg>
  ),
  user: (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
      <circle cx="12" cy="8" r="3.4" />
      <path d="M4.5 20a7.5 7.5 0 0 1 15 0" />
    </svg>
  ),
  box: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
      <path d="M3 8.5 12 3l9 5.5v7L12 21l-9-5.5z" />
      <path d="M3 8.5 12 14l9-5.5M12 14v7" />
    </svg>
  ),
  check: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
      <path d="m4 12.5 5 5L20 6.5" />
    </svg>
  ),
};

/* --------------------------------- logo ---------------------------------- */
function Logo({ light = false }: { light?: boolean }) {
  return (
    <Link href="/tienda" className="t-logo" aria-label="Ir al inicio de la tienda">
      <span className="t-logo__mark" style={light ? { background: '#1b5c3f' } : undefined}>
        {Ico.box}
      </span>
      <span>
        <span className="t-logo__name">Distribuidora</span>
        <span className="t-logo__tag">Materia prima y abasto</span>
      </span>
    </Link>
  );
}

/* -------------------------------- header --------------------------------- */
function Header() {
  const pathname = usePathname();
  const { items } = useTienda();
  const [buscar, setBuscar] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (buscar) inputRef.current?.focus();
  }, [buscar]);

  return (
    <header className="t-header">
      <div className="t-wrap">
        <div className="t-header__row">
          <Logo />

          <nav className="t-nav" aria-label="Secciones del catálogo">
            {SECCIONES.map((s) => (
              <Link
                key={s.id}
                href={`/tienda/${s.slug}`}
                className={pathname.startsWith(`/tienda/${s.slug}`) ? 'is-active' : undefined}
              >
                {s.nombre}
              </Link>
            ))}
          </nav>

          <div className="t-header__actions">
            {buscar ? (
              <label className="t-search t-hide-mobile">
                {Ico.search}
                <input
                  ref={inputRef}
                  type="search"
                  placeholder="Buscar producto o código…"
                  aria-label="Buscar producto"
                  onBlur={() => setBuscar(false)}
                />
              </label>
            ) : (
              <button
                type="button"
                className="t-iconbtn t-hide-mobile"
                aria-label="Buscar"
                onClick={() => setBuscar(true)}
              >
                {Ico.search}
              </button>
            )}

            <Link href="/login" className="t-iconbtn t-hide-mobile" aria-label="Mi cuenta">
              {Ico.user}
            </Link>

            <Link href="/tienda/carrito" className="t-iconbtn" aria-label={`Carrito, ${items} artículos`}>
              {Ico.cart}
              {items > 0 && <span className="t-badge">{items}</span>}
            </Link>

            <Link href="/tienda/catalogo" className="t-btn t-btn--primary t-btn--sm t-hide-mobile">
              Ver catálogo
            </Link>
          </div>
        </div>
      </div>

      {/* Las 3 secciones siempre visibles en móvil (no hamburguesa) */}
      <div className="t-mobile-cats" aria-label="Secciones">
        {SECCIONES.map((s) => (
          <Link key={s.id} href={`/tienda/${s.slug}`} className={pathname.startsWith(`/tienda/${s.slug}`) ? 'is-active' : undefined}>
            {s.nombre}
          </Link>
        ))}
      </div>
    </header>
  );
}

/* --------------------------------- footer -------------------------------- */
function Footer() {
  return (
    <footer className="t-footer">
      <div className="t-wrap">
        <div className="t-footer__grid">
          <div className="t-footer__brand">
            <Logo light />
            <p>
              Abasto de abarrotes, limpieza y herrería para tiendas y talleres.
              Precios de mayoreo, entrega programada y seguimiento de tu pedido.
            </p>
          </div>

          <div>
            <h4>Catálogo</h4>
            <ul>
              {SECCIONES.map((s) => (
                <li key={s.id}>
                  <Link href={`/tienda/${s.slug}`}>{s.nombre}</Link>
                </li>
              ))}
              <li>
                <Link href="/tienda/catalogo">Todos los productos</Link>
              </li>
            </ul>
          </div>

          <div>
            <h4>Mi cuenta</h4>
            <ul>
              <li><Link href="/login">Iniciar sesión</Link></li>
              <li><Link href="/tienda/carrito">Mi carrito</Link></li>
              <li><Link href="/tienda/pedidos">Mis pedidos</Link></li>
              <li><Link href="/tienda/seguimiento">Rastrear entrega</Link></li>
            </ul>
          </div>

          <div>
            <h4>Contacto</h4>
            <ul>
              <li>WhatsApp 55 1234 5678</li>
              <li>ventas@distribuidora.mx</li>
              <li>Lun a vie · 9:00 a 18:00</li>
              <li>Sábado · 9:00 a 14:00</li>
            </ul>
          </div>
        </div>

        <div className="t-footer__bottom">
          <span>© {new Date().getFullYear()} Distribuidora. Todos los derechos reservados.</span>
          <span>Aviso de privacidad · Términos · Devoluciones</span>
        </div>
      </div>
    </footer>
  );
}

/* --------------------------------- shell --------------------------------- */
export function TiendaShell({ children }: { children: ReactNode }) {
  const [items, setItems] = useState(0);
  const [toast, setToast] = useState<string | null>(null);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const agregar = useCallback((nombre: string) => {
    setItems((n) => n + 1);
    setToast(`Agregado al carrito: ${nombre}`);
    if (timer.current) clearTimeout(timer.current);
    timer.current = setTimeout(() => setToast(null), 2400);
  }, []);

  useEffect(() => () => {
    if (timer.current) clearTimeout(timer.current);
  }, []);

  const value = useMemo(() => ({ items, agregar }), [items, agregar]);

  return (
    <Ctx.Provider value={value}>
      <div className="t-root">
        <div className="t-topbar">
          <div className="t-wrap">
            <span>Entregas en CDMX y zona metropolitana</span>
            <span className="t-sep">·</span>
            <span>Pedido mínimo <b>$500</b></span>
            <span className="t-sep">·</span>
            <span>Factura incluida</span>
          </div>
        </div>

        <Header />
        {children}
        <Footer />

        <div className={`t-toast${toast ? ' is-on' : ''}`} role="status" aria-live="polite">
          {toast && (
            <>
              {Ico.check}
              <span>{toast}</span>
            </>
          )}
        </div>
      </div>
    </Ctx.Provider>
  );
}
