import { MapPin, Package } from "lucide-react";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { TripActions } from "@/components/paneles/trip-actions";
import { EmptyState, StatusBadge, fecha, money } from "@/components/ui";

export default async function EntregasPage() {
  const session = await auth();
  const entregas = await prisma.delivery.findMany({
    where: { driverId: session!.user.id },
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

  const activas = entregas.filter((e) => e.status !== "ENTREGADO");
  const historial = entregas.filter((e) => e.status === "ENTREGADO");

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Mis entregas</h1>
      <p className="mt-1 text-[13.5px] text-muted">
        {activas.length} en curso · {historial.length} completadas
      </p>

      {entregas.length === 0 ? (
        <div className="mt-6">
          <EmptyState
            title="Todavía no tomas viajes"
            description="Acepta un viaje desde la lista de disponibles y aparecerá aquí para seguimiento."
          />
        </div>
      ) : (
        <div className="mt-6 space-y-3">
          {[...activas, ...historial].map((e) => (
            <article key={e.id} className="card p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-[14.5px] font-semibold text-ink">{e.order.folio}</span>
                    <StatusBadge status={e.status} />
                  </div>
                  <p className="mt-1 text-[12px] text-muted">{fecha(e.createdAt)}</p>
                </div>
                <span className="text-[15px] font-semibold text-ink">{money(e.fee)}</span>
              </div>

              <div className="mt-4 grid gap-3 border-t border-line pt-3 sm:grid-cols-2">
                <div className="flex gap-2.5">
                  <Package size={15} strokeWidth={1.9} className="mt-0.5 shrink-0 text-brand" />
                  <div className="min-w-0">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.06em] text-muted">Origen</p>
                    <p className="truncate text-[13px] text-ink">{e.order.supplier.businessName}</p>
                    <p className="truncate text-[12px] text-muted">{e.order.supplier.address}</p>
                  </div>
                </div>
                <div className="flex gap-2.5">
                  <MapPin size={15} strokeWidth={1.9} className="mt-0.5 shrink-0 text-accent" />
                  <div className="min-w-0">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.06em] text-muted">Destino</p>
                    <p className="truncate text-[13px] text-ink">{e.order.buyer.businessName}</p>
                    <p className="truncate text-[12px] text-muted">{e.order.address || e.order.buyer.address}</p>
                  </div>
                </div>
              </div>

              {e.status !== "ENTREGADO" && (
                <div className="mt-4 flex justify-end border-t border-line pt-3">
                  <TripActions deliveryId={e.id} status={e.status} modo="asignado" />
                </div>
              )}
            </article>
          ))}
        </div>
      )}
    </div>
  );
}

