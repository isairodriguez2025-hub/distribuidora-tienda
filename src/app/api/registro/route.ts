import { NextResponse } from "next/server";
import { hash } from "bcrypt-ts";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { ROLES } from "@/lib/roles";

const schema = z.object({
  name: z.string().min(3, "Escribe tu nombre completo"),
  email: z.string().email("Correo no válido"),
  password: z.string().min(8, "La contraseña debe tener al menos 8 caracteres"),
  phone: z.string().min(10, "Teléfono a 10 dígitos"),
  role: z.enum(ROLES),
  businessName: z.string().min(2, "Escribe el nombre de tu negocio"),
  rfc: z.string().optional().default(""),
  city: z.string().min(2, "Indica tu ciudad"),
  address: z.string().min(5, "Indica tu dirección"),
});

export async function POST(request: Request) {
  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);

  if (!parsed.success) {
    return NextResponse.json(
      { error: parsed.error.issues[0]?.message ?? "Datos incompletos" },
      { status: 400 },
    );
  }

  const data = parsed.data;
  const email = data.email.toLowerCase();

  const exists = await prisma.user.findUnique({ where: { email } });
  if (exists) {
    return NextResponse.json({ error: "Ese correo ya está registrado" }, { status: 409 });
  }

  const user = await prisma.user.create({
    data: {
      email,
      password: await hash(data.password, 10),
      name: data.name,
      phone: data.phone,
      role: data.role,
      businessName: data.businessName,
      rfc: data.rfc ?? "",
      city: data.city,
      address: data.address,
      kycStatus: data.role === "COMERCIO" ? "PENDIENTE" : "PENDIENTE",
    },
    select: { id: true, email: true, role: true },
  });

  return NextResponse.json({ ok: true, user }, { status: 201 });
}
