import Link from "next/link";
import { ArrowRight, ClipboardList, ShieldCheck, TrendingUp, Users } from "lucide-react";
import { prisma } from "@/lib/db";
import { Stat, StatusBadge, fecha, money } from "@/components/ui";

export default async function AdminResumenPage() {
  const [usuarios, pendientes, pedidos, ventas, ultimosUsuarios, ultimosPedidos] = await Promise.all([
    prisma.user.count(),
    prisma.user.count({ where: { kycStatus: "PENDIENTE" } }),
    prisma.order.count(),
    prisma.order.aggregate({ _sum: { total: true } }),
    prisma.user.findMany({ orderBy: { createdAt: "desc" }, take: 5 }),
    prisma.order.findMany({
      include: { buyer: { select: { businessName: true } }, supplier: { select: { businessName: true } } },
      orderBy: { createdAt: "desc" },
      take: 5,
    }),
  ]);

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Resumen</h1>
      <p className="mt-1 text-[13.5px] text-muted">Operación de la plataforma</p>

      <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Stat label="Usuarios" value={usuarios} icon={<Users size={18} strokeWidth={1.9} />} />
        <Stat label="KYC pendientes" value={pendientes} hint="Requieren revisión" icon={<ShieldCheck size={18} strokeWidth={1.9} />} />
        <Stat label="Pedidos" value={pedidos} icon={<ClipboardList size={18} strokeWidth={1.9} />} />
        <Stat label="Volumen" value={money(ventas._sum.total ?? 0)} icon={<TrendingUp size={18} strokeWidth={1.9} />} />
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-2">
        <section>
          <div className="flex items-center justify-between">
            <h2 className="text-[15px] font-semibold text-ink">Altas recientes</h2>
            <Link href="/app/admin/usuarios" className="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-brand hover:gap-2.5 transition-all">
              Ver usuarios <ArrowRight size={13} strokeWidth={2.2} />
            </Link>
          </div>
          <div className="mt-3 space-y-2">
            {ultimosUsuarios.map((u) => (
              <div key={u.id} className="card flex items-center justify-between gap-3 p-3.5">
                <div className="min-w-0">
                  <p className="truncate text-[13px] font-semibold text-ink">{u.businessName || u.name}</p>
                  <p className="truncate text-[11.5px] text-muted">{u.email} · {u.role}</p>
                </div>
                <StatusBadge status={u.kycStatus} />
              </div>
            ))}
          </div>
        </section>

        <section>
          <div className="flex items-center justify-between">
            <h2 className="text-[15px] font-semibold text-ink">Pedidos recientes</h2>
            <Link href="/app/admin/pedidos" className="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-brand hover:gap-2.5 transition-all">
              Ver pedidos <ArrowRight size={13} strokeWidth={2.2} />
            </Link>
          </div>
          <div className="mt-3 space-y-2">
            {ultimosPedidos.map((o) => (
              <div key={o.id} className="card flex items-center justify-between gap-3 p-3.5">
                <div className="min-w-0">
                  <p className="truncate text-[13px] font-semibold text-ink">{o.folio}</p>
                  <p className="truncate text-[11.5px] text-muted">
                    {o.buyer.businessName} → {o.supplier.businessName} · {fecha(o.createdAt)}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <StatusBadge status={o.status} />
                  <span className="text-[12.5px] font-semibold text-ink">{money(o.total)}</span>
                </div>
              </div>
            ))}
          </div>
        </section>
      </div>
    </div>
  );
}

