# Revisión de `pos-system` como base para Didi2 B2B

Fecha: 5 de noviembre de 2026
Revisado: `/home/rizutov/pos-system` (v8.0.0)
Destino: `/home/rizutov/didi2-b2b`

---

## 1. Qué es hoy `pos-system`

| Dato | Valor |
|---|---|
| Stack | Next.js 16.2.6, React 19, Prisma 5 + SQLite, next-auth v5 (beta), Tailwind 4 |
| Tamaño | 31 852 líneas en `src/`, 23 páginas, 87 rutas de API, 29 componentes |
| Modelo de datos | 27 modelos (ventas, compras, inventario, caja, clientes, proveedores, auditoría) |
| Autenticación | Credenciales con `bcrypt-ts`, sesión JWT, rol único en `User.role` |
| Escritorio | Electron 35 + electron-builder + NSIS + auto-updater + descubrimiento UDP en LAN |
| Estado | App web funcional (auth, productos, ventas, reportes) y empaquetado de escritorio a medio terminar |

Fortalezas reales: la capa de autenticación, el patrón de rutas de API con validación Zod, el esquema Prisma con auditoría y el conjunto de componentes de interfaz ya resuelven el 60 % del trabajo "aburrido" de un sistema de operación.

---

## 2. Lo que se reutiliza

| Pieza de `pos-system` | Uso en Didi2 | Cómo se trasladó |
|---|---|---|
| `src/lib/auth.ts` (Credentials + JWT + `role` en token) | Login único para los cuatro roles | Copiado y adaptado: el identificador pasa de `username` a `email` |
| `src/lib/db.ts` (singleton de Prisma) | Acceso a datos | Idéntico |
| Patrón `route.ts` + Zod + `NextResponse` | Todas las rutas nuevas | Mismo estilo, con verificación de rol en cada handler |
| `bcrypt-ts` para hash | Registro de usuarios | Igual, costo 10 |
| Componentes de UI (botones, campos, tarjetas, badges) | Interfaz de los paneles | Reescritos con los tokens del sitio de referencia |
| `logAudit` | Trazabilidad de acciones | Pendiente de portar (fase 2) |
| Esquema de ventas (`Sale`, `SaleItem`) | Base conceptual de `Order` / `OrderItem` | Simplificado a pedidos de marketplace |

---

## 3. Lo que se descarta

| Pieza | Motivo |
|---|---|
| `electron/` completo (main, preload, updater, tailscale, nsis, setup.html, loading.html) | La aplicación es solo web; no hay instalador ni proceso de escritorio |
| `electron-builder`, NSIS, `electron-updater`, `electron-store`, `electron-log` | Dependencias exclusivas del empaquetado |
| Descubrimiento UDP y modo cliente/servidor en LAN | La web se sirve desde un solo origen; el multiusuario se resuelve con sesiones |
| `install.ps1`, `install.bat`, `run-pos.sh`, `deploy.sh` de escritorio | Reemplazados por despliegue web |
| Impresión directa a impresora térmica | En web se resuelve con `window.print()` o PDF; no se requiere acceso al puerto |
| `server.js` (servidor Express auxiliar) | Next.js cubre las rutas necesarias |

Nada de esto se borró del proyecto original: `pos-system` queda intacto. La nueva app simplemente no lo arrastra.

---

## 4. Brechas de `pos-system` frente a Didi2

| Brecha | Impacto | Cómo se resolvió en `didi2-b2b` |
|---|---|---|
| Un solo rol por usuario, sin perfiles | No se puede ser comercio y mayorista a la vez | `User.role` con cuatro valores y perfil en la misma fila; el multi-rol queda para fase 2 con tabla intermedia |
| No existe marketplace: las ventas son de mostrador | Falta el eje comprador–vendedor–repartidor | `Order` con `buyerId` y `supplierId`, `Delivery` con `driverId` |
| Sin carrito multi-proveedor | Un pedido real mezcla proveedores | El checkout agrupa por proveedor y crea un pedido hijo por cada uno |
| Sin verificación KYC | Cualquiera publica u opera | `User.kycStatus` + panel de administración para aprobar o rechazar |
| Sin seguimiento de entrega | El comprador no sabe dónde va su pedido | `Delivery.status` y línea de avance de cuatro pasos en el panel del comercio |
| Sin comisiones ni split de pagos | No hay modelo de negocio | Fuera del alcance de esta entrega; documentado como fase 3 |
| Sin CFDI | Requisito fiscal | Fuera del alcance; el campo RFC ya se captura |

---

## 5. Decisiones tomadas en la nueva app

1. **Solo web.** Next.js 16 con App Router, sin Electron ni instaladores.
2. **Un login, cuatro experiencias.** El rol vive en la sesión y el layout `/app` decide el menú; cada página vuelve a validar el rol en el servidor.
3. **Autorización en dos capas.** `proxy.ts` hace la comprobación optimista de cookie; cada página y cada ruta de API verifican sesión, rol y propiedad del recurso.
4. **Prisma + SQLite** para desarrollo; el esquema es portable a PostgreSQL cambiando el `provider`.
5. **Estética del sitio de referencia**: verde profundo `#0e5134`, dorado `#b07815`, superficies `#fbfaf7`, Inter, radio 16 px.
6. **Datos de demostración** con un usuario por rol y catorce productos, para que los paneles no arranquen vacíos.

---

## 6. Mapa de reutilización

```
pos-system                          didi2-b2b
├── src/lib/auth.ts          ──────► src/lib/auth.ts        (email en vez de username)
├── src/lib/db.ts            ──────► src/lib/db.ts
├── src/lib/validations.ts   ──────► validación Zod en cada ruta
├── src/components/ui/*      ──────► src/components/ui.tsx  (tokens nuevos)
├── prisma Sale/SaleItem     ──────► Order/OrderItem
├── electron/*               ──────► descartado
└── server.js                ──────► descartado
```

---

## 7. Siguientes pasos sugeridos

1. Portar `logAudit` y registrar cada transición de pedido.
2. Tabla `UserRole` para usuarios con dos roles (comercio y mayorista).
3. Reserva atómica de stock con TTL de 15 minutos antes de confirmar el pago.
4. Pasarela con split (Mercado Pago Marketplace o Stripe Connect) y webhook idempotente.
5. Notificaciones por correo en cada cambio de estado.
6. Migrar a PostgreSQL antes de producción y añadir respaldos automáticos.
