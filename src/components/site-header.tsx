import Link from "next/link";
import { Logo } from "@/components/ui";

const NAV = [
  { href: "/#como-funciona", label: "Cómo funciona" },
  { href: "/#comercios", label: "Para comercios" },
  { href: "/#mayoristas", label: "Para mayoristas" },
  { href: "/#repartidores", label: "Repartidores" },
];

export function SiteHeader() {
  return (
    <>
      <div className="bg-brand text-[#eaf1ec]">
        <div className="wrap flex h-[38px] items-center justify-center gap-2.5 text-[12.5px]">
          <span>Cobertura CDMX y zona metropolitana</span>
          <span className="opacity-40">·</span>
          <span>
            Pedido mínimo <b className="font-semibold text-white">$500</b>
          </span>
          <span className="hidden opacity-40 sm:inline">·</span>
          <span className="hidden sm:inline">Factura CFDI 4.0</span>
        </div>
      </div>

      <header className="sticky top-0 z-50 border-b border-line bg-white/90 backdrop-blur-md">
        <div className="wrap flex h-[68px] items-center justify-between gap-6">
          <Link href="/" aria-label="Inicio de Didi2 B2B">
            <Logo />
          </Link>

          <nav className="hidden items-center gap-7 text-[13.5px] font-medium text-ink-2 lg:flex">
            {NAV.map((item) => (
              <Link key={item.href} href={item.href} className="transition hover:text-brand">
                {item.label}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-2">
            <Link
              href="/login"
              className="hidden rounded-full border border-line-2 px-4 py-2 text-[13px] font-semibold text-ink transition hover:border-brand-2 hover:text-brand sm:inline-flex"
            >
              Iniciar sesión
            </Link>
            <Link
              href="/registro"
              className="inline-flex rounded-full bg-brand px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-brand-2"
            >
              Crear cuenta
            </Link>
          </div>
        </div>
      </header>
    </>
  );
}
