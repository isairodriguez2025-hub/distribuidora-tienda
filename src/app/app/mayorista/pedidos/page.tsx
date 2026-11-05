import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { MayoristaOrderActions } from "@/components/paneles/order-actions";
import { EmptyState, StatusBadge, fecha, money } from "@/components/ui";

export default async function PedidosMayoristaPage() {
  const session = await auth();
  const pedidos = await prisma.order.findMany({
    where: { supplierId: session!.user.id },
    include: {
      buyer: { select: { businessName: true, name: true, phone: true, address: true } },
      items: true,
      delivery: { include: { driver: { select: { name: true } } } },
    },
    orderBy: { createdAt: "desc" },
  });

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Pedidos</h1>
      <p className="mt-1 text-[13.5px] text-muted">
        {pedidos.length} {pedidos.length === 1 ? "pedido recibido" : "pedidos recibidos"}
      </p>

      {pedidos.length === 0 ? (
        <div className="mt-6">
          <EmptyState
            title="Sin pedidos por ahora"
            description="Cuando un comercio compre tus productos aparecerán aquí para que los aceptes."
          />
        </div>
      ) : (
        <div className="mt-6 space-y-3">
          {pedidos.map((o) => (
            <article key={o.id} className="card p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-[14.5px] font-semibold text-ink">{o.folio}</span>
                    <StatusBadge status={o.status} />
                  </div>
                  <p className="mt-1 text-[12.5px] text-muted">
                    {o.buyer.businessName} · {fecha(o.createdAt)}
                  </p>
                  <p className="text-[12px] text-muted">{o.address}</p>
                </div>
                <div className="text-right">
                  <p className="text-[16px] font-semibold text-ink">{money(o.total)}</p>
                  <p className="text-[11.5px] text-muted">IVA incluido</p>
                </div>
              </div>

              <ul className="mt-4 space-y-1 border-t border-line pt-3 text-[12.5px] text-ink-2">
                {o.items.map((item) => (
                  <li key={item.id} className="flex justify-between gap-3">
                    <span className="truncate">
                      {item.qty} × {item.name}
                    </span>
                    <span className="shrink-0 text-muted">{money(item.unitPrice * item.qty)}</span>
                  </li>
                ))}
              </ul>

              <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                <p className="text-[12px] text-muted">
                  {o.delivery?.driver
                    ? `Repartidor asignado: ${o.delivery.driver.name}`
                    : "Sin repartidor asignado"}
                </p>
                <MayoristaOrderActions orderId={o.id} status={o.status} />
              </div>
            </article>
          ))}
        </div>
      )}
    </div>
  );
}

