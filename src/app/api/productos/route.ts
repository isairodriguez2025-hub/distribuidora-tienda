import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET(request: Request) {
  const session = await auth();
  if (!session?.user) {
    return NextResponse.json({ error: "No autorizado" }, { status: 401 });
  }

  const { searchParams } = new URL(request.url);
  const q = (searchParams.get("q") ?? "").trim();
  const categoria = searchParams.get("categoria") ?? "";

  const products = await prisma.product.findMany({
    where: {
      active: true,
      ...(categoria ? { category: categoria } : {}),
      ...(q ? { name: { contains: q } } : {}),
    },
    include: { supplier: { select: { businessName: true, city: true } } },
    orderBy: { name: "asc" },
    take: 60,
  });

  return NextResponse.json({ products });
}

export async function POST(request: Request) {
  const session = await auth();
  if (!session?.user || session.user.role !== "MAYORISTA") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  const body = await request.json().catch(() => null);
  if (!body?.name || !body?.category || !body?.price) {
    return NextResponse.json({ error: "Faltan datos del producto" }, { status: 400 });
  }

  const product = await prisma.product.create({
    data: {
      supplierId: session.user.id,
      name: String(body.name),
      category: String(body.category),
      unit: String(body.unit ?? "pieza"),
      price: Number(body.price),
      stock: Number(body.stock ?? 0),
      minQty: Number(body.minQty ?? 1),
      multiple: Number(body.multiple ?? 1),
    },
  });

  return NextResponse.json({ ok: true, product }, { status: 201 });
}
