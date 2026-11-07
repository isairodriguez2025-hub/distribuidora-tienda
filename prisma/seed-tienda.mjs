/**
 * Seed de la tienda en línea (bloque 1 del documento de pasos).
 * Crea las 3 secciones, los 30 productos del catálogo demo y 2 repartidores.
 * Es idempotente: se puede correr varias veces sin duplicar.
 *
 *   node --env-file=.env prisma/seed-tienda.mjs
 */
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

const SECCIONES = [
  { key: 'abarrotes', name: 'Abarrotes', description: 'Consumo diario para tienda de barrio, venta por caja o bulto.' },
  { key: 'limpieza', name: 'Limpieza', description: 'Detergentes, cloro, jabón, papel e insumos de aseo.' },
  { key: 'herreria', name: 'Herrería', description: 'Herramienta, tornillería, perfiles y consumibles de taller.' },
];

const PRODUCTOS = [
  {
    "nombre": "Aceite vegetal comestible 1 L",
    "precio": 438,
    "seccion": "abarrotes",
    "presentacion": "Caja con 12 piezas",
    "stock": 24,
    "marca": "Nutrioli"
  },
  {
    "nombre": "Azúcar estándar 1 kg",
    "precio": 285,
    "seccion": "abarrotes",
    "presentacion": "Bulto con 10 piezas",
    "stock": 8,
    "marca": "Zulka"
  },
  {
    "nombre": "Café soluble 200 g",
    "precio": 1020,
    "seccion": "abarrotes",
    "presentacion": "Caja con 12 piezas",
    "stock": 15,
    "marca": "Nescafé Clásico"
  },
  {
    "nombre": "Arroz grano largo 1 kg",
    "precio": 245,
    "seccion": "abarrotes",
    "presentacion": "Paquete con 10 piezas",
    "stock": 32,
    "marca": "Verde Valle"
  },
  {
    "nombre": "Frijol negro 1 kg",
    "precio": 310,
    "seccion": "abarrotes",
    "presentacion": "Paquete con 10 piezas",
    "stock": 26,
    "marca": "La Sierra"
  },
  {
    "nombre": "Harina de trigo 1 kg",
    "precio": 232,
    "seccion": "abarrotes",
    "presentacion": "Paquete con 10 piezas",
    "stock": 18,
    "marca": "Tres Estrellas"
  },
  {
    "nombre": "Leche en polvo 1 kg",
    "precio": 1245,
    "seccion": "abarrotes",
    "presentacion": "Caja con 6 piezas",
    "stock": 9,
    "marca": "Nido"
  },
  {
    "nombre": "Refresco sabor cola 600 ml",
    "precio": 412,
    "seccion": "abarrotes",
    "presentacion": "Caja con 24 piezas",
    "stock": 45,
    "marca": "Coca-Cola"
  },
  {
    "nombre": "Galleta salada 200 g",
    "precio": 186,
    "seccion": "abarrotes",
    "presentacion": "Caja con 12 piezas",
    "stock": 38,
    "marca": "Saladitas"
  },
  {
    "nombre": "Atún en agua 140 g",
    "precio": 495,
    "seccion": "abarrotes",
    "presentacion": "Caja con 24 piezas",
    "stock": 21,
    "marca": "Dolores"
  },
  {
    "nombre": "Cloro 1 L",
    "precio": 186,
    "seccion": "limpieza",
    "presentacion": "Caja con 12 piezas",
    "stock": 40,
    "marca": "Cloralex"
  },
  {
    "nombre": "Detergente en polvo 1 kg",
    "precio": 528,
    "seccion": "limpieza",
    "presentacion": "Caja con 12 piezas",
    "stock": 6,
    "marca": "Ariel"
  },
  {
    "nombre": "Papel higiénico 4 rollos",
    "precio": 392,
    "seccion": "limpieza",
    "presentacion": "Paquete con 8 piezas",
    "stock": 30,
    "marca": "Pétalo"
  },
  {
    "nombre": "Jabón de barra 200 g",
    "precio": 214,
    "seccion": "limpieza",
    "presentacion": "Caja con 12 piezas",
    "stock": 34,
    "marca": "Zote"
  },
  {
    "nombre": "Limpiador multiusos 1 L",
    "precio": 268,
    "seccion": "limpieza",
    "presentacion": "Caja con 12 piezas",
    "stock": 22,
    "marca": "Pinol"
  },
  {
    "nombre": "Fibra para trastes (3 piezas)",
    "precio": 198,
    "seccion": "limpieza",
    "presentacion": "Paquete con 12 sets",
    "stock": 28,
    "marca": "Scotch-Brite"
  },
  {
    "nombre": "Bolsas para basura 60x70 cm",
    "precio": 142,
    "seccion": "limpieza",
    "presentacion": "Rollo con 20 piezas",
    "stock": 50,
    "marca": "Reyma"
  },
  {
    "nombre": "Gel antibacterial 1 L",
    "precio": 315,
    "seccion": "limpieza",
    "presentacion": "Caja con 6 piezas",
    "stock": 16,
    "marca": "Sani-T"
  },
  {
    "nombre": "Escoba de cerda suave",
    "precio": 288,
    "seccion": "limpieza",
    "presentacion": "Paquete con 6 piezas",
    "stock": 12,
    "marca": "Trapico"
  },
  {
    "nombre": "Servilletas 500 piezas",
    "precio": 236,
    "seccion": "limpieza",
    "presentacion": "Paquete con 6 piezas",
    "stock": 26,
    "marca": "Scribe"
  },
  {
    "nombre": "Tornillo pija 1\" punta broca",
    "precio": 78,
    "seccion": "herreria",
    "presentacion": "Caja con 100 piezas",
    "stock": 60,
    "marca": "Fischer"
  },
  {
    "nombre": "Martillo de uña 16 oz",
    "precio": 165,
    "seccion": "herreria",
    "presentacion": "Pieza",
    "stock": 12,
    "marca": "Truper"
  },
  {
    "nombre": "Disco de corte 4 1/2\"",
    "precio": 210,
    "seccion": "herreria",
    "presentacion": "Paquete con 10 piezas",
    "stock": 4,
    "marca": "Austromex"
  },
  {
    "nombre": "Perfil cuadrado 1\" x 6 m",
    "precio": 295,
    "seccion": "herreria",
    "presentacion": "Pieza",
    "stock": 18,
    "marca": "Deacero"
  },
  {
    "nombre": "Cinta métrica 5 m",
    "precio": 89,
    "seccion": "herreria",
    "presentacion": "Pieza",
    "stock": 25,
    "marca": "Stanley"
  },
  {
    "nombre": "Candado 50 mm",
    "precio": 149,
    "seccion": "herreria",
    "presentacion": "Paquete con 6 piezas",
    "stock": 30,
    "marca": "Yale"
  },
  {
    "nombre": "Electrodo 6013 3/32\"",
    "precio": 385,
    "seccion": "herreria",
    "presentacion": "Caja con 5 kg",
    "stock": 14,
    "marca": "Infra"
  },
  {
    "nombre": "Broca para concreto 1/2\"",
    "precio": 268,
    "seccion": "herreria",
    "presentacion": "Paquete con 5 piezas",
    "stock": 22,
    "marca": "Bosch"
  },
  {
    "nombre": "Guantes de carnaza",
    "precio": 312,
    "seccion": "herreria",
    "presentacion": "Paquete con 12 pares",
    "stock": 20,
    "marca": "Volteck"
  },
  {
    "nombre": "Pintura anticorrosiva 1 galón",
    "precio": 1240,
    "seccion": "herreria",
    "presentacion": "Caja con 4 piezas",
    "stock": 8,
    "marca": "Comex"
  }
];

const REPARTIDORES = [
  { name: 'Luis Ramírez', phone: '5512340001', vehicle: 'Camioneta 3.5 t', plate: 'ABC-123-A' },
  { name: 'Marco Antonio Silva', phone: '5512340002', vehicle: 'Moto de carga', plate: 'XYZ-987-B' },
];

async function main() {
  console.log('Sembrando tienda...\n');

  const deptos = {};
  for (const s of SECCIONES) {
    const existente = await prisma.department.findFirst({ where: { name: s.name } });
    deptos[s.key] = existente
      ? existente
      : await prisma.department.create({ data: { name: s.name, description: s.description } });
    console.log(`  sección ${s.name}: ${existente ? 'ya existía' : 'creada'}`);
  }

  let nuevos = 0;
  for (const p of PRODUCTOS) {
    const ya = await prisma.product.findFirst({ where: { name: p.nombre } });
    if (ya) continue;
    await prisma.product.create({
      data: {
        name: p.nombre,
        price: p.precio,
        stock: p.stock,
        minStock: 5,
        departmentId: deptos[p.seccion].id,
        active: true,
      },
    });
    nuevos++;
  }
  console.log(`  productos: ${nuevos} nuevos de ${PRODUCTOS.length}`);

  let rep = 0;
  for (const r of REPARTIDORES) {
    const ya = await prisma.driver.findFirst({ where: { name: r.name } });
    if (ya) continue;
    await prisma.driver.create({ data: r });
    rep++;
  }
  console.log(`  repartidores: ${rep} nuevos de ${REPARTIDORES.length}`);

  const [d, p, r] = await Promise.all([
    prisma.department.count(),
    prisma.product.count(),
    prisma.driver.count(),
  ]);
  console.log(`\nTotal en base: ${d} secciones, ${p} productos, ${r} repartidores`);
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => prisma.$disconnect());
