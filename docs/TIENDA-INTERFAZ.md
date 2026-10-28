# Interfaz del sitio público (`/tienda`)

Documento de la primera entrega del sitio de venta: **solo la interfaz
principal**, sin base de datos ni carrito persistente todavía.

## Por qué una ruta aparte

El POS ya ocupa `/` con el panel oscuro. El sitio público se monta en
`/tienda` con su propio layout para no mezclar dos sistemas visuales:
el panel es oscuro y denso (operación), la tienda es clara y aireada (venta).
Comparten repositorio y, más adelante, base de datos.

```
/                 → panel POS (login, dashboard, ventas)
/tienda           → sitio público  ← esta entrega
/tienda/abarrotes → (pendiente)
/tienda/limpieza  → (pendiente)
/tienda/herreria  → (pendiente)
```

## Decisiones de diseño

| Decisión | Valor | Motivo |
|---|---|---|
| Paleta | blanco cálido `#ffffff` / `#fbfaf7`, verde `#0e5134`, ámbar `#b07815` | regla 60/30/10: un solo acento de marca y el ámbar reservado a etiquetas |
| Tipografía | Inter autoalojada, 4 pesos | no depende de Google Fonts; 2 pesos visibles por pantalla |
| Radios | 16 px tarjetas, 10 px botones | consistente en todo el sitio |
| Cuadrícula | 3 columnas en escritorio, 2 en móvil | nunca 4 o 6, para no saturar |
| Secciones | 3 chips fijos en móvil, sin hamburguesa | las tres categorías son lo que vende |
| Animación | solo hover de tarjetas y aviso del carrito | respeta `prefers-reduced-motion` |

La hoja de estilos es CSS propio (no Tailwind) y todo vive bajo la clase
`.t-root`, para que el tema oscuro del POS no la afecte y viceversa.

## Archivos

```
src/app/(tienda)/
  layout.tsx              layout público (monta el shell)
  tienda.css              todos los estilos del sitio, aislados en .t-root
  tienda/page.tsx         portada: hero, secciones, destacados, cómo funciona, CTA
src/components/tienda/
  TiendaShell.tsx         header, footer, carrito (contexto) y aviso flotante
  Catalogo.tsx            cuadrícula filtrable de productos
src/lib/tienda-demo.ts    secciones y 9 productos de demostración
public/fonts/             Inter Regular / Medium / SemiBold / Bold
```

## Qué se ve

1. **Barra de aviso** — cobertura, pedido mínimo, factura.
2. **Hero** — titular, dos CTA (catálogo y WhatsApp) y tarjeta de
   seguimiento con mapa ilustrado: es el gancho del proyecto.
3. **Tres secciones** — abarrotes, limpieza, herrería, con conteo de productos.
4. **Destacados** — 9 productos con filtro por sección, presentación, precio
   en MXN, existencia (ámbar cuando quedan 8 o menos) y botón de agregar.
5. **Cómo funciona** — 3 pasos + 4 garantías (24–48 h, mayoreo, factura,
   producto verificado).
6. **CTA final** y **footer** con contacto, horarios y enlaces legales.

## Cómo correrlo

```bash
npm install          # Node 20 o superior (probado con 22.14)
npx prisma generate
npm run dev
```

Abrir <http://localhost:3000/tienda>.

Variables necesarias en `.env.local` (no se sube al repo):

```
AUTH_SECRET="..."
DATABASE_URL="file:./prisma/dev.db"
NEXT_PUBLIC_APP_URL="http://localhost:3000"
```

## Lo que falta (siguiente entrega)

- [ ] Páginas de las tres secciones con paginación y buscador real
- [ ] Ficha de producto (`/tienda/producto/[slug]`)
- [ ] Carrito persistente en base de datos
- [ ] Endpoint público `GET /api/tienda/productos` leyendo Prisma
- [ ] Mapa real con Leaflet y ubicaciones del repartidor
- [ ] Cuentas de comprador (rol nuevo junto a `ADMIN` y `CASHIER`)

## Publicación

El sitio estático vive en la rama `gh-pages` y se sirve por GitHub Pages:

**https://isairodriguez2025-hub.github.io/distribuidora-tienda/**

Para regenerarlo después de cambiar la portada:

```bash
npm run dev                                   # puerto 3000
python3 scripts/tienda-static-export.py       # genera dist-pages/
# publicar: copiar dist-pages/ a la rama gh-pages y hacer push
```

El script incrusta el CSS, elimina el JavaScript de Next y reinyecta un JS
mínimo para pestañas, carrito y buscador. No necesita Node en el servidor.

## Registro de cambios

Cada commit de esta entrega está documentado en su mensaje y en la sección
anterior; el orden es: estilos → datos → shell → catálogo → portada.
