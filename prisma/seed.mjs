import { PrismaClient } from "@prisma/client";
import { hash } from "bcrypt-ts";

const prisma = new PrismaClient();
const password = await hash("demo1234", 10);

const users = [
  {
    email: "comercio@didi2.mx",
    name: "María Fernanda Ríos",
    role: "COMERCIO",
    businessName: "Cafetería La Esquina",
    phone: "55 1234 5678",
    city: "CDMX",
    address: "Av. Insurgentes Sur 1234, Benito Juárez",
    rfc: "RIFM900101AB1",
    kycStatus: "APROBADO",
  },
  {
    email: "mayorista@didi2.mx",
    name: "Jorge Salazar",
    role: "MAYORISTA",
    businessName: "Distribuidora Salazar",
    phone: "55 8765 4321",
    city: "CDMX",
    address: "Bodega 12, Central de Abasto",
    rfc: "SAJO850202CD3",
    kycStatus: "APROBADO",
  },
  {
    email: "repartidor@didi2.mx",
    name: "Luis Martínez",
    role: "REPARTIDOR",
    businessName: "Transportes LM",
    phone: "55 5555 1212",
    city: "CDMX",
    address: "Iztapalapa",
    kycStatus: "APROBADO",
  },
  {
    email: "admin@didi2.mx",
    name: "Administración Didi2",
    role: "ADMIN",
    businessName: "Didi2 B2B",
    phone: "55 0000 0000",
    city: "CDMX",
    kycStatus: "APROBADO",
  },
];

const created = {};
for (const u of users) {
  created[u.role] = await prisma.user.upsert({
    where: { email: u.email },
    update: {},
    create: { ...u, password },
  });
}

const catalogo = [
  ["Aceite vegetal 1 L", "Abarrotes", "caja con 12", 438, 24, 1, 1],
  ["Azúcar estándar 1 kg", "Abarrotes", "bulto con 10", 285, 8, 1, 1],
  ["Café soluble 200 g", "Abarrotes", "caja con 12", 1020, 15, 1, 1],
  ["Harina de trigo 1 kg", "Abarrotes", "bulto con 10", 210, 40, 1, 1],
  ["Arroz grano largo 1 kg", "Abarrotes", "bulto con 10", 245, 32, 1, 1],
  ["Cloro 1 L", "Limpieza", "caja con 12", 186, 40, 1, 1],
  ["Detergente en polvo 1 kg", "Limpieza", "caja con 12", 528, 6, 1, 1],
  ["Papel higiénico 4 rollos", "Limpieza", "paquete con 8", 392, 30, 1, 1],
  ["Jabón de barra 200 g", "Limpieza", "caja con 24", 264, 18, 1, 1],
  ["Tornillo pija 1\" punta broca", "Herrería", "caja con 100", 78, 60, 1, 1],
  ["Martillo de uña 16 oz", "Herrería", "pieza", 165, 12, 1, 1],
  ["Disco de corte 4 1/2\"", "Herrería", "paquete con 10", 210, 4, 1, 1],
  ["Perfil cuadrado 1\" x 6 m", "Herrería", "pieza", 320, 25, 1, 1],
  ["Guantes de carnaza", "Herrería", "par", 95, 50, 1, 1],
];

for (const [name, category, unit, price, stock, minQty, multiple] of catalogo) {
  const exists = await prisma.product.findFirst({
    where: { name, supplierId: created.MAYORISTA.id },
  });
  if (!exists) {
    await prisma.product.create({
      data: {
        name,
        category,
        unit,
        price,
        stock,
        minQty,
        multiple,
        supplierId: created.MAYORISTA.id,
      },
    });
  }
}

const pedidos = await prisma.order.count();
if (pedidos === 0) {
  const productos = await prisma.product.findMany({ take: 3 });
  const items = productos.map((p) => ({ p, qty: 2 }));
  const subtotal = items.reduce((s, i) => s + i.p.price * i.qty, 0);
  const iva = Math.round(subtotal * 0.16 * 100) / 100;

  const order = await prisma.order.create({
    data: {
      folio: "PED-000128",
      buyerId: created.COMERCIO.id,
      supplierId: created.MAYORISTA.id,
      status: "EN_CAMINO",
      subtotal,
      iva,
      total: subtotal + iva,
      address: "Av. Insurgentes Sur 1234, Benito Juárez",
      items: {
        create: items.map((i) => ({
          productId: i.p.id,
          name: i.p.name,
          qty: i.qty,
          unitPrice: i.p.price,
        })),
      },
    },
  });

  await prisma.delivery.create({
    data: {
      orderId: order.id,
      driverId: created.REPARTIDOR.id,
      status: "ASIGNADO",
      fee: 89,
    },
  });

  const segundo = await prisma.order.create({
    data: {
      folio: "PED-000129",
      buyerId: created.COMERCIO.id,
      supplierId: created.MAYORISTA.id,
      status: "PENDIENTE",
      subtotal: 528,
      iva: 84.48,
      total: 612.48,
      address: "Av. Insurgentes Sur 1234, Benito Juárez",
      items: {
        create: [
          {
            productId: productos[0].id,
            name: productos[0].name,
            qty: 1,
            unitPrice: 528,
          },
        ],
      },
    },
  });

  await prisma.delivery.create({
    data: { orderId: segundo.id, status: "DISPONIBLE", fee: 75 },
  });
}

console.log("Seed listo. Usuarios demo (contraseña: demo1234):");
for (const u of users) console.log(` - ${u.role.padEnd(11)} ${u.email}`);

await prisma.$disconnect();
