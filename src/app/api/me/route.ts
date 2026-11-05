import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user) return NextResponse.json({ error: "No autorizado" }, { status: 401 });

  const user = await prisma.user.findUnique({
    where: { id: session.user.id },
    select: {
      id: true,
      name: true,
      email: true,
      role: true,
      businessName: true,
      phone: true,
      city: true,
      address: true,
      rfc: true,
      kycStatus: true,
    },
  });

  if (!user) return NextResponse.json({ error: "No encontrado" }, { status: 404 });
  return NextResponse.json({ user });
}
