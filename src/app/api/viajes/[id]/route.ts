import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user || session.user.role !== "REPARTIDOR") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  const { id } = await params;
  const body = await request.json().catch(() => null);
  const accion = String(body?.accion ?? "");

  const delivery = await prisma.delivery.findUnique({ where: { id } });
  if (!delivery) return NextResponse.json({ error: "Viaje no encontrado" }, { status: 404 });

  if (accion === "aceptar") {
    if (delivery.status !== "DISPONIBLE") {
      return NextResponse.json({ error: "Ese viaje ya fue tomado" }, { status: 409 });
    }
    const updated = await prisma.delivery.update({
      where: { id },
      data: { driverId: session.user.id, status: "ASIGNADO" },
    });
    await prisma.order.update({ where: { id: delivery.orderId }, data: { status: "EN_CAMINO" } });
    return NextResponse.json({ ok: true, delivery: updated });
  }

  if (delivery.driverId !== session.user.id) {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  if (accion === "recoger") {
    const updated = await prisma.delivery.update({ where: { id }, data: { status: "RECOGIDO" } });
    return NextResponse.json({ ok: true, delivery: updated });
  }

  if (accion === "entregar") {
    const updated = await prisma.delivery.update({ where: { id }, data: { status: "ENTREGADO" } });
    await prisma.order.update({ where: { id: delivery.orderId }, data: { status: "ENTREGADO" } });
    return NextResponse.json({ ok: true, delivery: updated });
  }

  return NextResponse.json({ error: "Acción no válida" }, { status: 400 });
}
