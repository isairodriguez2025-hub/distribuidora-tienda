import Link from "next/link";
import { Truck } from "lucide-react";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { CancelOrderButton } from "@/components/comercio/order-actions";
import { EmptyState, StatusBadge, fecha, money } from "@/components/ui";

const PASOS = ["Pedido", "Preparando", "En camino", "Entregado"];

function avance(status: string) {
  if (status === "PENDIENTE") return 1;
  if (status === "ACEPTADO" || status === "PREPARANDO") return 2;
  if (status === "EN_CAMINO") return 3;
  if (status === "ENTREGADO") return 4;
  return 0;
}

export default async function PedidosComercioPage() {
  const session = await auth();
  const pedidos = await prisma.order.findMany({
    where: { buyerId: session!.user.id },
    include: {
      items: true,
      supplier: { select: { businessName: true, phone: true } },
      delivery: { include: { driver: { select: { name: true, phone: true } } } },
    },
    orderBy: { createdAt: "desc" },
  });

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Mis pedidos</h1>
      <p className="mt-1 text-[13.5px] text-muted">
        {pedidos.length} {pedidos.length === 1 ? "pedido" : "pedidos"} · sigue el avance hasta la entrega
      </p>

      {pedidos.length === 0 ? (
        <div className="mt-6">
          <EmptyState
            title="Todavía no tienes pedidos"
            description="Cuando confirmes tu primer pedido aparecerá aquí con su seguimiento."
            action={
              <Link
                href="/app/comercio"
                className="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13.5px] font-semibold text-white transition hover:bg-brand-2"
              >
                Ir al catálogo
              </Link>
            }
          />
        </div>
      ) : (
        <div className="mt-6 space-y-4">
          {pedidos.map((pedido) => {
            const paso = avance(pedido.status);
            const cancelado = pedido.status === "CANCELADO";
            return (
              <article key={pedido.id} className="card p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-[14.5px] font-semibold text-ink">{pedido.folio}</span>
                      <StatusBadge status={pedido.status} />
                    </div>
                    <p className="mt-1 text-[12.5px] text-muted">
                      {fecha(pedido.createdAt)} · {pedido.supplier.businessName}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-[16px] font-semibold text-ink">{money(pedido.total)}</p>
                    <p className="text-[11.5px] text-muted">
                      {pedido.items.length} {pedido.items.length === 1 ? "artículo" : "artículos"}
                    </p>
                  </div>
                </div>

                {!cancelado && (
                  <div className="mt-5 flex items-center gap-1.5">
                    {PASOS.map((nombre, i) => {
                      const activo = i < paso;
                      return (
                        <div key={nombre} className="flex flex-1 items-center gap-1.5">
                          <div className="flex-1">
                            <div
                              className={`h-1.5 rounded-full ${activo ? "bg-brand" : "bg-line"}`}
                            />
                            <p
                              className={`mt-1.5 text-[10.5px] font-medium ${
                                activo ? "text-brand" : "text-muted"
                              }`}
                            >
                              {nombre}
                            </p>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )}

                <ul className="mt-4 space-y-1 border-t border-line pt-3 text-[12.5px] text-ink-2">
                  {pedido.items.map((item) => (
                    <li key={item.id} className="flex justify-between gap-3">
                      <span className="truncate">
                        {item.qty} × {item.name}
                      </span>
                      <span className="shrink-0 text-muted">{money(item.unitPrice * item.qty)}</span>
                    </li>
                  ))}
                </ul>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                  <div className="text-[12px] text-muted">
                    {pedido.delivery?.driver ? (
                      <span className="inline-flex items-center gap-1.5">
                        <Truck size={14} strokeWidth={1.9} className="text-brand" />
                        Repartidor: {pedido.delivery.driver.name} · {pedido.delivery.driver.phone}
                      </span>
                    ) : (
                      <span>Entrega en {pedido.address || "tu dirección registrada"}</span>
                    )}
                  </div>
                  {pedido.status === "PENDIENTE" && <CancelOrderButton orderId={pedido.id} />}
                </div>
              </article>
            );
          })}
        </div>
      )}
    </div>
  );
}

