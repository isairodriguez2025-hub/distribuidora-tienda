import Link from "next/link";
import { prisma } from "@/lib/db";
import { AdminUserActions } from "@/components/paneles/admin-user-actions";
import { Badge, StatusBadge, fecha } from "@/components/ui";

const ROLES = ["COMERCIO", "MAYORISTA", "REPARTIDOR", "ADMIN"];

export default async function UsuariosPage({
  searchParams,
}: {
  searchParams: Promise<{ rol?: string }>;
}) {
  const { rol = "" } = await searchParams;
  const usuarios = await prisma.user.findMany({
    where: rol ? { role: rol } : {},
    orderBy: { createdAt: "desc" },
  });

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Usuarios y KYC</h1>
      <p className="mt-1 text-[13.5px] text-muted">
        {usuarios.length} {usuarios.length === 1 ? "cuenta" : "cuentas"} · verifica antes de permitir operar
      </p>

      <div className="mt-4 flex flex-wrap gap-2">
        <Link
          href="/app/admin/usuarios"
          className={`rounded-full border px-3 py-1.5 text-[12.5px] font-medium transition ${
            rol === "" ? "border-brand-2 bg-brand-soft text-brand" : "border-line bg-white text-ink-2 hover:border-line-2"
          }`}
        >
          Todos
        </Link>
        {ROLES.map((r) => (
          <Link
            key={r}
            href={`/app/admin/usuarios?rol=${r}`}
            className={`rounded-full border px-3 py-1.5 text-[12.5px] font-medium transition ${
              rol === r ? "border-brand-2 bg-brand-soft text-brand" : "border-line bg-white text-ink-2 hover:border-line-2"
            }`}
          >
            {r}
          </Link>
        ))}
      </div>

      <div className="card mt-5 overflow-x-auto">
        <table className="w-full min-w-[860px] text-left text-[13px]">
          <thead>
            <tr className="border-b border-line bg-surface text-[11px] uppercase tracking-[0.06em] text-muted">
              <th className="px-4 py-3 font-semibold">Cuenta</th>
              <th className="px-4 py-3 font-semibold">Rol</th>
              <th className="px-4 py-3 font-semibold">Ciudad</th>
              <th className="px-4 py-3 font-semibold">KYC</th>
              <th className="px-4 py-3 font-semibold">Alta</th>
              <th className="px-4 py-3 font-semibold">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {usuarios.map((u) => (
              <tr key={u.id} className="border-b border-line last:border-0">
                <td className="px-4 py-3">
                  <p className="font-medium text-ink">{u.businessName || u.name}</p>
                  <p className="text-[11.5px] text-muted">{u.email}</p>
                </td>
                <td className="px-4 py-3">
                  <Badge tone={u.role === "ADMIN" ? "info" : "neutral"}>{u.role}</Badge>
                </td>
                <td className="px-4 py-3 text-ink-2">{u.city || "—"}</td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-1.5">
                    <StatusBadge status={u.kycStatus} />
                    {!u.active && <Badge tone="danger">Inactiva</Badge>}
                  </div>
                </td>
                <td className="px-4 py-3 text-muted">{fecha(u.createdAt)}</td>
                <td className="px-4 py-3">
                  <AdminUserActions userId={u.id} kycStatus={u.kycStatus} active={u.active} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

