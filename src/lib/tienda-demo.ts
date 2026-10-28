export type Seccion = 'abarrotes' | 'limpieza' | 'herreria';

export type Producto = {
  id: string;
  slug: string;
  nombre: string;
  marca: string;
  presentacion: string;
  precio: number;
  stock: number;
  seccion: Seccion;
  etiqueta?: string;
  descripcion: string;
  incluye: string[];
};

export const SECCIONES: {
  id: Seccion;
  nombre: string;
  slug: string;
  titulo: string;
  descripcion: string;
  descripcionLarga: string;
  conteo: number;
}[] = [
  {
    id: 'abarrotes',
    nombre: 'Abarrotes',
    slug: 'abarrotes',
    titulo: 'Abarrotes para tu tienda',
    descripcion:
      'Consumo diario para tu tienda: aceite, azúcar, café, harina y más, por caja o bulto.',
    descripcionLarga:
      'Lo que se mueve todos los días en el anaquel. Precios por caja o bulto, con existencia real y entrega en 24 a 48 horas.',
    conteo: 148,
  },
  {
    id: 'limpieza',
    nombre: 'Limpieza',
    slug: 'limpieza',
    titulo: 'Productos de limpieza',
    descripcion:
      'Cloro, detergente, jabón, papel y desechables para surtir el anaquel completo.',
    descripcionLarga:
      'Línea completa para el hogar y para el negocio: cloro, detergentes, jabones, papel y desechables por caja cerrada.',
    conteo: 96,
  },
  {
    id: 'herreria',
    nombre: 'Herrería',
    slug: 'herreria',
    titulo: 'Herrería y ferretería',
    descripcion:
      'Herramienta, tornillería, discos de corte y perfiles para el taller y la obra.',
    descripcionLarga:
      'Herramienta de mano, consumibles y perfiles. Pensado para talleres, herreros y cuadrillas de obra que compran por volumen.',
    conteo: 84,
  },
];

const BASE: Omit<Producto, 'id' | 'slug'>[] = [
  // ------------------------------- ABARROTES -------------------------------
  {
    nombre: 'Aceite vegetal comestible 1 L',
    marca: 'Nutrioli',
    presentacion: 'Caja con 12 piezas',
    precio: 438,
    stock: 24,
    seccion: 'abarrotes',
    etiqueta: 'Mayoreo',
    descripcion:
      'Aceite de soya refinado para cocina diaria. La presentación de caja cerrada es la que más rota en tienda de barrio.',
    incluye: ['12 botellas de 1 L', 'Empaque de cartón reforzado', 'Caducidad mínima de 12 meses'],
  },
  {
    nombre: 'Azúcar estándar 1 kg',
    marca: 'Zulka',
    presentacion: 'Bulto con 10 piezas',
    precio: 285,
    stock: 8,
    seccion: 'abarrotes',
    descripcion:
      'Azúcar refinada de caña en bolsa de kilo. Se vende por bulto para no rebanar el margen pieza por pieza.',
    incluye: ['10 bolsas de 1 kg', 'Bulto de polipropileno', 'Sin colorantes'],
  },
  {
    nombre: 'Café soluble 200 g',
    marca: 'Nescafé Clásico',
    presentacion: 'Caja con 12 piezas',
    precio: 1020,
    stock: 15,
    seccion: 'abarrotes',
    etiqueta: 'Más vendido',
    descripcion:
      'Café soluble de frasco de 200 g. El producto de mayor rotación en la categoría de abarrotes.',
    incluye: ['12 frascos de 200 g', 'Tapa de rosca', 'Rendimiento aproximado de 100 tazas por frasco'],
  },
  {
    nombre: 'Arroz grano largo 1 kg',
    marca: 'Verde Valle',
    presentacion: 'Paquete con 10 piezas',
    precio: 245,
    stock: 32,
    seccion: 'abarrotes',
    descripcion: 'Arroz de grano largo, limpio y seleccionado. Base de la despensa, rotación alta.',
    incluye: ['10 bolsas de 1 kg', 'Grano entero', 'Cocción en 18 minutos'],
  },
  {
    nombre: 'Frijol negro 1 kg',
    marca: 'La Sierra',
    presentacion: 'Paquete con 10 piezas',
    precio: 310,
    stock: 26,
    seccion: 'abarrotes',
    descripcion: 'Frijol negro seleccionado, sin piedra. Se surte por paquete cerrado.',
    incluye: ['10 bolsas de 1 kg', 'Selección mecánica', 'Origen nacional'],
  },
  {
    nombre: 'Harina de trigo 1 kg',
    marca: 'Tres Estrellas',
    presentacion: 'Paquete con 10 piezas',
    precio: 232,
    stock: 18,
    seccion: 'abarrotes',
    descripcion: 'Harina de trigo para repostería y cocina general.',
    incluye: ['10 bolsas de 1 kg', 'Fortificada con hierro y zinc', 'Uso casero y comercial'],
  },
  {
    nombre: 'Leche en polvo 1 kg',
    marca: 'Nido',
    presentacion: 'Caja con 6 piezas',
    precio: 1245,
    stock: 9,
    seccion: 'abarrotes',
    descripcion: 'Leche entera en polvo, presentación de kilo. Alta rotación en colonias sin leche fresca.',
    incluye: ['6 latas de 1 kg', 'Leche entera', 'Fortificada con vitaminas'],
  },
  {
    nombre: 'Refresco sabor cola 600 ml',
    marca: 'Coca-Cola',
    presentacion: 'Caja con 24 piezas',
    precio: 412,
    stock: 45,
    seccion: 'abarrotes',
    etiqueta: 'Mayoreo',
    descripcion: 'Caja cerrada de refresco de 600 ml, la presentación que más gira en tienda.',
    incluye: ['24 botellas de 600 ml', 'Empaque retornable no incluido', 'Entrega en caja cerrada'],
  },
  {
    nombre: 'Galleta salada 200 g',
    marca: 'Saladitas',
    presentacion: 'Caja con 12 piezas',
    precio: 186,
    stock: 38,
    seccion: 'abarrotes',
    descripcion: 'Galleta salada en bolsa de 200 g. Complemento de anaquel junto a refrescos.',
    incluye: ['12 bolsas de 200 g', 'Producto de alta rotación', 'Fecha mínima de 6 meses'],
  },
  {
    nombre: 'Atún en agua 140 g',
    marca: 'Dolores',
    presentacion: 'Caja con 24 piezas',
    precio: 495,
    stock: 21,
    seccion: 'abarrotes',
    descripcion: 'Atún en agua, lata de 140 g. Producto de despensa con demanda estable todo el año.',
    incluye: ['24 latas de 140 g', 'Lomo de atún', 'Abre fácil'],
  },

  // -------------------------------- LIMPIEZA -------------------------------
  {
    nombre: 'Cloro 1 L',
    marca: 'Cloralex',
    presentacion: 'Caja con 12 piezas',
    precio: 186,
    stock: 40,
    seccion: 'limpieza',
    descripcion:
      'Cloro de uso doméstico e industrial. La presentación de caja cerrada evita mermas por manejo.',
    incluye: ['12 botellas de 1 L', 'Concentración 6%', 'Tapa de seguridad'],
  },
  {
    nombre: 'Detergente en polvo 1 kg',
    marca: 'Ariel',
    presentacion: 'Caja con 12 piezas',
    precio: 528,
    stock: 6,
    seccion: 'limpieza',
    descripcion: 'Detergente en polvo para lavado manual y de máquina.',
    incluye: ['12 bolsas de 1 kg', 'Rinde 40 lavadas', 'Aroma floral'],
  },
  {
    nombre: 'Papel higiénico 4 rollos',
    marca: 'Petalo',
    presentacion: 'Paquete con 8 piezas',
    precio: 392,
    stock: 30,
    seccion: 'limpieza',
    etiqueta: 'Mayoreo',
    descripcion: 'Paquete de 4 rollos doble hoja. Se vende en mazo de 8 paquetes.',
    incluye: ['8 paquetes de 4 rollos', 'Doble hoja', 'Papel suave'],
  },
  {
    nombre: 'Jabón de barra 200 g',
    marca: 'Zote',
    presentacion: 'Caja con 12 piezas',
    precio: 214,
    stock: 34,
    seccion: 'limpieza',
    descripcion: 'Jabón de barra para lavado de ropa y uso general.',
    incluye: ['12 barras de 200 g', 'Rinde para ropa y trastes', 'Producto económico'],
  },
  {
    nombre: 'Limpiador multiusos 1 L',
    marca: 'Pinol',
    presentacion: 'Caja con 12 piezas',
    precio: 268,
    stock: 22,
    seccion: 'limpieza',
    descripcion: 'Limpiador desinfectante para pisos y superficies.',
    incluye: ['12 botellas de 1 L', 'Aroma pino', 'Action desinfectante'],
  },
  {
    nombre: 'Fibra para trastes (3 piezas)',
    marca: 'Scotch-Brite',
    presentacion: 'Paquete con 12 sets',
    precio: 198,
    stock: 28,
    seccion: 'limpieza',
    descripcion: 'Fibra abrasiva para lavado de trastes, presentación de tres piezas.',
    incluye: ['12 sets de 3 piezas', 'Doble cara', 'No raya superficies'],
  },
  {
    nombre: 'Bolsas para basura 60x70 cm',
    marca: 'Reyma',
    presentacion: 'Rollo con 20 piezas',
    precio: 142,
    stock: 50,
    seccion: 'limpieza',
    descripcion: 'Bolsa negra reforzada para basura de negocio.',
    incluye: ['20 bolsas por rollo', 'Calibre reforzado', 'Capacidad 30 L'],
  },
  {
    nombre: 'Gel antibacterial 1 L',
    marca: 'Sani-T',
    presentacion: 'Caja con 6 piezas',
    precio: 315,
    stock: 16,
    seccion: 'limpieza',
    descripcion: 'Gel antibacterial con dispensador, para mostrador y área de caja.',
    incluye: ['6 botellas de 1 L', 'Dispensador incluido', '70% alcohol'],
  },
  {
    nombre: 'Escoba de cerda suave',
    marca: 'Trapico',
    presentacion: 'Paquete con 6 piezas',
    precio: 288,
    stock: 12,
    seccion: 'limpieza',
    descripcion: 'Escoba de cerda para barrido fino en piso de negocio.',
    incluye: ['6 escobas', 'Mango roscado incluido', 'Cerda de PET'],
  },
  {
    nombre: 'Servilletas 500 piezas',
    marca: 'Scribe',
    presentacion: 'Paquete con 6 piezas',
    precio: 236,
    stock: 26,
    seccion: 'limpieza',
    descripcion: 'Servilleta blanca de una hoja, paquete de 500 piezas.',
    incluye: ['6 paquetes de 500 piezas', 'Una hoja', 'Corte cuadrado'],
  },

  // -------------------------------- HERRERÍA -------------------------------
  {
    nombre: 'Tornillo pija 1" punta broca',
    marca: 'Fischer',
    presentacion: 'Caja con 100 piezas',
    precio: 78,
    stock: 60,
    seccion: 'herreria',
    descripcion:
      'Pija autotaladrante para lámina y madera. La caja de 100 es la unidad de compra del taller.',
    incluye: ['100 piezas de 1"', 'Punta broca', 'Cabeza hexagonal'],
  },
  {
    nombre: 'Martillo de uña 16 oz',
    marca: 'Truper',
    presentacion: 'Pieza',
    precio: 165,
    stock: 12,
    seccion: 'herreria',
    descripcion: 'Martillo de uña con mango de fibra de vidrio, uso profesional.',
    incluye: ['1 martillo 16 oz', 'Mango ergonómico', 'Cabeza de acero forjado'],
  },
  {
    nombre: 'Disco de corte 4 1/2"',
    marca: 'Austromex',
    presentacion: 'Paquete con 10 piezas',
    precio: 210,
    stock: 4,
    seccion: 'herreria',
    etiqueta: 'Mayoreo',
    descripcion: 'Disco de corte para metal, grano 60. Consumible de alta rotación en obra.',
    incluye: ['10 discos de 4 1/2"', 'Para esmeril angular', 'Espesor 1.2 mm'],
  },
  {
    nombre: 'Perfil cuadrado 1" x 6 m',
    marca: 'Deacero',
    presentacion: 'Pieza',
    precio: 295,
    stock: 18,
    seccion: 'herreria',
    descripcion: 'Perfil cuadrado de acero para herrería y estructuras ligeras.',
    incluye: ['Tramo de 6 metros', 'Calibre 14', 'Acero al carbón'],
  },
  {
    nombre: 'Cinta métrica 5 m',
    marca: 'Stanley',
    presentacion: 'Pieza',
    precio: 89,
    stock: 25,
    seccion: 'herreria',
    descripcion: 'Cinta de 5 metros con seguro y gancho magnético.',
    incluye: ['1 cinta de 5 m', 'Carcasa reforzada', 'Escala en cm y pulgadas'],
  },
  {
    nombre: 'Candado 50 mm',
    marca: 'Yale',
    presentacion: 'Paquete con 6 piezas',
    precio: 149,
    stock: 30,
    seccion: 'herreria',
    descripcion: 'Candado de latón con arco templado, para cortina y bodega.',
    incluye: ['6 candados de 50 mm', '3 llaves cada uno', 'Arco templado'],
  },
  {
    nombre: 'Electrodo 6013 3/32"',
    marca: 'Infra',
    presentacion: 'Caja con 5 kg',
    precio: 385,
    stock: 14,
    seccion: 'herreria',
    descripcion: 'Electrodo para soldadura de acero al carbón.',
    incluye: ['Caja de 5 kg', 'Calibre 3/32"', 'Uso general en herrería'],
  },
  {
    nombre: 'Broca para concreto 1/2"',
    marca: 'Bosch',
    presentacion: 'Paquete con 5 piezas',
    precio: 268,
    stock: 22,
    seccion: 'herreria',
    descripcion: 'Broca SDS para concreto y mampostería.',
    incluye: ['5 brocas de 1/2"', 'Punta de carburo', 'Encaje SDS Plus'],
  },
  {
    nombre: 'Guantes de carnaza',
    marca: 'Volteck',
    presentacion: 'Paquete con 12 pares',
    precio: 312,
    stock: 20,
    seccion: 'herreria',
    descripcion: 'Guante de carnaza para soldadura y manejo de material pesado.',
    incluye: ['12 pares', 'Carnaza reforzada', 'Puño de resorte'],
  },
  {
    nombre: 'Pintura anticorrosiva 1 galón',
    marca: 'Comex',
    presentacion: 'Caja con 4 piezas',
    precio: 1240,
    stock: 8,
    seccion: 'herreria',
    descripcion: 'Esmalte anticorrosivo para estructuras metálicas.',
    incluye: ['4 galones de 1 gal', 'Acabado satinado', 'Secado en 4 horas'],
  },
];

/** Prefijo estable para las URLs de producto. */
const slugify = (s: string) =>
  s
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[°º]/g, '')
    .replace(/["']/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60);

export const PRODUCTOS: Producto[] = BASE.map((p, i) => ({
  ...p,
  id: `p${i + 1}`,
  slug: slugify(p.nombre),
}));

export const porSeccion = (seccion: Seccion) => PRODUCTOS.filter((p) => p.seccion === seccion);

export const buscar = (slug: string) => PRODUCTOS.find((p) => p.slug === slug);

export const SECCION_POR_SLUG = (slug: string) => SECCIONES.find((s) => s.slug === slug);

/** Los que salen en la portada (uno o dos por sección, los marcados). */
export const DESTACADOS = [
  ...porSeccion('abarrotes').slice(0, 3),
  ...porSeccion('limpieza').slice(0, 3),
  ...porSeccion('herreria').slice(0, 3),
];

export const mxn = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  minimumFractionDigits: 2,
});
