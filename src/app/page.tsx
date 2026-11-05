import Link from "next/link";
import {
  ArrowRight,
  BadgeCheck,
  Boxes,
  Check,
  Clock,
  FileText,
  Package,
  ShieldCheck,
  Sparkles,
  Store,
  Truck,
  Wrench,
} from "lucide-react";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";
import { Badge, ButtonLink, money } from "@/components/ui";

const ROLES = [
  {
    id: "comercios",
    icon: Store,
    title: "Comercio",
    desc: "Compra materia prima e insumos a mayoristas verificados, con precio por volumen y un solo pago aunque el pedido venga de varios proveedores.",
    cta: "Crear cuenta de comercio",
    href: "/registro?rol=COMERCIO",
  },
  {
    id: "mayoristas",
    icon: Boxes,
    title: "Mayorista",
    desc: "Publica tu catálogo con escalas de precio, recibe pedidos ordenados y cobra por la pasarela sin perseguir a nadie.",
    cta: "Crear cuenta de mayorista",
    href: "/registro?rol=MAYORISTA",
  },
  {
    id: "repartidores",
    icon: Truck,
    title: "Repartidor",
    desc: "Toma viajes con tarifa y ruta visibles antes de aceptar, confirma recolección y entrega con PIN y acumula reputación.",
    cta: "Crear cuenta de repartidor",
    href: "/registro?rol=REPARTIDOR",
  },
];

const PASOS = [
  {
    n: "1",
    title: "Arma tu pedido",
    desc: "Busca por categoría, compara precios de mayoreo y agrega por caja, bulto o pieza. El carrito agrupa solo lo que cada proveedor surte.",
  },
  {
    n: "2",
    title: "El mayorista acepta",
    desc: "Recibe la solicitud, confirma existencia y prepara el pedido. Tú ves el avance sin tener que llamar a preguntar.",
  },
  {
    n: "3",
    title: "Sigue la entrega",
    desc: "Se asigna un repartidor verificado, ves la tarifa y el estado del viaje hasta que llega a tu puerta.",
  },
];

const PRODUCTOS = [
  { name: "Aceite vegetal 1 L", cat: "Abarrotes", pres: "Caja con 12 piezas", price: 438, icon: Package },
  { name: "Azúcar estándar 1 kg", cat: "Abarrotes", pres: "Bulto con 10 piezas", price: 285, icon: Package },
  { name: "Café soluble 200 g", cat: "Abarrotes", pres: "Caja con 12 piezas", price: 1020, icon: Package },
  { name: "Cloro 1 L", cat: "Limpieza", pres: "Caja con 12 piezas", price: 186, icon: Sparkles },
  { name: 'Tornillo pija 1" punta broca', cat: "Herrería", pres: "Caja con 100 piezas", price: 78, icon: Wrench },
  { name: "Martillo de uña 16 oz", cat: "Herrería", pres: "Pieza", price: 165, icon: Wrench },
];

const CONFIANZA = [
  { icon: Clock, title: "Entrega 24 a 48 h", desc: "CDMX y zona metropolitana" },
  { icon: Boxes, title: "Precios de mayoreo", desc: "Mientras más piezas, mejor precio" },
  { icon: FileText, title: "Factura y remisión", desc: "Todos los pedidos facturados" },
  { icon: ShieldCheck, title: "Cuentas verificadas", desc: "KYC antes de operar" },
];

export default function HomePage() {
  return (
    <div className="min-h-screen bg-white">
      <SiteHeader />

      {/* hero */}
      <section className="border-b border-line bg-surface">
        <div className="wrap grid items-center gap-12 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:py-20">
          <div>
            <span className="eyebrow">
              <BadgeCheck size={13} strokeWidth={2.2} />
              Mayoreo para tiendas, talleres y cocinas
            </span>

            <h1 className="mt-5 text-[40px] font-semibold leading-[1.08] tracking-[-0.02em] text-ink sm:text-[52px]">
              Surtes tu negocio,
              <br />
              sin perseguir <em className="not-italic text-brand">proveedores</em>.
            </h1>

            <p className="mt-5 max-w-xl text-[15.5px] leading-relaxed text-ink-2">
              Un solo lugar para abarrotes, limpieza y herrería. Pides en línea, el mayorista
              confirma, se asigna un repartidor y sigues el pedido hasta tu puerta.
            </p>

            <div className="mt-7 flex flex-wrap gap-3">
              <ButtonLink href="/registro" size="lg">
                Crear cuenta
                <ArrowRight size={16} strokeWidth={2.2} />
              </ButtonLink>
              <ButtonLink href="#como-funciona" variant="ghost" size="lg">
                Ver cómo funciona
              </ButtonLink>
            </div>

            <div className="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-[12.5px] text-muted">
              <span className="inline-flex items-center gap-1.5">
                <Check size={14} strokeWidth={2.4} className="text-brand" /> Pedido mínimo $500
              </span>
              <span className="inline-flex items-center gap-1.5">
                <Check size={14} strokeWidth={2.4} className="text-brand" /> Factura incluida
              </span>
              <span className="inline-flex items-center gap-1.5">
                <Check size={14} strokeWidth={2.4} className="text-brand" /> Repartidor verificado
              </span>
            </div>
          </div>

          {/* tarjeta de seguimiento */}
          <div className="card overflow-hidden shadow-[0_24px_60px_-40px_rgba(15,18,16,0.45)]">
            <div className="flex items-center justify-between border-b border-line px-4 py-3">
              <span className="text-[12.5px] font-semibold text-ink">Seguimiento de pedido</span>
              <span className="rounded-full border border-line bg-surface px-2 py-0.5 text-[11px] font-semibold text-muted">
                PED-000128
              </span>
            </div>

            <div className="bg-[#eef1ea]">
              <svg viewBox="0 0 480 300" className="h-[240px] w-full" role="img" aria-label="Mapa de seguimiento del repartidor">
                <rect width="480" height="300" fill="#eef1ea" />
                <g fill="#e3e7de">
                  <rect x="18" y="22" width="118" height="66" rx="6" />
                  <rect x="160" y="22" width="96" height="66" rx="6" />
                  <rect x="280" y="22" width="182" height="66" rx="6" />
                  <rect x="18" y="112" width="118" height="76" rx="6" />
                  <rect x="280" y="112" width="86" height="76" rx="6" />
                  <rect x="390" y="112" width="72" height="76" rx="6" />
                  <rect x="18" y="212" width="96" height="70" rx="6" />
                  <rect x="140" y="212" width="130" height="70" rx="6" />
                  <rect x="296" y="212" width="166" height="70" rx="6" />
                </g>
                <g fill="#dbe8d8">
                  <rect x="160" y="112" width="96" height="76" rx="8" />
                  <circle cx="188" cy="136" r="8" fill="#c7dcbe" />
                  <circle cx="222" cy="162" r="6" fill="#c7dcbe" />
                </g>
                <g stroke="#ffffff" strokeWidth="10" strokeLinecap="round">
                  <path d="M148 10v280" />
                  <path d="M270 10v280" />
                  <path d="M378 10v280" />
                  <path d="M10 100h460" />
                  <path d="M10 200h460" />
                </g>
                <path
                  d="M60 250 L60 200 L148 200 L148 100 L270 100 L270 50 L392 50 L392 128"
                  fill="none"
                  stroke="#0e5134"
                  strokeWidth="5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  opacity="0.9"
                />
                <circle cx="60" cy="250" r="9" fill="#0e5134" opacity="0.18" />
                <circle cx="60" cy="250" r="4.5" fill="#0e5134" />
                <g transform="translate(392 128)">
                  <path d="M0 0c-8 0-14 6.2-14 13.8C-14 24 0 36 0 36s14-12 14-22.2C14 6.2 8 0 0 0z" fill="#b07815" />
                  <circle cx="0" cy="13" r="5" fill="#fff" />
                </g>
                <g transform="translate(270 74)">
                  <circle r="16" fill="#0e5134" opacity="0.14" />
                  <circle r="10" fill="#0e5134" />
                  <path d="M-3.4 -3.2h3.2l1.6 3.2h-4.6z" fill="#fff" />
                  <circle cx="-2" cy="2.4" r="1.5" fill="#fff" />
                  <circle cx="2" cy="2.4" r="1.5" fill="#fff" />
                </g>
              </svg>
            </div>

            <div className="flex items-center gap-3 px-4 py-3.5">
              <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-soft text-brand">
                <Truck size={17} strokeWidth={1.9} />
              </span>
              <div className="min-w-0 flex-1">
                <p className="text-[13px] font-semibold text-ink">En camino</p>
                <p className="truncate text-[12px] text-muted">Repartidor: Luis M. · Unidad 12</p>
              </div>
              <Badge tone="brand">8 min</Badge>
            </div>
          </div>
        </div>
      </section>

      {/* roles */}
      <section className="wrap py-16">
        <div className="flex flex-wrap items-end justify-between gap-4">
          <div>
            <h2 className="text-[28px] font-semibold tracking-[-0.01em] text-ink">
              Tres cuentas, un mismo pedido
            </h2>
            <p className="mt-2 max-w-2xl text-[14px] text-muted">
              Cada tipo de usuario entra a su propio panel con las herramientas que necesita.
              El comercio compra, el mayorista surte y el repartidor entrega.
            </p>
          </div>
          <Link href="/registro" className="text-[13px] font-semibold text-brand hover:underline">
            Elegir mi tipo de cuenta
          </Link>
        </div>

        <div className="mt-8 grid gap-4 md:grid-cols-3">
          {ROLES.map((rol) => {
            const Icon = rol.icon;
            return (
              <article key={rol.id} id={rol.id} className="card flex flex-col p-6 transition hover:border-line-2">
                <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-soft text-brand">
                  <Icon size={21} strokeWidth={1.9} />
                </span>
                <h3 className="mt-4 text-[17px] font-semibold text-ink">{rol.title}</h3>
                <p className="mt-2 flex-1 text-[13.5px] leading-relaxed text-muted">{rol.desc}</p>
                <Link
                  href={rol.href}
                  className="mt-5 inline-flex items-center gap-1.5 text-[13px] font-semibold text-brand hover:gap-2.5 transition-all"
                >
                  {rol.cta}
                  <ArrowRight size={14} strokeWidth={2.2} />
                </Link>
              </article>
            );
          })}
        </div>
      </section>

      {/* cómo funciona */}
      <section id="como-funciona" className="border-y border-line bg-surface py-16">
        <div className="wrap">
          <h2 className="text-[28px] font-semibold tracking-[-0.01em] text-ink">
            Pedir toma menos de cinco minutos
          </h2>
          <p className="mt-2 max-w-2xl text-[14px] text-muted">
            Sin cotizaciones por WhatsApp a las ocho de la noche ni llamadas para saber si ya salió
            el pedido.
          </p>

          <div className="mt-8 grid gap-4 md:grid-cols-3">
            {PASOS.map((paso) => (
              <div key={paso.n} className="card p-6">
                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-brand text-[13px] font-semibold text-white">
                  {paso.n}
                </span>
                <h3 className="mt-4 text-[15.5px] font-semibold text-ink">{paso.title}</h3>
                <p className="mt-2 text-[13.5px] leading-relaxed text-muted">{paso.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* catálogo */}
      <section className="wrap py-16">
        <div className="flex flex-wrap items-end justify-between gap-4">
          <div>
            <h2 className="text-[28px] font-semibold tracking-[-0.01em] text-ink">
              Lo que más se mueve esta semana
            </h2>
            <p className="mt-2 text-[14px] text-muted">
              Precios de mayoreo por caja o bulto. La existencia se actualiza cuando el mayorista surte.
            </p>
          </div>
          <ButtonLink href="/login" variant="ghost" size="sm">
            Ver catálogo completo
          </ButtonLink>
        </div>

        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {PRODUCTOS.map((p) => {
            const Icon = p.icon;
            return (
              <article key={p.name} className="card overflow-hidden">
                <div className="flex h-[132px] items-center justify-center border-b border-line bg-surface">
                  <Icon size={44} strokeWidth={1.4} className="text-[#9aa89f]" />
                </div>
                <div className="p-4">
                  <span className="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted">
                    {p.cat}
                  </span>
                  <h3 className="mt-1 text-[14.5px] font-semibold leading-snug text-ink">{p.name}</h3>
                  <p className="mt-0.5 text-[12px] text-muted">{p.pres}</p>
                  <div className="mt-3 flex items-baseline gap-1.5">
                    <b className="text-[17px] font-semibold text-ink">{money(p.price)}</b>
                    <small className="text-[11.5px] text-muted">por caja</small>
                  </div>
                </div>
              </article>
            );
          })}
        </div>
      </section>

      {/* confianza */}
      <section className="border-y border-line bg-surface py-10">
        <div className="wrap grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {CONFIANZA.map((item) => {
            const Icon = item.icon;
            return (
              <div key={item.title} className="flex items-start gap-3">
                <span className="mt-0.5 text-brand">
                  <Icon size={20} strokeWidth={1.9} />
                </span>
                <div>
                  <p className="text-[13.5px] font-semibold text-ink">{item.title}</p>
                  <p className="text-[12.5px] text-muted">{item.desc}</p>
                </div>
              </div>
            );
          })}
        </div>
      </section>

      {/* cta */}
      <section className="wrap py-16">
        <div className="card flex flex-col items-start justify-between gap-6 bg-brand p-8 text-white sm:flex-row sm:items-center">
          <div>
            <h2 className="text-[24px] font-semibold tracking-[-0.01em]">
              ¿Es tu primera compra con nosotros?
            </h2>
            <p className="mt-2 max-w-xl text-[13.5px] text-[#d7e6dd]">
              Crea tu cuenta, verifica tus datos y empieza a pedir. Si ya tienes cuenta, entra y
              repite tu último pedido.
            </p>
          </div>
          <div className="flex flex-wrap gap-2.5">
            <Link
              href="/registro"
              className="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-[13.5px] font-semibold text-brand transition hover:bg-[#f2f7f4]"
            >
              Crear cuenta
            </Link>
            <Link
              href="/login"
              className="inline-flex items-center gap-2 rounded-full border border-white/30 px-5 py-2.5 text-[13.5px] font-semibold text-white transition hover:bg-white/10"
            >
              Entrar a mi cuenta
            </Link>
          </div>
        </div>
      </section>

      <SiteFooter />
    </div>
  );
}
