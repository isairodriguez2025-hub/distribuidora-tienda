import { MapPin, Package, Truck } from "lucide-react";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { TripActions } from "@/components/paneles/trip-actions";
import { Badge, EmptyState, money } from "@/components/ui";

export default async function ViajesPage() {
  const session = await auth();
  const viajes = await prisma.delivery.findMany({
    where: { status: "DISPONIBLE" },
    include: {
      order: {
        include: {
          supplier: { select: { businessName: true, address: true, city: true } },
          buyer: { select: { businessName: true, address: true, city: true } },
          items: true,
        },
      },
    },
    orderBy: { createdAt: "desc" },
  });

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Viajes disponibles</h1>
      <p className="mt-1 text-[13.5px] text-muted">
        {session!.user.businessName || "Tu unidad"} · {viajes.length}{" "}
        {viajes.length === 1 ? "viaje esperando repartidor" : "viajes esperando repartidor"}
      </p>

      {viajes.length === 0 ? (
        <div className="mt-6">
          <EmptyState
            title="No hay viajes por ahora"
            description="Cuando un mayorista tenga un pedido listo aparecerá aquí con su tarifa."
          />
        </div>
      ) : (
        <div className="mt-6 grid gap-4 lg:grid-cols-2">
          {viajes.map((v) => {
            const bultos = v.order.items.reduce((s, i) => s + i.qty, 0);
            return (
              <article key={v.id} className="card p-5">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="text-[14.5px] font-semibold text-ink">{v.order.folio}</p>
                    <p className="text-[12px] text-muted">
                      {bultos} {bultos === 1 ? "bulto" : "bultos"} · {v.order.items.length} líneas
                    </p>
                  </div>
                  <Badge tone="accent">{money(v.fee)}</Badge>
                </div>

                <div className="mt-4 space-y-3 border-t border-line pt-3">
                  <div className="flex gap-2.5">
                    <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand">
                      <Package size={14} strokeWidth={2} />
                    </span>
                    <div className="min-w-0">
                      <p className="text-[11px] font-semibold uppercase tracking-[0.06em] text-muted">Recoger en</p>
                      <p className="truncate text-[13px] font-medium text-ink">{v.order.supplier.businessName}</p>
                      <p className="truncate text-[12px] text-muted">
                        {v.order.supplier.address}, {v.order.supplier.city}
                      </p>
                    </div>
                  </div>

                  <div className="flex gap-2.5">
                    <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent">
                      <MapPin size={14} strokeWidth={2} />
                    </span>
                    <div className="min-w-0">
                      <p className="text-[11px] font-semibold uppercase tracking-[0.06em] text-muted">Entregar en</p>
                      <p className="truncate text-[13px] font-medium text-ink">{v.order.buyer.businessName}</p>
                      <p className="truncate text-[12px] text-muted">
                        {v.order.address || `${v.order.buyer.address}, ${v.order.buyer.city}`}
                      </p>
                    </div>
                  </div>
                </div>

                <div className="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3">
                  <span className="inline-flex items-center gap-1.5 text-[12px] text-muted">
                    <Truck size={14} strokeWidth={1.9} className="text-brand" />
                    Tarifa congelada al aceptar
                  </span>
                  <TripActions deliveryId={v.id} status={v.status} modo="disponible" />
                </div>
              </article>
            );
          })}
        </div>
      )}
    </div>
  );
}

