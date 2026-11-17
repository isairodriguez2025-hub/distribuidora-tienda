# Plan de mejoras — Didi2 B2B (Repo-Final)

Documento de trabajo. Cada bloque indica alcance, decisiones técnicas y criterio de aceptación.
Se implementa en fases; ninguna fase se marca terminada sin prueba en el sitio público.

## 0. Decisiones de arquitectura

| Tema | Decisión | Motivo |
|---|---|---|
| React | **No** | La app es PHP con vistas server-side y CSP con nonce. Meter React implica reescribir 40+ vistas, duplicar el router y perder la sesión por cookie en el primer render. El objetivo (responsive, fluidez) se logra con CSS Grid/Flexbox y JS progresivo sobre el HTML que ya existe. Se reevalúa solo si se migra todo el frontend. |
| CSS | Hoja nueva `public/assets/css/app.css` cargada **después** del bloque inline | Permite rehacer el layout sin tocar 40 vistas de golpe y sin riesgo de romper estilos existentes. |
| Componentes | Clases con prefijo (`app-`, `perfil-`, `msg-`, `pos-`) | Evita colisiones con el CSS heredado (`.ficha`, `.barra`, `.boton`). |
| Mensajería | Tablas propias + polling ligero (8 s) | Sin WebSocket en PHP compartido; el polling con `since_id` es barato y suficiente. |
| HTTPSMS | Adaptador con modo `simulado` y llaves por `.env` | La laptop con las llaves no está accesible; la estructura queda lista y se enciende al pegar la clave. |
| POS | Módulo PHP dentro de esta app, inspirado en `~/pos-system` | El POS del usuario es Next.js/Electron; se portan **conceptos** (turnos, cortes, métodos de pago, folios, devoluciones), no código. |

## 1. Responsive y layout (Fase 1)

- Shell con CSS Grid: `header` sticky, `main` fluido, `footer` de acciones.
- Breakpoints: 1200 / 900 / 640 / 420.
- Tablas: en móvil se convierten en tarjetas con `data-label` (sin scroll horizontal).
- Formularios: una columna en móvil, dos en escritorio; targets táctiles de 44 px.
- Navegación: barra horizontal con scroll en escritorio; menú desplegable en móvil.
- Criterio: ninguna vista con scroll horizontal entre 320 px y 1920 px.

## 2. Identidad: avatar y menú de cuenta (Fase 1)

- Migración: `usuarios.avatar_ruta`, `usuarios.avatar_actualizado_at`.
- Subida en `/perfil`: JPG/PNG/WebP hasta 2 MB, se recorta a 256×256 y se guarda en `storage/avatars/{id}.webp`.
- Servido por `GET /avatar/{id}` con caché y `Content-Type` correcto (no se expone `storage/`).
- Header: el enlace de texto «Mi perfil» desaparece; a la derecha queda un botón con la foto (o iniciales si no hay).
- Menú desplegable (accesible: `aria-expanded`, cierre con Escape y clic fuera): Mi perfil, Mis pedidos, Mensajes, **Cerrar sesión**.
- Criterio: subir foto, verla en el header, recargar y que persista; logout desde el menú.

## 3. Mensajería segura (Fase 2)

- Tablas: `conversaciones`, `conversacion_participantes`, `mensajes_directos`.
- Bandeja `/mensajes` con lista de hilos, no leídos, último mensaje y hora.
- Hilo `/mensajes/{uuid}`: burbujas, envío por `POST`, polling cada 8 s con `?desde=id`.
- Reglas:
  - Un usuario solo ve hilos donde es participante (comprobado en cada lectura, no solo en la vista).
  - Hilo `soporte`: todo usuario puede abrirlo y **solo los administradores** lo ven; el usuario nunca ve a otros administradores entre sí.
  - No se puede iniciar conversación con un administrador concreto: se escribe al canal de soporte.
  - Límite de 3 mensajes cada 30 s y 2000 caracteres por mensaje.
  - Se registra en `audit_log` la apertura y el envío.
- Criterio: dos usuarios conversan; un tercero recibe 403; el admin ve soporte y el usuario no ve otros hilos.

## 4. Notificaciones SMS/correo con HTTPSMS (Fase 2)

- `app/Services/Notificador.php` con dos canales: `httpsms` y `correo`, y un modo `simulado` que escribe en `storage/correos/`.
- Configuración por `.env`: `HTTPSMS_API_KEY`, `HTTPSMS_FROM`, `HTTPSMS_WEBHOOK_SECRET`, `MAIL_*`.
- Se dispara en: mensaje nuevo de soporte, pedido aceptado, pedido en camino, venta POS con reembolso.
- Plantillas cortas (≤160 caracteres) en `app/Services/Plantillas.php`.
- Sin clave configurada: **no** se intenta enviar y queda registrado como `simulado` en `notificaciones`.
- Criterio: con `HTTPSMS_API_KEY` vacía, el flujo no falla y el mensaje aparece como simulado; con clave, un `POST` al endpoint de HTTPSMS con el formato correcto.

## 5. Mapa fluido (Fase 3)

Problemas actuales: se recrean todos los marcadores en cada búsqueda, sin clúster, sin cancelar peticiones previas y con animaciones en cada `setView`.

- `preferCanvas: true` y `L.markerClusterGroup` (vendorizado en `public/assets/leaflet/`).
- Agrupar marcadores por coordenada de mayorista (hoy se apila uno por producto).
- `AbortController` para cancelar el fetch anterior; `debounce` de 350 ms en `moveend`.
- Sin `flyTo` durante búsquedas; `setView` con `animate: false` al actualizar resultados.
- Popups construidos una vez y reutilizados; `bindTooltip` solo en hover.
- Estados de carga: esqueleto en la lista y spinner en el mapa.
- Criterio: mover el mapa 10 veces seguidas no deja peticiones colgadas ni parpadeo; con 30 productos se ven clústeres.

## 6. POS para el comercio (Fase 4)

Base conceptual tomada de `~/pos-system`: turnos de caja, folios, métodos de pago, devoluciones, corte y reportes.

- Tablas: `pos_ajustes`, `pos_productos`, `pos_clientes`, `pos_turnos`, `pos_ventas`, `pos_venta_lineas`, `pos_pagos`, `pos_movimientos`.
- Pantalla `/pos` (caja): buscador con lector de código de barras, carrito, cambio de cantidad, descuento por línea, total con IVA, cobro con efectivo/tarjeta/transferencia/crédito, cálculo de cambio, ticket imprimible.
- Apertura y cierre de turno con fondo de caja, ventas por método, efectivo esperado vs contado y diferencia.
- Catálogo propio: al recibir un pedido del mayorista, las líneas se copian como productos POS con precio de venta = costo × (1 + margen).
- **Reposición automática**: si el stock POS baja del mínimo, se genera un pedido al mayorista reutilizando `PedidoService` (modo `reorden_auto` desactivable).
- Reportes: ventas del día, por método, productos más vendidos, ticket promedio, corte Z imprimible.
- Criterio: abrir turno → vender en efectivo con cambio → cerrar turno con diferencia calculada → el stock baja y, al cruzar el mínimo, aparece el pedido de reposición.

## 7. Orden de ejecución y verificación

1. Migración y esquema.
2. Shell responsive + avatar (Fase 1).
3. Mensajería + notificador (Fase 2).
4. Mapa (Fase 3).
5. POS (Fase 4).
6. Prueba integral por rol con Playwright, capturas por pantalla, y rastreo de enlaces sin 404.
7. Actualizar la skill `didi2-b2b` con lo aprendido.

Nada se declara terminado sin: `php -l` sin errores, la ruta respondiendo 200/302 con el rol correcto, 403 con el rol incorrecto, y cero errores de JS en consola.
