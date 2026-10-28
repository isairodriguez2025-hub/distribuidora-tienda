export type Seccion = 'abarrotes' | 'limpieza' | 'herreria';

export type ProductoDemo = {
  id: string;
  nombre: string;
  presentacion: string;
  precio: number;
  stock: number;
  seccion: Seccion;
  etiqueta?: string;
};

export const SECCIONES: {
  id: Seccion;
  nombre: string;
  slug: string;
  descripcion: string;
  conteo: number;
}[] = [
  {
    id: 'abarrotes',
    nombre: 'Abarrotes',
    slug: '/tienda/abarrotes',
    descripcion: 'Consumo diario para tu tienda: aceite, azúcar, café, harina y más, por caja o bulto.',
    conteo: 148,
  },
  {
    id: 'limpieza',
    nombre: 'Limpieza',
    slug: '/tienda/limpieza',
    descripcion: 'Cloro, detergente, jabón, papel y desechables para surtir el anaquel completo.',
    conteo: 96,
  },
  {
    id: 'herreria',
    nombre: 'Herrería',
    slug: '/tienda/herreria',
    descripcion: 'Herramienta, tornillería, discos de corte y perfiles para el taller y la obra.',
    conteo: 84,
  },
];

export const PRODUCTOS_DESTACADOS: ProductoDemo[] = [
  {
    id: 'p1',
    nombre: 'Aceite vegetal comestible 1 L',
    presentacion: 'Caja con 12 piezas',
    precio: 438,
    stock: 24,
    seccion: 'abarrotes',
    etiqueta: 'Mayoreo',
  },
  {
    id: 'p2',
    nombre: 'Azúcar estándar 1 kg',
    presentacion: 'Bulto con 10 piezas',
    precio: 285,
    stock: 8,
    seccion: 'abarrotes',
  },
  {
    id: 'p3',
    nombre: 'Café soluble 200 g',
    presentacion: 'Caja con 12 piezas',
    precio: 1020,
    stock: 15,
    seccion: 'abarrotes',
    etiqueta: 'Más vendido',
  },
  {
    id: 'p4',
    nombre: 'Cloro 1 L',
    presentacion: 'Caja con 12 piezas',
    precio: 186,
    stock: 40,
    seccion: 'limpieza',
  },
  {
    id: 'p5',
    nombre: 'Detergente en polvo 1 kg',
    presentacion: 'Caja con 12 piezas',
    precio: 528,
    stock: 6,
    seccion: 'limpieza',
  },
  {
    id: 'p6',
    nombre: 'Papel higiénico 4 rollos',
    presentacion: 'Paquete con 8 piezas',
    precio: 392,
    stock: 30,
    seccion: 'limpieza',
    etiqueta: 'Mayoreo',
  },
  {
    id: 'p7',
    nombre: 'Tornillo pija 1" punta broca',
    presentacion: 'Caja con 100 piezas',
    precio: 78,
    stock: 60,
    seccion: 'herreria',
  },
  {
    id: 'p8',
    nombre: 'Martillo de uña 16 oz',
    presentacion: 'Pieza',
    precio: 165,
    stock: 12,
    seccion: 'herreria',
  },
  {
    id: 'p9',
    nombre: 'Disco de corte 4 1/2"',
    presentacion: 'Paquete con 10 piezas',
    precio: 210,
    stock: 4,
    seccion: 'herreria',
  },
];

export const mxn = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  minimumFractionDigits: 2,
});
