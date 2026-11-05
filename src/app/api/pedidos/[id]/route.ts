import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

const PERMITIDOS: Record<string, string[]> = {
  MAYORISTA: ["ACEPTADO", "PREPARANDO", "CANCELADO"],
  COMERCIO: ["CANCELADO"],
  ADMIN: ["ACEPTADO", "PREPARANDO", "EN_CAMINO", "ENTREGADO", "CANCELADO"],
};

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user) return NextResponse.json({ error: "No autorizado" }, { status: 401 });

  const { id } = await params;
  const body = await request.json().catch(() => null);
  const status = String(body?.status ?? "");

  const order = await prisma.order.findUnique({ where: { id } });
  if (!order) return NextResponse.json({ error: "Pedido no encontrado" }, { status: 404 });

  const role = session.user.role;
  const esPropio =
    (role === "MAYORISTA" && order.supplierId === session.user.id) ||
    (role === "COMERCIO" && order.buyerId === session.user.id) ||
    role === "ADMIN";

  if (!esPropio) return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  if (!PERMITIDOS[role]?.includes(status)) {
    return NextResponse.json({ error: "Transición no permitida" }, { status: 400 });
  }

  const updated = await prisma.order.update({ where: { id }, data: { status } });

  if (status === "PREPARANDO") {
    await prisma.delivery.updateMany({
      where: { orderId: id, status: "DISPONIBLE" },
      data: { status: "DISPONIBLE" },
    });
  }

  return NextResponse.json({ ok: true, order: updated });
}
