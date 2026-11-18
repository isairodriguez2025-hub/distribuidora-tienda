# Plan Ares · evolución de Didi2 B2B

Documento de trabajo. Cada fase termina con criterios de aceptación verificables
con Playwright; nada se da por hecho sin prueba real contra la URL pública.

## 1. Identidad

El producto pasa a llamarse **Ares** (dios de la guerra: fuerza, orden, logística).
Paleta: negro carbón `#0B0C0E`, plata `#C8CCD2`, acento verde `#1F4D3A` que ya
usa la app. El logo es una **A** construida con dos trazos en punta de flecha y
una barra horizontal, en plata con degradado, sobre negro.

- `public/assets/img/ares-logo.svg` (vectorial, escalable, sin texto).
- `public/assets/img/ares-icon.svg` + PNG 192/512 para el manifest.
- `favicon.ico` generado desde el SVG.
- El nombre aparece en: `<title>`, cabecera, login, manifest, correos.

## 2. Autenticación

**Problema actual:** dos pantallas separadas, tarjetas a todo lo ancho, sin
recuperación de cuenta.

**Diseño:** una sola tarjeta centrada de 420 px, fondo con el logo, título corto,
campos de 44 px, botón principal a todo el ancho y enlace secundario debajo.
Login y registro comparten componente; se alternan con una pestaña, sin recargar.

- Botón de ojo dentro del campo para mostrar/ocultar contraseña (accesible:
  `aria-pressed`, `aria-label`, foco visible, no altera el autocompletado).
- Medidor de fuerza en el registro (longitud, mayúscula, número, símbolo).
- **Recuperación**: `/recuperar` pide correo o teléfono, responde siempre lo
  mismo (no revela si la cuenta existe), crea token de un solo uso con caducidad
  de 30 min y envía enlace por correo y/o código de 6 dígitos por SMS.
  `/restablecer?token=` fija la contraseña nueva y cierra todas las sesiones.
- Límite: 3 solicitudes por hora por cuenta y 10 por IP.

## 3. Pedidos (ciclo completo)

Estados nuevos, en este orden:

| Estado | Significado | Quién lo mueve |
|---|---|---|
| `en_creacion` | borrador, editable, no visible al proveedor | comercio |
| `por_aceptar` | enviado, espera al mayorista | mayorista |
| `buscando_repartidor` | aceptado, sin repartidor asignado | mayorista / sistema |
| `en_camino` | repartidor asignado, en ruta | repartidor |
| `recibido` | entregado y confirmado por el comercio | comercio |
| `cancelado` / `rechazado` | salidas laterales | ambos |

Migración de los estados viejos: `pendiente|pagado → por_aceptar`,
`aceptado|preparando → buscando_repartidor`, `entregado|completado → recibido`.

Cada transición escribe en `pedido_eventos` (quién, cuándo, estado anterior,
estado nuevo, nota) y en la auditoría. El comercio ve una línea de tiempo; el
mayorista, una bandeja con acciones según el estado.

## 4. Carrito múltiple

- Tabla `carritos` (uuid, usuario, nombre, proveedor_id, estado
  `activo|guardado|convertido`, created_at) y `carrito_lineas`.
- Barra superior del carrito con pestañas: un carrito por proveedor + "Guardados".
- Acciones: renombrar, duplicar, guardar para después, vaciar, convertir a pedido.
- Un pedido puede agrupar varios carritos: se crea **un pedido por proveedor** y
  se enlazan con `grupo_pedido` (uuid común) para verlos juntos.
- Persistencia en servidor (no en localStorage): el carrito sigue en otro equipo.

## 5. Inventario

- **Importar CSV**: carga con vista previa de las primeras 20 filas, mapeo de
  columnas, detección de duplicados por SKU/código de barras, informe de errores
  por fila y confirmación. Plantilla descargable.
- **Imágenes de producto**: subida por producto (recorte cuadrado 600 px, WebP),
  visibles en la rejilla de la caja y en el inventario.
- Búsqueda por código de barras en inventario y en el mapa.

## 6. Caja (POS)

Replica la disposición de POS-SYSTEM: rejilla de productos a la izquierda con
imagen, nombre, precio y stock; ticket a la derecha; barra inferior con cliente,
método de pago, subtotal/IVA/total y botón Cobrar.

- **Impresión**: ticket de 80 mm generado en el servidor (`/pos/ticket/{uuid}`)
  con `@media print` y ancho configurable (32/42/48 columnas). Al cobrar se abre
  la ventana de impresión automáticamente (configurable) y queda el botón
  "Reimprimir" en el historial. Sin diálogos intermedios.
- Atajos: F2 buscar, F3 cobrar, Esc cerrar.
- Pestaña **Inventario** en la barra superior (junto a Caja, Ventas, Clientes).

## 7. Programados

- Regla por comercio: "cada N días a las HH:MM, pedir lo vendido desde el último
  pedido". Se calcula con las ventas del POS entre la fecha del último pedido
  programado y ahora, agrupadas por producto, con stock objetivo configurable.
- Si al llegar la hora el pedido anterior sigue en `en_creacion`, **no se crea
  otro**: se suman las piezas nuevas a ese borrador y se registra el ajuste.
- Vista previa antes de confirmar: qué se va a pedir y por qué.

## 8. Proveedores, métodos de pago y ventas

- **Proveedores**: alta/edición, contacto, condiciones de pago, productos que
  surte, historial de pedidos y saldo.
- **Métodos de pago**: alta/edición, comisión, si afecta caja, activo/inactivo.
- **Ventas**: filtros por fecha, cajero, método y estado; detalle con líneas,
  reimpresión de ticket y cancelación con motivo (devuelve stock).

## 9. Auditoría

Tabla `auditoria` con: usuario, rol, acción, entidad, id, descripción, datos
antes/después en JSON, IP, user-agent y fecha. Pantalla `/admin/auditoria` con
filtros por usuario, acción, entidad y rango de fechas, y exportación CSV.
Se registra: login (éxito y fallo), altas/bajas/cambios de cualquier entidad,
cambios de stock, ventas, cancelaciones y transiciones de pedido.

## 10. Navegación

Barra superior: **Buscar · Pedidos · Carritos · Programados · Inventario · Caja ·
Ventas · Finanzas · Facturación · Perfil**. Se eliminan *Avisos* y *Mensajes* de
la barra; la mensajería queda accesible desde el menú de cuenta y desde el
detalle de cada pedido.

## 11. Datos desde POS-SYSTEM

La app de referencia corre en `pos-equipo:3000` (Next.js + SQLite). Se extraen
productos, proveedores, métodos de pago y clientes mediante un importador CSV
que ya queda en la app, para que la carga sea repetible y auditable.

## 12. Fases de entrega

1. **Base**: migraciones, auditoría, branding Ares, navegación nueva.
2. **Auth**: login/registro unificados, ojo de contraseña, recuperación.
3. **Pedidos**: estados nuevos, línea de tiempo, bandejas.
4. **Carrito múltiple** y agrupación multi-proveedor.
5. **Inventario**: CSV, imágenes, código de barras.
6. **Caja**: rejilla con imágenes, ticket automático, atajos.
7. **Programados** con fusión de borradores.
8. **Proveedores, pagos y ventas**.
9. **Pruebas** de extremo a extremo y despliegue.

Cada fase: `php -l`, migración aplicada, prueba Playwright del flujo feliz y del
caso límite, y captura de pantalla revisada antes de pasar a la siguiente.
