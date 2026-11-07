/**
 * Seed de la tienda en línea (bloques 0 y 1 del documento de pasos).
 * Crea las 3 secciones, los 30 productos del catálogo y 2 repartidores.
 * Idempotente: se puede correr varias veces sin duplicar.
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
    "slug": "aceite-vegetal-comestible-1-l",
    "nombre": "Aceite vegetal comestible 1 L",
    "marca": "Nutrioli",
    "presentacion": "Caja con 12 piezas",
    "precio": 438,
    "stock": 24,
    "seccion": "abarrotes",
    "etiqueta": "Mayoreo",
    "descripcion": "Aceite de soya refinado para cocina diaria. La presentación de caja cerrada es la que más rota en tienda de barrio.",
    "incluye": [
      "12 botellas de 1 L",
      "Empaque de cartón reforzado",
      "Caducidad mínima de 12 meses"
    ]
  },
  {
    "slug": "azucar-estandar-1-kg",
    "nombre": "Azúcar estándar 1 kg",
    "marca": "Zulka",
    "presentacion": "Bulto con 10 piezas",
    "precio": 285,
    "stock": 8,
    "seccion": "abarrotes",
    "etiqueta": "",
    "descripcion": "Azúcar refinada de caña en bolsa de kilo. Se vende por bulto para no rebanar el margen pieza por pieza.",
    "incluye": [
      "10 bolsas de 1 kg",
      "Bulto de polipropileno",
      "Sin colorantes"
    ]
  },
  {
    "slug": "cafe-soluble-200-g",
    "nombre": "Café soluble 200 g",
    "marca": "Nescafé Clásico",
    "presentacion": "Caja con 12 piezas",
    "precio": 1020,
    "stock": 15,
    "seccion": "abarrotes",
    "etiqueta": "Más vendido",
    "descripcion": "Café soluble de frasco de 200 g. El producto de mayor rotación en la categoría de abarrotes.",
    "incluye": [
      "12 frascos de 200 g",
      "Tapa de rosca",
      "Rendimiento aproximado de 100 tazas por frasco"
    ]
  },
  {
    "slug": "arroz-grano-largo-1-kg",
    "nombre": "Arroz grano largo 1 kg",
    "marca": "Verde Valle",
    "presentacion": "Paquete con 10 piezas",
    "precio": 245,
    "stock": 32,
    "seccion": "abarrotes",
    "etiqueta": "",
    "descripcion": "Arroz de grano largo, limpio y seleccionado. Base de la despensa, rotación alta.",
    "incluye": [
      "10 bolsas de 1 kg",
      "Grano entero",
      "Cocción en 18 minutos"
    ]
  },
  {
    "slug": "frijol-negro-1-kg",
    "nombre": "Frijol negro 1 kg",
    "marca": "La Sierra",
    "presentacion": "Paquete con 10 piezas",
    "precio": 310,
    "stock": 26,
    "seccion": "abarrotes",
    "etiqueta": "",
    "descripcion": "Frijol negro seleccionado, sin piedra. Se surte por paquete cerrado.",
    "incluye": [
      "10 bolsas de 1 kg",
      "Selección mecánica",
      "Origen nacional"
    ]
  },
  {
    "slug": "harina-de-trigo-1-kg",
    "nombre": "Harina de trigo 1 kg",
    "marca": "Tres Estrellas",
    "presentacion": "Paquete con 10 piezas",
    "precio": 232,
    "stock": 18,
    "seccion": "abarrotes",
    "etiqueta": "",
    "descripcion": "Harina de trigo para repostería y cocina general.",
    "incluye": [
      "10 bolsas de 1 kg",
      "Fortificada con hierro y zinc",
      "Uso casero y comercial"
    ]
  },
  {
    "slug": "leche-en-polvo-1-kg",
    "nombre": "Leche en polvo 1 kg",
    "marca": "Nido",
    "presentacion": "Caja con 6 piezas",
    "precio": 1245,
    "stock": 9,
    "seccion": "abarrotes",
    "etiqueta": "",
    "descripcion": "Leche entera en polvo, presentación de kilo. Alta rotación en colonias sin leche fresca.",
    "incluye": [
      "6 latas de 1 kg",
      "Leche entera",
      "Fortificada con vitaminas"
    ]
  },
  {
    "slug": "refresco-sabor-cola-600-ml",
    "nombre": "Refresco sabor cola 600 ml",
    "marca": "Coca-Cola",
    "presentacion": "Caja con 24 piezas",
    "precio": 412,
    "stock": 45,
    "seccion": "abarrotes",
    "etiqueta": "Mayoreo",
    "descripcion": "Caja cerrada de refresco de 600 ml, la presentación que más gira en tienda.",
    "incluye": [
      "24 botellas de 600 ml",
      "Empaque retornable no incluido",
      "Entrega en caja cerrada"
    ]
  },
  {
    "slug": "galleta-salada-200-g",
    "nombre": "Galleta salada 200 g",
    "marca": "Saladitas",
    "presentacion": "Caja con 12 piezas",
    "precio": 186,
    "stock": 38,
    "seccion": "abarrotes",
    "etiqueta": "",
    "descripcion": "Galleta salada en bolsa de 200 g. Complemento de anaquel junto a refrescos.",
    "incluye": [
      "12 bolsas de 200 g",
      "Producto de alta rotación",
      "Fecha mínima de 6 meses"
    ]
  },
  {
    "slug": "atun-en-agua-140-g",
    "nombre": "Atún en agua 140 g",
    "marca": "Dolores",
    "presentacion": "Caja con 24 piezas",
    "precio": 495,
    "stock": 21,
    "seccion": "abarrotes",
    "etiqueta": "",
    "descripcion": "Atún en agua, lata de 140 g. Producto de despensa con demanda estable todo el año.",
    "incluye": [
      "24 latas de 140 g",
      "Lomo de atún",
      "Abre fácil"
    ]
  },
  {
    "slug": "cloro-1-l",
    "nombre": "Cloro 1 L",
    "marca": "Cloralex",
    "presentacion": "Caja con 12 piezas",
    "precio": 186,
    "stock": 40,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Cloro de uso doméstico e industrial. La presentación de caja cerrada evita mermas por manejo.",
    "incluye": [
      "12 botellas de 1 L",
      "Concentración 6%",
      "Tapa de seguridad"
    ]
  },
  {
    "slug": "detergente-en-polvo-1-kg",
    "nombre": "Detergente en polvo 1 kg",
    "marca": "Ariel",
    "presentacion": "Caja con 12 piezas",
    "precio": 528,
    "stock": 6,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Detergente en polvo para lavado manual y de máquina.",
    "incluye": [
      "12 bolsas de 1 kg",
      "Rinde 40 lavadas",
      "Aroma floral"
    ]
  },
  {
    "slug": "papel-higienico-4-rollos",
    "nombre": "Papel higiénico 4 rollos",
    "marca": "Pétalo",
    "presentacion": "Paquete con 8 piezas",
    "precio": 392,
    "stock": 30,
    "seccion": "limpieza",
    "etiqueta": "Mayoreo",
    "descripcion": "Paquete de 4 rollos doble hoja. Se vende en mazo de 8 paquetes.",
    "incluye": [
      "8 paquetes de 4 rollos",
      "Doble hoja",
      "Papel suave"
    ]
  },
  {
    "slug": "jabon-de-barra-200-g",
    "nombre": "Jabón de barra 200 g",
    "marca": "Zote",
    "presentacion": "Caja con 12 piezas",
    "precio": 214,
    "stock": 34,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Jabón de barra para lavado de ropa y uso general.",
    "incluye": [
      "12 barras de 200 g",
      "Rinde para ropa y trastes",
      "Producto económico"
    ]
  },
  {
    "slug": "limpiador-multiusos-1-l",
    "nombre": "Limpiador multiusos 1 L",
    "marca": "Pinol",
    "presentacion": "Caja con 12 piezas",
    "precio": 268,
    "stock": 22,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Limpiador desinfectante para pisos y superficies.",
    "incluye": [
      "12 botellas de 1 L",
      "Aroma pino",
      "Acción desinfectante"
    ]
  },
  {
    "slug": "fibra-para-trastes-3-piezas",
    "nombre": "Fibra para trastes (3 piezas)",
    "marca": "Scotch-Brite",
    "presentacion": "Paquete con 12 sets",
    "precio": 198,
    "stock": 28,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Fibra abrasiva para lavado de trastes, presentación de tres piezas.",
    "incluye": [
      "12 sets de 3 piezas",
      "Doble cara",
      "No raya superficies"
    ]
  },
  {
    "slug": "bolsas-para-basura-60x70-cm",
    "nombre": "Bolsas para basura 60x70 cm",
    "marca": "Reyma",
    "presentacion": "Rollo con 20 piezas",
    "precio": 142,
    "stock": 50,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Bolsa negra reforzada para basura de negocio.",
    "incluye": [
      "20 bolsas por rollo",
      "Calibre reforzado",
      "Capacidad 30 L"
    ]
  },
  {
    "slug": "gel-antibacterial-1-l",
    "nombre": "Gel antibacterial 1 L",
    "marca": "Sani-T",
    "presentacion": "Caja con 6 piezas",
    "precio": 315,
    "stock": 16,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Gel antibacterial con dispensador, para mostrador y área de caja.",
    "incluye": [
      "6 botellas de 1 L",
      "Dispensador incluido",
      "70% alcohol"
    ]
  },
  {
    "slug": "escoba-de-cerda-suave",
    "nombre": "Escoba de cerda suave",
    "marca": "Trapico",
    "presentacion": "Paquete con 6 piezas",
    "precio": 288,
    "stock": 12,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Escoba de cerda para barrido fino en piso de negocio.",
    "incluye": [
      "6 escobas",
      "Mango roscado incluido",
      "Cerda de PET"
    ]
  },
  {
    "slug": "servilletas-500-piezas",
    "nombre": "Servilletas 500 piezas",
    "marca": "Scribe",
    "presentacion": "Paquete con 6 piezas",
    "precio": 236,
    "stock": 26,
    "seccion": "limpieza",
    "etiqueta": "",
    "descripcion": "Servilleta blanca de una hoja, paquete de 500 piezas.",
    "incluye": [
      "6 paquetes de 500 piezas",
      "Una hoja",
      "Corte cuadrado"
    ]
  },
  {
    "slug": "tornillo-pija-1-punta-broca",
    "nombre": "Tornillo pija 1\" punta broca",
    "marca": "Fischer",
    "presentacion": "Caja con 100 piezas",
    "precio": 78,
    "stock": 60,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Pija autotaladrante para lámina y madera. La caja de 100 es la unidad de compra del taller.",
    "incluye": [
      "100 piezas de 1\"",
      "Punta broca",
      "Cabeza hexagonal"
    ]
  },
  {
    "slug": "martillo-de-una-16-oz",
    "nombre": "Martillo de uña 16 oz",
    "marca": "Truper",
    "presentacion": "Pieza",
    "precio": 165,
    "stock": 12,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Martillo de uña con mango de fibra de vidrio, uso profesional.",
    "incluye": [
      "1 martillo 16 oz",
      "Mango ergonómico",
      "Cabeza de acero forjado"
    ]
  },
  {
    "slug": "disco-de-corte-4-1-2",
    "nombre": "Disco de corte 4 1/2\"",
    "marca": "Austromex",
    "presentacion": "Paquete con 10 piezas",
    "precio": 210,
    "stock": 4,
    "seccion": "herreria",
    "etiqueta": "Mayoreo",
    "descripcion": "Disco de corte para metal, grano 60. Consumible de alta rotación en obra.",
    "incluye": [
      "10 discos de 4 1/2\"",
      "Para esmeril angular",
      "Espesor 1.2 mm"
    ]
  },
  {
    "slug": "perfil-cuadrado-1-x-6-m",
    "nombre": "Perfil cuadrado 1\" x 6 m",
    "marca": "Deacero",
    "presentacion": "Pieza",
    "precio": 295,
    "stock": 18,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Perfil cuadrado de acero para herrería y estructuras ligeras.",
    "incluye": [
      "Tramo de 6 metros",
      "Calibre 14",
      "Acero al carbón"
    ]
  },
  {
    "slug": "cinta-metrica-5-m",
    "nombre": "Cinta métrica 5 m",
    "marca": "Stanley",
    "presentacion": "Pieza",
    "precio": 89,
    "stock": 25,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Cinta de 5 metros con seguro y gancho magnético.",
    "incluye": [
      "1 cinta de 5 m",
      "Carcasa reforzada",
      "Escala en cm y pulgadas"
    ]
  },
  {
    "slug": "candado-50-mm",
    "nombre": "Candado 50 mm",
    "marca": "Yale",
    "presentacion": "Paquete con 6 piezas",
    "precio": 149,
    "stock": 30,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Candado de latón con arco templado, para cortina y bodega.",
    "incluye": [
      "6 candados de 50 mm",
      "3 llaves cada uno",
      "Arco templado"
    ]
  },
  {
    "slug": "electrodo-6013-3-32",
    "nombre": "Electrodo 6013 3/32\"",
    "marca": "Infra",
    "presentacion": "Caja con 5 kg",
    "precio": 385,
    "stock": 14,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Electrodo para soldadura de acero al carbón.",
    "incluye": [
      "Caja de 5 kg",
      "Calibre 3/32\"",
      "Uso general en herrería"
    ]
  },
  {
    "slug": "broca-para-concreto-1-2",
    "nombre": "Broca para concreto 1/2\"",
    "marca": "Bosch",
    "presentacion": "Paquete con 5 piezas",
    "precio": 268,
    "stock": 22,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Broca SDS para concreto y mampostería.",
    "incluye": [
      "5 brocas de 1/2\"",
      "Punta de carburo",
      "Encaje SDS Plus"
    ]
  },
  {
    "slug": "guantes-de-carnaza",
    "nombre": "Guantes de carnaza",
    "marca": "Volteck",
    "presentacion": "Paquete con 12 pares",
    "precio": 312,
    "stock": 20,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Guante de carnaza para soldadura y manejo de material pesado.",
    "incluye": [
      "12 pares",
      "Carnaza reforzada",
      "Puño de resorte"
    ]
  },
  {
    "slug": "pintura-anticorrosiva-1-galon",
    "nombre": "Pintura anticorrosiva 1 galón",
    "marca": "Comex",
    "presentacion": "Caja con 4 piezas",
    "precio": 1240,
    "stock": 8,
    "seccion": "herreria",
    "etiqueta": "",
    "descripcion": "Esmalte anticorrosivo para estructuras metálicas.",
    "incluye": [
      "4 galones de 1 gal",
      "Acabado satinado",
      "Secado en 4 horas"
    ]
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

  let nuevos = 0, actualizados = 0;
  for (const p of PRODUCTOS) {
    const datos = {
      name: p.nombre,
      slug: p.slug,
      brand: p.marca,
      presentation: p.presentacion,
      description: p.descripcion,
      includes: JSON.stringify(p.incluye),
      label: p.etiqueta,
      price: p.precio,
      stock: p.stock,
      minStock: 5,
      departmentId: deptos[p.seccion].id,
      active: true,
    };
    const ya = await prisma.product.findFirst({ where: { name: p.nombre } });
    if (ya) {
      await prisma.product.update({ where: { id: ya.id }, data: datos });
      actualizados++;
    } else {
      await prisma.product.create({ data: datos });
      nuevos++;
    }
  }
  console.log(`  productos: ${nuevos} nuevos, ${actualizados} actualizados`);

  let rep = 0;
  for (const r of REPARTIDORES) {
    const ya = await prisma.driver.findFirst({ where: { name: r.name } });
    if (ya) continue;
    await prisma.driver.create({ data: r });
    rep++;
  }
  console.log(`  repartidores: ${rep} nuevos`);

  const sinSlug = await prisma.product.count({ where: { slug: '' } });
  const [d, pr, r] = await Promise.all([
    prisma.department.count(), prisma.product.count(), prisma.driver.count(),
  ]);
  console.log(`\nTotal: ${d} secciones, ${pr} productos, ${r} repartidores`);
  console.log(`Productos sin slug: ${sinSlug} ${sinSlug === 0 ? '(correcto)' : '(ERROR)'}`);
  if (sinSlug > 0) process.exitCode = 1;
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => prisma.$disconnect());
