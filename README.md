# Didi2 B2B

Marketplace de materia prima, insumos y logística tripartita para México. Conecta tres tipos de cuenta —comercio, mayorista y repartidor— con una administración interna.

Aplicación **solo web**: Next.js 16 (App Router), React 19, Tailwind CSS 4, Prisma + SQLite y next-auth v5.

## Roles

| Rol | Qué puede hacer | Panel |
|---|---|---|
| Comercio | Ver catálogo, armar carrito, pedir, seguir su pedido | `/app/comercio` |
| Mayorista | Publicar productos, aceptar y preparar pedidos | `/app/mayorista` |
| Repartidor | Tomar viajes, marcar recolección y entrega | `/app/repartidor` |
| Administración | Verificar cuentas (KYC), supervisar pedidos y métricas | `/app/admin` |

## Puesta en marcha

```bash
npm install
npx prisma db push
node prisma/seed.mjs
npm run dev
```

Abre `http://localhost:3000`.

Copia `.env.example` a `.env` antes de arrancar y genera tu propio `AUTH_SECRET` con `openssl rand -base64 32`.

## Publicación

La instancia de demostración corre en:

- https://riz0308.tail0820b4.ts.net/didi2
- https://riz0308.tail0820b4.ts.net/tienda (alias)

Se sirve con `next start` detrás de Tailscale Funnel. Como Funnel recorta el prefijo antes de reenviar, `scripts/prefix-proxy.mjs` lo vuelve a anteponer y `NEXT_PUBLIC_BASE_PATH` mantiene las rutas y las llamadas a la API bajo `/didi2`.

## Cuentas de demostración

Contraseña para todas: `demo1234`

| Correo | Rol |
|---|---|
| comercio@didi2.mx | Comercio |
| mayorista@didi2.mx | Mayorista |
| repartidor@didi2.mx | Repartidor |
| admin@didi2.mx | Administración |

## Estructura

```
src/app/                 Páginas públicas (landing, registro, login)
src/app/app/             Paneles por rol (layout con sesión obligatoria)
src/app/api/             Registro, catálogo, pedidos, viajes, administración
src/components/          Interfaz compartida y componentes por panel
src/lib/                 Prisma, autenticación y catálogo de roles
prisma/                  Esquema y datos de demostración
docs/                    Revisión de pos-system y notas de arquitectura
```

## Pruebas

```bash
node tests/e2e.mjs                              # contra http://localhost:3100
BASE_URL=https://tu-dominio/didi2 node tests/e2e.mjs   # contra una instancia publicada
```

El guion recorre registro, inicio de sesión por rol, compra, aceptación del pedido, viaje del repartidor y aprobación de KYC, y guarda capturas en `~/.hermes/cache/scratch/didi2/`.
