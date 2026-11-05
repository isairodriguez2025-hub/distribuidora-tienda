"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { logout } from "@/lib/auth-client";
import { useState } from "react";
import {
  BarChart3,
  Boxes,
  ClipboardList,
  LayoutDashboard,
  LogOut,
  Menu,
  Package,
  ShoppingCart,
  Store,
  Truck,
  Users,
  X,
} from "lucide-react";
import { Logo } from "@/components/ui";
import { ROLE_META, type Role } from "@/lib/roles";

type NavItem = { href: string; label: string; icon: React.ElementType };

const NAV: Record<Role, NavItem[]> = {
  COMERCIO: [
    { href: "/app/comercio", label: "Catálogo", icon: Store },
    { href: "/app/comercio/carrito", label: "Carrito", icon: ShoppingCart },
    { href: "/app/comercio/pedidos", label: "Mis pedidos", icon: ClipboardList },
  ],
  MAYORISTA: [
    { href: "/app/mayorista", label: "Resumen", icon: LayoutDashboard },
    { href: "/app/mayorista/productos", label: "Productos", icon: Boxes },
    { href: "/app/mayorista/pedidos", label: "Pedidos", icon: ClipboardList },
  ],
  REPARTIDOR: [
    { href: "/app/repartidor", label: "Viajes disponibles", icon: Truck },
    { href: "/app/repartidor/entregas", label: "Mis entregas", icon: Package },
  ],
  ADMIN: [
    { href: "/app/admin", label: "Resumen", icon: BarChart3 },
    { href: "/app/admin/usuarios", label: "Usuarios y KYC", icon: Users },
    { href: "/app/admin/pedidos", label: "Pedidos", icon: ClipboardList },
  ],
};

export function AppShell({
  role,
  name,
  businessName,
  children,
}: {
  role: Role;
  name: string;
  businessName: string;
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const items = NAV[role] ?? [];
  const meta = ROLE_META[role];

  const nav = (
    <nav className="flex flex-col gap-1">
      {items.map((item) => {
        const active = pathname === item.href;
        const Icon = item.icon;
        return (
          <Link
            key={item.href}
            href={item.href}
            onClick={() => setOpen(false)}
            className={`flex items-center gap-2.5 rounded-[10px] px-3 py-2.5 text-[13.5px] font-medium transition ${
              active
                ? "bg-brand-soft text-brand"
                : "text-ink-2 hover:bg-surface-2 hover:text-ink"
            }`}
          >
            <Icon size={17} strokeWidth={1.9} />
            {item.label}
          </Link>
        );
      })}
    </nav>
  );

  return (
    <div className="min-h-screen bg-surface">
      {/* barra móvil */}
      <div className="sticky top-0 z-40 flex items-center justify-between border-b border-line bg-white px-4 py-3 lg:hidden">
        <Logo />
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          aria-label="Abrir menú"
          className="rounded-lg border border-line p-2 text-ink-2"
        >
          {open ? <X size={18} /> : <Menu size={18} />}
        </button>
      </div>

      <div className="mx-auto flex w-full max-w-[1400px]">
        {/* sidebar */}
        <aside
          className={`${
            open ? "block" : "hidden"
          } w-full shrink-0 border-r border-line bg-white p-4 lg:block lg:w-[248px] lg:min-h-screen`}
        >
          <div className="hidden lg:block">
            <Link href="/">
              <Logo />
            </Link>
          </div>

          <div className="mt-6 rounded-xl border border-line bg-surface p-3">
            <p className="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted">
              {meta.label}
            </p>
            <p className="mt-1 truncate text-[13.5px] font-semibold text-ink">{businessName || name}</p>
            <p className="truncate text-[12px] text-muted">{name}</p>
          </div>

          <div className="mt-5">{nav}</div>

          <div className="mt-6 border-t border-line pt-4">
            <button
              type="button"
              onClick={() => void logout()}
              className="flex w-full items-center gap-2.5 rounded-[10px] px-3 py-2.5 text-[13.5px] font-medium text-ink-2 transition hover:bg-surface-2 hover:text-ink"
            >
              <LogOut size={17} strokeWidth={1.9} />
              Cerrar sesión
            </button>
          </div>
        </aside>

        <main className="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">{children}</main>
      </div>
    </div>
  );
}
