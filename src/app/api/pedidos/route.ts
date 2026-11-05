import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user) return NextResponse.json({ error: "No autorizado" }, { status: 401 });

  const role = session.user.role;
  const where =
    role === "COMERCIO"
      ? { buyerId: session.user.id }
      : role === "MAYORISTA"
        ? { supplierId: session.user.id }
        : role === "REPARTIDOR"
          ? { delivery: { driverId: session.user.id } }
          : {};

  const orders = await prisma.order.findMany({
    where,
    include: {
      items: true,
      buyer: { select: { businessName: true, name: true, phone: true } },
      supplier: { select: { businessName: true, name: true, phone: true } },
      delivery: { include: { driver: { select: { name: true, phone: true } } } },
    },
    orderBy: { createdAt: "desc" },
    take: 50,
  });

  return NextResponse.json({ orders });
}

export async function POST(request: Request) {
  const session = await auth();
  if (!session?.user || session.user.role !== "COMERCIO") {
    return NextResponse.json({ error: "Solo los comercios pueden comprar" }, { status: 403 });
  }

  const body = await request.json().catch(() => null);
  const items = Array.isArray(body?.items) ? body.items : [];
  if (items.length === 0) {
    return NextResponse.json({ error: "El carrito está vacío" }, { status: 400 });
  }

  const ids = items.map((i: { productId: string }) => i.productId);
  const products = await prisma.product.findMany({ where: { id: { in: ids } } });

  const porProveedor = new Map<string, { productId: string; name: string; qty: number; unitPrice: number }[]>();

  for (const item of items as { productId: string; qty: number }[]) {
    const product = products.find((p) => p.id === item.productId);
    if (!product) continue;
    const qty = Math.max(1, Math.floor(Number(item.qty) || 1));
    if (product.stock < qty) {
      return NextResponse.json(
        { error: `Sin existencia suficiente de ${product.name}` },
        { status: 409 },
      );
    }
    const lista = porProveedor.get(product.supplierId) ?? [];
    lista.push({ productId: product.id, name: product.name, qty, unitPrice: product.price });
    porProveedor.set(product.supplierId, lista);
  }

  if (porProveedor.size === 0) {
    return NextResponse.json({ error: "No se encontraron productos" }, { status: 400 });
  }

  const creados = [];
  let consecutivo = await prisma.order.count();

  for (const [supplierId, lista] of porProveedor) {
    const subtotal = lista.reduce((s, i) => s + i.unitPrice * i.qty, 0);
    const iva = Math.round(subtotal * 0.16 * 100) / 100;
    consecutivo += 1;

    const order = await prisma.order.create({
      data: {
        folio: `PED-${String(consecutivo).padStart(6, "0")}`,
        buyerId: session.user.id,
        supplierId,
        status: "PENDIENTE",
        subtotal,
        iva,
        total: Math.round((subtotal + iva) * 100) / 100,
        address: String(body?.address ?? ""),
        items: { create: lista },
      },
      include: { items: true },
    });

    await prisma.delivery.create({
      data: { orderId: order.id, status: "DISPONIBLE", fee: 75 },
    });

    for (const item of lista) {
      await prisma.product.update({
        where: { id: item.productId },
        data: { stock: { decrement: item.qty } },
      });
    }

    creados.push(order);
  }

  return NextResponse.json({ ok: true, orders: creados }, { status: 201 });
}
