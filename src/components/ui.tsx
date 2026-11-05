import Link from "next/link";
import type { ComponentProps, ReactNode } from "react";

/* ------------------------------------------------------------------ logo */

export function Logo({ compact = false }: { compact?: boolean }) {
  return (
    <span className="inline-flex items-center gap-2.5">
      <span className="flex h-9 w-9 items-center justify-center rounded-[10px] bg-brand text-white">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
          <path d="M3 8.5 12 3l9 5.5v7L12 21l-9-5.5z" />
          <path d="M3 8.5 12 14l9-5.5M12 14v7" />
        </svg>
      </span>
      {!compact && (
        <span className="leading-tight">
          <span className="block text-[15px] font-semibold tracking-tight text-ink">Didi2 B2B</span>
          <span className="block text-[11px] text-muted">Materia prima y abasto</span>
        </span>
      )}
    </span>
  );
}

/* ----------------------------------------------------------------- botones */

const BTN_BASE =
  "inline-flex items-center justify-center gap-2 rounded-full font-semibold transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60";

const BTN_VARIANTS = {
  primary: "bg-brand text-white hover:bg-brand-2 shadow-sm",
  ghost: "border border-line-2 bg-white text-ink hover:border-brand-2 hover:text-brand",
  soft: "bg-brand-soft text-brand hover:bg-[#dcece3]",
  accent: "bg-accent text-white hover:brightness-95",
  danger: "border border-red-200 bg-white text-red-700 hover:bg-red-50",
} as const;

const BTN_SIZES = {
  sm: "px-3.5 py-2 text-[12.5px]",
  md: "px-5 py-2.5 text-sm",
  lg: "px-6 py-3 text-[15px]",
} as const;

type Variant = keyof typeof BTN_VARIANTS;
type Size = keyof typeof BTN_SIZES;

export function Button({
  variant = "primary",
  size = "md",
  className = "",
  ...props
}: ComponentProps<"button"> & { variant?: Variant; size?: Size }) {
  return <button className={`${BTN_BASE} ${BTN_VARIANTS[variant]} ${BTN_SIZES[size]} ${className}`} {...props} />;
}

export function ButtonLink({
  variant = "primary",
  size = "md",
  className = "",
  ...props
}: ComponentProps<typeof Link> & { variant?: Variant; size?: Size }) {
  return <Link className={`${BTN_BASE} ${BTN_VARIANTS[variant]} ${BTN_SIZES[size]} ${className}`} {...props} />;
}

/* ------------------------------------------------------------------ badges */

const TONES = {
  neutral: "border-line bg-surface text-ink-2",
  brand: "border-[#cfe3d8] bg-brand-soft text-brand",
  accent: "border-[#f0e0bd] bg-accent-soft text-[#8a5d10]",
  danger: "border-red-200 bg-red-50 text-red-700",
  info: "border-blue-200 bg-blue-50 text-blue-700",
} as const;

export function Badge({
  children,
  tone = "neutral",
  className = "",
}: {
  children: ReactNode;
  tone?: keyof typeof TONES;
  className?: string;
}) {
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${TONES[tone]} ${className}`}
    >
      {children}
    </span>
  );
}

export function StatusBadge({ status }: { status: string }) {
  const map: Record<string, { tone: keyof typeof TONES; label: string }> = {
    PENDIENTE: { tone: "accent", label: "Pendiente" },
    ACEPTADO: { tone: "info", label: "Aceptado" },
    PREPARANDO: { tone: "info", label: "Preparando" },
    EN_CAMINO: { tone: "brand", label: "En camino" },
    ENTREGADO: { tone: "brand", label: "Entregado" },
    CANCELADO: { tone: "danger", label: "Cancelado" },
    DISPONIBLE: { tone: "accent", label: "Disponible" },
    ASIGNADO: { tone: "info", label: "Asignado" },
    RECOGIDO: { tone: "info", label: "Recogido" },
    APROBADO: { tone: "brand", label: "Aprobado" },
    RECHAZADO: { tone: "danger", label: "Rechazado" },
  };
  const item = map[status] ?? { tone: "neutral" as const, label: status };
  return <Badge tone={item.tone}>{item.label}</Badge>;
}

/* ------------------------------------------------------------------ campos */

export function Field({
  label,
  hint,
  children,
}: {
  label: string;
  hint?: string;
  children: ReactNode;
}) {
  return (
    <label className="block">
      <span className="label">{label}</span>
      {children}
      {hint && <span className="mt-1 block text-[11.5px] text-muted">{hint}</span>}
    </label>
  );
}

/* ------------------------------------------------------------------ varios */

export function Stat({
  label,
  value,
  hint,
  icon,
}: {
  label: string;
  value: string | number;
  hint?: string;
  icon?: ReactNode;
}) {
  return (
    <div className="card p-4">
      <div className="flex items-start justify-between gap-3">
        <span className="text-[11.5px] font-semibold uppercase tracking-[0.08em] text-muted">{label}</span>
        {icon && <span className="text-brand">{icon}</span>}
      </div>
      <p className="mt-2 text-2xl font-semibold tracking-tight text-ink">{value}</p>
      {hint && <p className="mt-0.5 text-[12px] text-muted">{hint}</p>}
    </div>
  );
}

export function EmptyState({
  title,
  description,
  action,
}: {
  title: string;
  description: string;
  action?: ReactNode;
}) {
  return (
    <div className="card flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
      <p className="text-[15px] font-semibold text-ink">{title}</p>
      <p className="max-w-md text-[13px] text-muted">{description}</p>
      {action && <div className="mt-2">{action}</div>}
    </div>
  );
}

export function money(value: number) {
  return new Intl.NumberFormat("es-MX", {
    style: "currency",
    currency: "MXN",
    minimumFractionDigits: 2,
  }).format(value);
}

export function fecha(value: Date | string) {
  return new Intl.DateTimeFormat("es-MX", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(new Date(value));
}
