import Link from "next/link";
import { ArrowRight, Boxes, ClipboardList, PackageX, TrendingUp } from "lucide-react";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { Stat, StatusBadge, fecha, money } from "@/components/ui";

export default async function MayoristaResumenPage() {
  const session = await auth();
  const supplierId = session!.user.id;

  const [productos, pendientes, ventas, stockBajo, ultimos] = await Promise.all([
    prisma.product.count({ where: { supplierId, active: true } }),
    prisma.order.count({ where: { supplierId, status: "PENDIENTE" } }),
    prisma.order.aggregate({
      where: { supplierId, status: { in: ["ACEPTADO", "PREPARANDO", "EN_CAMINO", "ENTREGADO"] } },
      _sum: { total: true },
    }),
    prisma.product.count({ where: { supplierId, active: true, stock: { lt: 10 } } }),
    prisma.order.findMany({
      where: { supplierId },
      include: { buyer: { select: { businessName: true } }, items: true },
      orderBy: { createdAt: "desc" },
      take: 5,
    }),
  ]);

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Resumen</h1>
      <p className="mt-1 text-[13.5px] text-muted">
        {session!.user.businessName || "Tu mayorista"} · actividad de los últimos pedidos
      </p>

      <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Stat label="Productos publicados" value={productos} icon={<Boxes size={18} strokeWidth={1.9} />} />
        <Stat label="Pedidos pendientes" value={pendientes} hint="Esperando tu aceptación" icon={<ClipboardList size={18} strokeWidth={1.9} />} />
        <Stat label="Ventas acumuladas" value={money(ventas._sum.total ?? 0)} icon={<TrendingUp size={18} strokeWidth={1.9} />} />
        <Stat label="Stock bajo" value={stockBajo} hint="Menos de 10 piezas" icon={<PackageX size={18} strokeWidth={1.9} />} />
      </div>

      <div className="mt-8 flex items-center justify-between">
        <h2 className="text-[16px] font-semibold text-ink">Últimos pedidos</h2>
        <Link href="/app/mayorista/pedidos" className="inline-flex items-center gap-1.5 text-[13px] font-semibold text-brand hover:gap-2.5 transition-all">
          Ver todos <ArrowRight size={14} strokeWidth={2.2} />
        </Link>
      </div>

      <div className="mt-3 space-y-2">
        {ultimos.length === 0 && (
          <p className="card p-6 text-center text-[13px] text-muted">Aún no tienes pedidos.</p>
        )}
        {ultimos.map((o) => (
          <div key={o.id} className="card flex flex-wrap items-center justify-between gap-3 p-4">
            <div className="min-w-0">
              <p className="text-[13.5px] font-semibold text-ink">
                {o.folio} · {o.buyer.businessName}
              </p>
              <p className="text-[12px] text-muted">
                {fecha(o.createdAt)} · {o.items.length} {o.items.length === 1 ? "artículo" : "artículos"}
              </p>
            </div>
            <div className="flex items-center gap-3">
              <StatusBadge status={o.status} />
              <span className="text-[14px] font-semibold text-ink">{money(o.total)}</span>
            </div>
          </div>
        ))}
      </div>

      <div className="mt-8 grid gap-3 sm:grid-cols-2">
        <Link href="/app/mayorista/productos" className="card flex items-center justify-between p-5 transition hover:border-line-2">
          <div>
            <p className="text-[14px] font-semibold text-ink">Publicar productos</p>
            <p className="text-[12.5px] text-muted">Agrega artículos, precios y existencias</p>
          </div>
          <ArrowRight size={16} strokeWidth={2} className="text-brand" />
        </Link>
        <Link href="/app/mayorista/pedidos" className="card flex items-center justify-between p-5 transition hover:border-line-2">
          <div>
            <p className="text-[14px] font-semibold text-ink">Atender pedidos</p>
            <p className="text-[12.5px] text-muted">Acepta y marca como preparando</p>
          </div>
          <ArrowRight size={16} strokeWidth={2} className="text-brand" />
        </Link>
      </div>
    </div>
  );
}

