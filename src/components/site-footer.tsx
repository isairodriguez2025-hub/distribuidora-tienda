import Link from "next/link";
import { Logo } from "@/components/ui";

export function SiteFooter() {
  return (
    <footer className="border-t border-line bg-surface">
      <div className="wrap py-14">
        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <Logo />
            <p className="mt-4 max-w-xs text-[13px] leading-relaxed text-muted">
              Materia prima, insumos y logística para tiendas, talleres y cocinas. Un solo
              proveedor, un solo pedido, seguimiento hasta la puerta.
            </p>
          </div>

          <div>
            <h4 className="text-[12px] font-semibold uppercase tracking-[0.08em] text-ink">Plataforma</h4>
            <ul className="mt-3 space-y-2 text-[13px] text-muted">
              <li><Link href="/#como-funciona" className="hover:text-brand">Cómo funciona</Link></li>
              <li><Link href="/registro?rol=COMERCIO" className="hover:text-brand">Cuenta de comercio</Link></li>
              <li><Link href="/registro?rol=MAYORISTA" className="hover:text-brand">Cuenta de mayorista</Link></li>
              <li><Link href="/registro?rol=REPARTIDOR" className="hover:text-brand">Cuenta de repartidor</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="text-[12px] font-semibold uppercase tracking-[0.08em] text-ink">Mi cuenta</h4>
            <ul className="mt-3 space-y-2 text-[13px] text-muted">
              <li><Link href="/login" className="hover:text-brand">Iniciar sesión</Link></li>
              <li><Link href="/app/comercio" className="hover:text-brand">Catálogo</Link></li>
              <li><Link href="/app/comercio/pedidos" className="hover:text-brand">Mis pedidos</Link></li>
              <li><Link href="/app/repartidor" className="hover:text-brand">Viajes</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="text-[12px] font-semibold uppercase tracking-[0.08em] text-ink">Contacto</h4>
            <ul className="mt-3 space-y-2 text-[13px] text-muted">
              <li>WhatsApp 55 1234 5678</li>
              <li>ventas@didi2.mx</li>
              <li>Lunes a viernes · 9:00 a 18:00</li>
              <li>Sábado · 9:00 a 14:00</li>
            </ul>
          </div>
        </div>

        <div className="mt-12 flex flex-col gap-2 border-t border-line pt-6 text-[12px] text-muted sm:flex-row sm:items-center sm:justify-between">
          <span>© 2026 Didi2 B2B. Todos los derechos reservados.</span>
          <span>Aviso de privacidad · Términos · Devoluciones</span>
        </div>
      </div>
    </footer>
  );
}
