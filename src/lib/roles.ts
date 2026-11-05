export const ROLES = ["COMERCIO", "MAYORISTA", "REPARTIDOR", "ADMIN"] as const;
export type Role = (typeof ROLES)[number];

export const ROLE_META: Record<
  Role,
  { label: string; short: string; home: string; desc: string }
> = {
  COMERCIO: {
    label: "Comercio",
    short: "Comprador",
    home: "/app/comercio",
    desc: "Compra materia prima e insumos a mayoristas verificados.",
  },
  MAYORISTA: {
    label: "Mayorista",
    short: "Proveedor",
    home: "/app/mayorista",
    desc: "Publica tu catálogo, recibe pedidos y surte a tus clientes.",
  },
  REPARTIDOR: {
    label: "Repartidor",
    short: "Logística",
    home: "/app/repartidor",
    desc: "Acepta viajes, recoge y entrega con tarifa visible.",
  },
  ADMIN: {
    label: "Administración",
    short: "Admin",
    home: "/app/admin",
    desc: "Verifica cuentas, supervisa pedidos y revisa métricas.",
  },
};

export function isRole(value: string): value is Role {
  return (ROLES as readonly string[]).includes(value);
}

/** Antepone el basePath configurado (por ejemplo /didi2) a una ruta interna. */
export function withBase(path: string): string {
  const base = process.env.NEXT_PUBLIC_BASE_PATH ?? "";
  return `${base}${path}`;
}

export function homeFor(role: string): string {
  return isRole(role) ? ROLE_META[role].home : "/login";
}
