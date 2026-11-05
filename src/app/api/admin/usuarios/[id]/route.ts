import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const session = await auth();
  if (!session?.user || session.user.role !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  const { id } = await params;
  const body = await request.json().catch(() => null);
  const data: { kycStatus?: string; active?: boolean } = {};

  if (body?.kycStatus === "APROBADO" || body?.kycStatus === "RECHAZADO") {
    data.kycStatus = body.kycStatus;
  }
  if (typeof body?.active === "boolean") data.active = body.active;

  if (Object.keys(data).length === 0) {
    return NextResponse.json({ error: "Nada que actualizar" }, { status: 400 });
  }

  const user = await prisma.user.update({ where: { id }, data });
  return NextResponse.json({ ok: true, user: { id: user.id, kycStatus: user.kycStatus, active: user.active } });
}
