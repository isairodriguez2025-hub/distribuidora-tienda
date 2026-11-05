import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user || session.user.role !== "REPARTIDOR") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  const [disponibles, mios] = await Promise.all([
    prisma.delivery.findMany({
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
    }),
    prisma.delivery.findMany({
      where: { driverId: session.user.id },
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
    }),
  ]);

  return NextResponse.json({ disponibles, mios });
}
