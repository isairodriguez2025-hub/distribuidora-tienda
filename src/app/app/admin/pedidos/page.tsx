import { prisma } from "@/lib/db";
import { AdminOrderStatus } from "@/components/paneles/order-actions";
import { EmptyState, fecha, money } from "@/components/ui";

export default async function AdminPedidosPage() {
  const pedidos = await prisma.order.findMany({
    include: {
      buyer: { select: { businessName: true } },
      supplier: { select: { businessName: true } },
      items: true,
    },
    orderBy: { createdAt: "desc" },
  });

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Pedidos</h1>
      <p className="mt-1 text-[13.5px] text-muted">{pedidos.length} pedidos en la plataforma</p>

      {pedidos.length === 0 ? (
        <div className="mt-6">
          <EmptyState title="Sin pedidos" description="Todavía no se ha generado ningún pedido." />
        </div>
      ) : (
        <div className="card mt-5 overflow-x-auto">
          <table className="w-full min-w-[900px] text-left text-[13px]">
            <thead>
              <tr className="border-b border-line bg-surface text-[11px] uppercase tracking-[0.06em] text-muted">
                <th className="px-4 py-3 font-semibold">Folio</th>
                <th className="px-4 py-3 font-semibold">Comercio</th>
                <th className="px-4 py-3 font-semibold">Mayorista</th>
                <th className="px-4 py-3 font-semibold">Fecha</th>
                <th className="px-4 py-3 text-right font-semibold">Total</th>
                <th className="px-4 py-3 font-semibold">Estado</th>
              </tr>
            </thead>
            <tbody>
              {pedidos.map((o) => (
                <tr key={o.id} className="border-b border-line last:border-0">
                  <td className="px-4 py-3 font-medium text-ink">{o.folio}</td>
                  <td className="px-4 py-3 text-ink-2">{o.buyer.businessName}</td>
                  <td className="px-4 py-3 text-ink-2">{o.supplier.businessName}</td>
                  <td className="px-4 py-3 text-muted">{fecha(o.createdAt)}</td>
                  <td className="px-4 py-3 text-right font-semibold text-ink">{money(o.total)}</td>
                  <td className="px-4 py-3">
                    <AdminOrderStatus orderId={o.id} status={o.status} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

