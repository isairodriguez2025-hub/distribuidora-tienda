# Plan de cuentas profesionales — mayorista, repartidor y administrador

> Estado: propuesta para revisión. No hay código escrito todavía.
> Base: inventario del repositorio al commit `716512c` (rama `Repo-Final`).
> Alcance: las tres cuentas que faltan, más las dos funciones transversales
> obligatorias (seguimiento en vivo y contratación de repartidores).

---

## 1. Lo que pediste, traducido a objetivos

Dijiste: *"faltan el resto de tipos de cuentas… el objetivo es el diseño, la
comodidad visual para el usuario y las funciones útiles"*, y marcaste dos
funciones que **deben existir sí o sí**:

1. **Seguir en tiempo real la ruta de un producto enviado.**
2. **Que el proveedor pueda escoger o contratar a un repartidor, o repartir él mismo.**

De ahí salen cinco objetivos medibles:

| Objetivo | Cómo se comprueba |
|---|---|
| Cada cuenta tiene una pantalla de inicio propia, como el comercio | Entrar con cada rol y ver saludo + tarjeta de accesos |
| Cada cuenta puede hacer su trabajo sin salir de la app | Cero consultas a la base de datos para tareas del día |
| El estado de cualquier pedido se entiende de un vistazo | Chip de estado + siguiente paso en toda pantalla |
| El envío se sigue en vivo, con posición y hora estimada | Posición en pantalla en menos de 5 s; ETA con error menor al 20 % |
| El proveedor decide cómo se entrega | Tres modos: ronda automática, elegir repartidor, reparto propio |

Lo que **no** entra en este plan: pagos reales, aplicación móvil nativa,
notificaciones push del sistema operativo, multi-idioma.

---

## 2. Principios de diseño

Son reglas, no deseos. Cada una tiene una consecuencia concreta en el código.

**1. Una tarea primaria por pantalla.**
Cada vista responde a una pregunta ("¿qué tengo que surtir hoy?", "¿a dónde
voy?"). Las acciones secundarias viven en el menú de la tarjeta o en un
desplegable. Si una pantalla tiene dos botones principales del mismo peso, está
mal.

**2. El estado siempre está visible y siempre dice qué sigue.**
Ningún pedido se muestra solo con su nombre: lleva chip de estado y, al lado, la
acción que toca. "En camino · faltan 12 min" es mejor que "en_camino".

**3. Densidad según el dispositivo.**
El mayorista y el administrador trabajan en escritorio: tablas cómodas, filas de
56 px, columnas alineadas a la derecha para dinero. El repartidor trabaja de pie,
con una mano y con guantes: botones de 56 px, una columna, texto de 16 px mínimo,
nada que exija precisión.

**4. El color no es decoración, es información.**
Verde = avanza, ámbar = espera a alguien, rojo = problema, gris = cerrado. Un
chip de estado nunca usa el color de marca, para que la marca no compita con el
estado.

**5. Nada de pantallas en blanco.**
Todo listado tiene tres estados diseñados: cargando (esqueleto con la forma real),
vacío (icono, frase y botón para crear el primer elemento) y error (qué pasó y qué
hacer). El estado vacío es la primera pantalla que verá un usuario nuevo.

**6. Lo importante cabe en el primer scroll de un teléfono.**
En móvil, la acción del día debe estar en los primeros 640 px. Los filtros y los
totales secundarios van después.

**7. La misma gramática visual en las cuatro cuentas.**
Mismos tokens, mismas tarjetas, mismos chips, misma barra lateral. Cambia el
contenido, no el lenguaje. Un usuario que aprende a usar la cuenta de comercio ya
sabe moverse en las otras tres.

---

## 3. Estado actual, verificado

### 3.1 Mayorista

Existe: catálogo (lista, alta, edición, stock, CSV, vista previa de escalas),
bandeja de pedidos con contadores, detalle con acciones por estado, hoja de
surtido, chat dentro del pedido, facturación como emisor, finanzas del periodo,
KYC y perfil.

Duele:
- No hay pantalla de inicio ni indicadores: el menú solo tiene "Mis productos" y "Pedidos".
- El inventario es un ajuste manual de stock, sin mínimos, alertas ni movimientos visibles.
- El catálogo no tiene fotos ni ficha: se vende por nombre y precio.
- No hay reportes (ventas por producto, por cliente, rotación, tiempo de surtido).
- No hay cartera de clientes: ni historial por comercio, ni frecuencia, ni condiciones.
- La logística es una caja negra: puede pedir reparto, pero no ve tarifa, transportista ni cuándo pasa.
- Un solo usuario por empresa: sin equipo ni permisos internos.

### 3.2 Repartidor

Existe: una pantalla con interruptor de disponibilidad, ofertas y entregas del
día; seis endpoints; viaje completo con PIN de recolección (6 dígitos) y PIN de
entrega (4 dígitos); envío de GPS en lotes; el comercio ve el mapa.

Duele, y hay un fallo de fondo:
- **Las ofertas nunca vencen.** `AsignacionService::procesarVencidas()` existe pero
  no está registrada en el trabajador de tareas (`Worker`), así que las rondas 2 y
  3 nunca se lanzan y un envío puede quedarse esperando para siempre.
- Una sola pantalla para todo: sin viaje activo destacado, sin mapa propio, sin navegación.
- Sin ganancias, sin historial, sin liquidaciones. La tarifa se guarda y nunca se muestra.
- Prueba de entrega solo por PIN: sin foto, sin firma, sin validar que está en el destino.
- No puede rechazar una oferta (el estado existe en la base, no hay botón).
- Sin horarios, sin zonas, sin bloqueo por documentos vencidos.

### 3.3 Administrador

Existe: panel con seis contadores, usuarios (lista y ficha), cambio de rol y de
estado, reclamos con resolución, parámetros versionados con historial y cola KYC.

Duele:
- `admin_soporte` y `admin_finanzas` existen en la base y en el menú, pero **no
  tienen ni una ruta**: son roles fantasma. Entrar a `/admin` con ellos da 403.
- El panel son seis `COUNT(*)` sin tendencia, sin dinero, sin series.
- Cambiar rol borra todos los roles previos y no deja auditoría.
- El reembolso solo cambia el estado del pedido: no mueve dinero ni queda conciliado.
- No hay pantalla de auditoría, aunque el registro se escribe en toda la app.
- El filtro por rol de `/admin/usuarios` se lee y se ignora (bug).

### 3.4 Sistema de diseño

Ya hay: tokens de color y radio en `app.css` (`--d-verde`, `--d-oro`, `--d-tinta`,
`--d-fondo`, `--d-linea`, `--d-radio`, `--d-sombra`…), tarjetas, tablas, botones,
avisos, diálogos, barra lateral colapsable (76/240 px), barra inferior móvil,
modo oscuro persistido en la cuenta y una familia de iconos de trazo.

Deudas que conviene pagar antes de crecer:
- El `<style>` en línea de `layout.php` y `app.css` definen lo mismo con valores
  distintos (`.boton--claro`, `.campo`, `.aviso--ok`).
- El botón global pinta el texto de blanco: cualquier componente que reutilice
  `<button>` hereda texto invisible (ya pasó con las tarjetas de la caja).
- `[hidden]` no gana siempre: hay que forzarlo por selector.
- Los estados de pedido no tienen color propio; cada vista improvisa.

---

## 4. Sistema de diseño compartido

### 4.1 Tokens nuevos

Se añaden a `:root` en `app.css`, con variante en modo oscuro:

```
--est-espera:   #B7791F   /* ámbar: esperando a alguien */
--est-avance:   #1F6F4A   /* verde: en movimiento */
--est-riesgo:   #B42318   /* rojo: problema o cancelado */
--est-cierre:   #6B7280   /* gris: cerrado con normalidad */
--est-suave:    #F3F4F1   /* fondo de chip */
--elev-1: 0 1px 2px rgba(16,24,20,.06);
--elev-2: 0 10px 24px -14px rgba(16,24,20,.35);
--elev-3: 0 24px 48px -24px rgba(16,24,20,.45);
--toque: 44px;            /* área mínima táctil */
```

### 4.2 Componentes nuevos

| Componente | Clase | Dónde se usa |
|---|---|---|
| Chip de estado | `.chip` + `.chip--espera/--avance/--riesgo/--cierre` | Pedidos, envíos, KYC, reclamos |
| Métrica | `.metrica` (valor grande + etiqueta + delta) | Inicio de mayorista y admin |
| Cronología | `.cronologia` (punto, línea, hora, actor) | Detalle de pedido, viaje, reclamo |
| Barra de fases | `.fases` (5 pasos con estado) | Detalle de pedido y seguimiento |
| Hoja inferior | `.hoja` (móvil, sube desde abajo) | Acciones del repartidor |
| Tabla densa | `.tabla-densa` (56 px, cabecera fija, ordenable) | Admin y mayorista |
| Persona | `.persona` (avatar + nombre + calificación) | Repartidores, clientes |
| Mapa vivo | `.mapa-vivo` (mapa + tarjeta flotante inferior) | Seguimiento y viaje activo |
| Acción principal | `.accion-principal` (ancho completo, 56 px) | Móvil del repartidor |

### 4.3 Reglas de composición

- Toda pantalla abre con el mismo bloque: título, una línea de contexto, acción principal a la derecha (o debajo en móvil).
- Los filtros van en una fila de chips, no en un desplegable, cuando son tres o menos.
- El dinero se alinea a la derecha y se escribe con dos decimales y separador de miles.
- Las fechas relativas ("hace 4 min", "mañana 09:00") y la absoluta en el `title`.
- Ningún texto por debajo de 13 px; en móvil, 15 px.

---

## 5. Navegación por cuenta

Todas las cuentas abren en **Explorar**, con la misma estructura: saludo, tarjeta
de accesos y franja de estado del día.

**Mayorista** — Explorar · Pedidos · Catálogo · Inventario · Logística · Reportes · Cuenta
**Repartidor** — Inicio · Viaje · Ofertas · Ganancias · Cuenta (barra inferior de 4 en móvil)
**Administrador** — Panel · Operación · Usuarios · Moderación · Dinero · Parámetros · Cuenta

El comercio conserva su menú actual. No se toca.

---

## 6. Cuenta mayorista — portal de proveedor

### 6.1 Inicio

```
Hola, Abarrotes Maru
Hoy tienes 3 pedidos por surtir y 2 productos bajo mínimo.

[ Por surtir 3 ] [ Vendido hoy $12,480 ] [ Ticket promedio $1,040 ] [ Cumplimiento 96 % ]

┌─────────────────────────────┐  ┌──────────────────────────┐
│ Pedidos por surtir      3 → │  │ Inventario en alerta  2 →│
│ Catálogo               48 → │  │ Repartos en curso     1 →│
│ Reportes del mes         → │  │ Facturas por emitir   4 →│
└─────────────────────────────┘  └──────────────────────────┘

Últimos pedidos
#A-1042  Casa de Toño      $3,480   [Por surtir]  hace 12 min
#A-1041  Abarrotes Luna    $1,120   [En camino]   hace 40 min
```

Contenido: cuatro métricas del día (`pedidos por surtir`, `vendido hoy`, `ticket
promedio`, `cumplimiento`), tarjeta de accesos, alertas accionables y los últimos
cinco pedidos. Datos con caché de 60 s para no golpear la base en cada visita.

Acciones: aceptar pedido desde la fila, ir a inventario, emitir factura.

### 6.2 Pedidos

Bandeja con pestañas por estado (`Por aceptar`, `Preparando`, `Listos`, `En
camino`, `Cerrados`), buscador por folio o cliente, paginación real (25 por
página) y vista de tabla densa.

Cada fila: folio, cliente, total, número de líneas, chip de estado, tiempo
transcurrido y la acción que toca ("Aceptar", "Marcar listo", "Elegir repartidor").

En móvil se convierte en tarjetas apiladas con la acción como botón inferior.

### 6.3 Detalle de pedido

Dos columnas en escritorio, una en móvil:

- **Izquierda:** cronología del pedido con horas y actor, líneas con cantidad y precio, totales, hoja de surtido imprimible.
- **Derecha:** tarjeta del cliente (historial, ticket promedio, calificación), tarjeta de entrega (repartidor, tarifa, ETA, mapa reducido) y chat del pedido.

La barra de fases de cinco pasos corona la vista: `Aceptado → Preparando → Listo
→ En camino → Entregado`, con el paso actual resaltado y la hora de cada uno.

### 6.4 Catálogo

Lista en tarjetas con foto cuadrada, nombre, precio, existencias y estado de
publicación. Filtros por categoría, estado y "bajo mínimo". Alta y edición en un
panel lateral (no en otra página), con foto arrastrable, ficha corta y escalas de
precio en la misma vista.

Importación CSV: primero una vista previa con mapeo de columnas y errores por
línea; después, confirmar. Nunca importar a ciegas.

### 6.5 Inventario

Tabla con producto, existencias, mínimo, estado (ok / bajo / agotado) y última
entrada. Cuatro acciones por fila: ajustar, reponer, ver movimientos, pausar
publicación. Arriba, tres métricas: valor del inventario, productos bajo mínimo,
agotados.

Movimientos: kardex en cronología con motivo, usuario y saldo resultante.

### 6.6 Precios

Tres bloques en una pantalla: escalas por volumen (ya existen, se muestran como
tabla editable), listas por cliente (precio especial por comercio) y promociones
con vigencia. Vista previa: "un comercio que compre 50 piezas paga $X".

### 6.7 Logística

Es la pantalla que resuelve la función obligatoria B del lado del proveedor:

- **Envíos activos:** mapa con los repartos en curso, tarjeta por envío (repartidor, ETA, estado) y acceso al seguimiento.
- **Buscar repartidor:** lista de repartidores disponibles con calificación, vehículo, distancia a la bodega, tarifa estimada y tiempo de llegada. Botón "Solicitar" por fila y "Enviar a todos" para la ronda automática.
- **Reparto propio:** interruptor "Entrego yo" que salta la asignación y genera el PIN de entrega para el personal del proveedor.
- **Tarifas:** tabla de tarifas por tipo de vehículo, con simulador de costo (distancia, peso, espera).
- **Historial:** envíos cerrados con incidencias y calificación.

### 6.8 Reportes

Cuatro pestañas: ventas (por producto, por cliente, por periodo), operación
(tiempo de surtido, tasa de rechazo, cumplimiento), inventario (rotación,
agotados) y exportación CSV. Cada gráfica con su tabla de datos debajo: la
gráfica es para decidir, la tabla para verificar.

### 6.9 Clientes

Cartera de comercios: nombre, pedidos, ticket promedio, última compra, saldo y
condiciones. Ficha con historial y botón "ofrecer precio".

### 6.10 Facturación

Facturas emitidas con estado (`timbrada`, `cancelada`), nota de crédito, estado
de cuenta por cliente y descarga de XML/PDF. La hoja imprimible ya existe; se
reutiliza.

### 6.11 Perfil público

Lo que ve el comercio: portada, descripción, categorías, cobertura, horarios,
calificación y tiempo de respuesta. Se edita desde "Cuenta".

---

## 7. Cuenta repartidor — app de reparto

Diseñada para usarse de pie, con una mano, con prisa y con el sol de frente.

### 7.1 Inicio

```
Hola, Luis
[ ● En línea ]  Desde las 08:00

┌──────────────────────────────────────────┐
│ VIAJE EN CURSO                            │
│ Recoger en Bodega Maru · 2.1 km           │
│ Entregar en Casa de Toño · 4.8 km         │
│ Estimado 26 min · $78.00                  │
│ [ Ver viaje ]                             │
└──────────────────────────────────────────┘

Hoy: 6 viajes · $412.00 · 4.9 ★
```

Interruptor de disponibilidad grande (estado en palabras, no solo color),
tarjeta del viaje activo siempre arriba, resumen del día y ofertas nuevas
destacadas con contador.

### 7.2 Viaje activo

Mapa a pantalla completa con la ruta trazada y una tarjeta inferior fija:

- Parada actual con dirección grande y botón "Navegar" (abre Google Maps o Waze con las coordenadas).
- Botón único contextual: `Llegué a la bodega` → `Confirmar recolección (PIN)` → `Salir a entregar` → `Llegué al cliente` → `Entregar (PIN)`.
- Acciones secundarias en hoja inferior: llamar, mensaje, reportar problema.
- Prueba de entrega: PIN de 4 dígitos, foto opcional obligatoria si hay incidencia, y validación de que el GPS esté a menos de 200 m del destino (si no, pide confirmación).
- Wake lock: la pantalla no se apaga mientras el viaje está activo.

### 7.3 Ofertas

Tarjetas con origen, destino, distancia total, tarifa, tiempo estimado y
**contador de expiración** (90 s en modo directo, 60 s en ronda). Dos botones:
`Aceptar` (grande) y `Rechazar` (texto). Rechazar sí debe existir: hoy la oferta
solo se ignora y el sistema no aprende.

Sin recarga manual: la pantalla consulta cada 20 s y vibra/emite un tono al
aparecer una oferta nueva.

### 7.4 Ganancias

Hoy, esta semana, este mes. Desglose por viaje (fecha, cliente, distancia,
tarifa, propina, comisión). Liquidación con estado (`pendiente`, `pagada`) y
método de cobro. Exportación CSV.

### 7.5 Historial

Viajes entregados con calificación recibida, incidencias y tiempo total. Filtro
por periodo.

### 7.6 Disponibilidad y zonas

Horario semanal (franjas por día), zona de trabajo (por colonia o polígono),
vehículo activo y capacidad. Fuera de horario, el sistema no le ofrece viajes.

### 7.7 Documentos y perfil

Licencia, seguro, tarjeta de circulación, verificación vehicular, con estado y
fecha de vencimiento. **Un documento vencido bloquea la conexión** y lo dice con
claridad, sin dejar el interruptor en verde.

### 7.8 Soporte

Chat con soporte, reporte de incidente en dos toques y botón de emergencia
(llamada directa).

---

## 8. Cuenta administrador — consola de operación

### 8.1 Panel

```
Salud del sistema                       Negocio hoy
● Base de datos  ok · 12 ms             Pedidos        42   +8 %
● Disco          61 % libre             GMV        $48,120  +12 %
● Cola            3 en espera           Comisión    $5,774
● Errores 24 h    2                     Repartos        18   -2 %

Requieren atención
3 envíos sin repartidor hace más de 10 min   →
5 KYC esperando revisión                     →
2 reclamos abiertos con 48 h                 →
```

Métricas con caché de 60 s y comparación contra ayer. Cada alerta enlaza a la
pantalla que la resuelve.

### 8.2 Operación en vivo

Tres columnas: pedidos atascados (por estado y antigüedad), envíos sin
repartidor, y repartidores conectados con su viaje actual. Acciones: relanzar
ronda, reasignar, cancelar con motivo, abrir el pedido.

### 8.3 Usuarios

Búsqueda por correo, nombre o ID. Filtros que **sí** se aplican (rol, estado,
tipo de cuenta). Ficha con historial, pedidos, mensajes, KYC, sanciones y
acciones: cambiar rol (multirol, con motivo y auditoría), suspender, reactivar,
verificar, impersonar para soporte (con aviso visible y registro).

### 8.4 Moderación

Cola de reseñas reportadas, productos sospechosos y conversaciones marcadas. Cada
caso con su contexto y tres salidas: aprobar, ocultar, sancionar.

### 8.5 Disputas

Reclamo con evidencia: pedido completo, chat, prueba de entrega, fotos y pagos.
Resolución con motivo estandarizado y doble confirmación cuando hay dinero.

### 8.6 Dinero

Pagos, reembolsos, conciliación con la pasarela, comisiones y liquidaciones a
repartidores. Cada movimiento con su referencia externa y su estado.

### 8.7 Auditoría

Registro consultable con filtros por actor, acción, entidad y fecha. Hoy se
escribe y no se lee: esta pantalla lo hace útil.

### 8.8 Parámetros

Se conserva lo que ya funciona (versionado e historial) y se agrupa por tema:
tarifas, comisiones, límites, tiempos de ronda.

### 8.9 Soporte

Bandeja unificada de conversaciones con asignación, etiquetas y tiempo de
respuesta. Aquí sí tiene sentido dar rutas a `admin_soporte`.

---

## 9. Función obligatoria A — seguimiento en vivo

### 9.1 Qué hay hoy

`TrackingService` guarda lotes de posiciones en `tracking_puntos`, valida
precisión (máximo 100 m), acepta solo durante `en_recoleccion`, `en_camino` y
`en_puerta`, y muestra el mapa al comercio solo en `en_camino` y `en_puerta`. El
mapa refresca cada 15 s con ETag y calcula ETA con OSRM. El repartidor envía cada
12 s en lotes de 20.

Funciona, pero no está a la altura de "seguir la ruta en tiempo real":

| Problema | Consecuencia |
|---|---|
| Sin cronología de hitos | El comercio ve el punto, no el recorrido ni lo que falta |
| ETA sin confianza ni estado | "12 min" parece un dato duro y cambia sin explicación |
| 15 s de refresco y solo con la pestaña abierta | Se siente congelado |
| Sin enlace para compartir | El cliente final no puede seguirlo |
| Sin cola offline | En un sótano o una zona sin señal se pierden posiciones |
| Posición visible solo al comercio | El proveedor, que organiza el envío, no ve nada |

### 9.2 Diseño propuesto

**Recorrido del dato**

```
Móvil del repartidor          Servidor                    Quien mira
watchPosition (10 s)   →   POST /tracking/posicion   →   GET /pedidos/{uuid}/tracking
  cola en localStorage      valida y guarda               cada 5 s con ETag
  reintenta al volver red   calcula ETA (OSRM)            pinta solo si cambió
```

**Pantalla de seguimiento** (comercio, proveedor y cliente final):

```
┌───────────────────────────────────────────────┐
│ ← Pedido #A-1042                     [Compartir]│
│                                                   │
│                MAPA  ·  ruta trazada              │
│                ▲ repartidor en vivo               │
│                                                   │
├───────────────────────────────────────────────┤
│ Luis M.  ★ 4.9 · Moto · ABC-123          [Llamar]│
│ Llega 14:32 · faltan 11 min                       │
│ ●───●───●───○───○                                 │
│ Recogido  En camino  En puerta                    │
│                                                   │
│ [ Ver detalle del pedido ]                        │
└───────────────────────────────────────────────┘
```

**Cronología de hitos** guardada en `envios_eventos`: cada transición con hora,
actor y nota. Se muestra como línea de tiempo y alimenta la barra de fases.

**Reglas de privacidad**

- La posición se expone solo mientras el envío está activo, a comercio dueño, proveedor dueño, repartidor asignado y quien tenga el enlace público.
- Al entregarse, el enlace público caduca (24 h) y el mapa deja de mostrar posición.
- Se guarda el recorrido para incidencias, pero no se publica.
- El enlace público es un token aleatorio de 32 bytes, sin datos personales, revocable.

**Rendimiento**

- 5 s de refresco con ETag durante el viaje; 15 s cuando está en puerta; se detiene al entregar.
- El servidor devuelve solo el último punto y el ETA, no el historial completo.
- Si OSRM no responde, ETA en línea recta con aviso de "estimado".
- Presupuesto: menos de 300 ms por consulta, menos de 40 KB por respuesta.

**Criterios de aceptación**

1. La posición del repartidor aparece en el mapa en menos de 5 s desde que se mueve.
2. El ETA se recalcula y su error es menor al 20 % en trayectos de más de 3 km.
3. Al entregar, el seguimiento muestra "entregado" y deja de pedir posición.
4. Diez minutos sin señal: al volver, la ruta se completa sin huecos.
5. El enlace público funciona sin sesión y caduca a las 24 h.
6. Un usuario ajeno al pedido recibe 403.

---

## 10. Función obligatoria B — contratación de repartidores

Tres modos, un mismo motor. El proveedor elige; el sistema no decide por él.

### 10.1 Modo 1 — Ronda automática (ya existe, hay que repararlo)

El proveedor pulsa "Buscar repartidor" y el sistema ofrece el viaje a los
repartidores disponibles por cercanía y calificación: ronda 1 hasta 5 km, ronda 2
hasta 8 km, ronda 3 hasta 12 km; tres ofertas por ronda, 60 s para aceptar; el
primero que acepta se lo queda (bloqueo atómico y tarifa congelada).

**Arreglo necesario:** registrar `procesarVencidas()` en el trabajador de tareas.
Hoy nadie lo llama, así que las ofertas no vencen y las rondas 2 y 3 nunca
ocurren. Es un cambio de una línea más la prueba que lo demuestre.

### 10.2 Modo 2 — Elegir repartidor (nuevo)

El proveedor ve la lista de repartidores disponibles y elige.

```
Elegir repartidor para #A-1042
Bodega Maru → Casa de Toño · 4.8 km · 18 kg · sin refrigeración

[ Todos ] [ Moto ] [ Camioneta ] [ Refrigeración ]     Ordenar: ⦿ Calificación ○ Precio ○ Cercanía

┌──────────────────────────────────────────────────────────┐
│ (LM) Luis M.       ★ 4.9 · 312 viajes · Moto             │
│      1.2 km de la bodega · llega en 9 min                │
│      $78.00                              [ Solicitar ]   │
├──────────────────────────────────────────────────────────┤
│ (RG) Rosa G.      ★ 4.7 · 158 viajes · Camioneta         │
│      3.8 km · llega en 16 min · capacidad 800 kg         │
│      $124.00                             [ Solicitar ]   │
└──────────────────────────────────────────────────────────┘

[ Enviar a todos (ronda automática) ]
```

La tarifa la calcula `TarifaService` (base + km + peso + espera, por vehículo) y
se muestra **antes** de solicitar: el proveedor contrata sabiendo cuánto cuesta.

La solicitud va directa a ese repartidor con 90 s para responder. Si rechaza o
vence, el proveedor lo ve al instante y puede elegir a otro o lanzar la ronda.

### 10.3 Modo 3 — Reparto propio (nuevo)

El proveedor entrega con sus propios medios.

- Interruptor "Entrego yo" en el detalle del pedido y en Logística.
- Se salta la asignación: no hay oferta, no hay comisión de logística.
- Genera un PIN de entrega para el personal del proveedor.
- El pedido avanza igual: `listo → en_camino → en_puerta → entregado`.
- Si el proveedor tiene repartidores propios dados de alta, puede elegir a cuál asignar: se guardan como vínculo `repartidor_empresa`.

### 10.4 Ser repartidor siendo proveedor

Un mayorista puede además darse de alta como repartidor (con KYC y vehículo).
Su cuenta gana la sección "Repartos" y puede tomar viajes de otros proveedores.
Es la vía para pequeños distribuidores que reparten su propia mercancía y
aprovechan los huecos.

### 10.5 Reglas de negocio

| Regla | Valor propuesto |
|---|---|
| Expiración de oferta en ronda | 60 s |
| Expiración de solicitud directa | 90 s |
| Rondas | 3 (5, 8 y 12 km) |
| Ofertas por ronda | 3 |
| Cancelación por el repartidor tras aceptar | permitida hasta "en recolección", con penalización de 1 punto de calificación |
| Cancelación por el proveedor tras asignar | permitida, paga la tarifa si el repartidor ya salió |
| Comisión de logística | 12 % (parámetro existente) |
| Reparto propio | sin comisión de logística |
| Documento vencido | bloquea la conexión y las ofertas |
| Calificación | dos vías: comercio → repartidor y proveedor → repartidor; promedio en `repartidores_perfil.calificacion_promedio` |

### 10.6 Criterios de aceptación

1. El proveedor puede contratar en tres toques: abrir Logística, elegir repartidor, solicitar.
2. La tarifa mostrada coincide con la que se congela al aceptar.
3. Si el repartidor rechaza, el proveedor se entera en menos de 2 s y puede elegir otro.
4. "Entrego yo" deja el pedido sin repartidor asignado y con PIN propio.
5. Un repartidor con documento vencido no aparece en la lista ni recibe ofertas.
6. Las ofertas vencen solas y la ronda 2 se lanza sin intervención.

---

## 11. Modelo de datos

Migraciones nuevas, todas idempotentes y con `IF NOT EXISTS`:

**030 · seguimiento**
- `envios_eventos` (id, envio_id, estado, actor_id, actor_rol, nota, created_at)
- `envios.modo` ENUM('ronda','directo','propio') DEFAULT 'ronda'
- `envios.eta_minutos` INT NULL
- `envios.asignado_en` DATETIME NULL
- `envios.compartir_token` CHAR(64) NULL UNIQUE

**031 · contratación**
- `solicitudes_envio` (id, envio_id, repartidor_id, tarifa, estado ENUM('enviada','aceptada','rechazada','expirada'), expira_en, respondida_en, created_at)
- `repartidor_empresa` (id, mayorista_id, repartidor_id, activo, created_at)

**032 · repartidor**
- `repartidores_perfil`: `viajes_completados`, `calificacion_viajes`, `zonas`, `horario_json`, `liquidacion_estado`
- `repartidor_documentos` (id, repartidor_id, tipo, vence_en, archivo, estado)
- `repartidor_ganancias` como vista sobre `envios` + propinas

**033 · mayorista**
- `productos.imagen` VARCHAR(255) (igual que la caja)
- `productos.ficha_corta` TEXT
- `inventario_movimientos` ya existe como kardex: se añade índice por fecha
- `listas_precio` (id, mayorista_id, comercio_id, producto_id, precio, vigencia)

**034 · administración**
- `sanciones` (id, usuario_id, motivo, alcance, expira_en, admin_id, created_at)
- `auditoria` ya existe: se añade índice por (entidad, entidad_id, created_at)

**035 · soporte**
- `conversaciones` y `mensajes` ya existen: se añade `asignado_a` y `prioridad`

---

## 12. Fases

Cada fase termina con algo usable, no con código a medias.

| Fase | Contenido | Entregable verificable | Duración |
|---|---|---|---|
| 0 | Tokens y componentes nuevos; Explorar para los tres roles; arreglo de deudas CSS | Los tres roles entran y ven su inicio, sin romper lo existente | 4 días |
| 1 | Mayorista: inicio, pedidos, detalle, catálogo con foto | Un proveedor surte un pedido completo sin salir de la app | 1.5 semanas |
| 2 | Mayorista: inventario, precios, reportes, clientes | Reportes con datos reales y exportación | 1 semana |
| 3 | Repartidor: inicio, viaje activo, ofertas, ganancias, historial | Un repartidor completa un viaje desde el teléfono | 1.5 semanas |
| 4 | Seguimiento en vivo (sección 9) | Posición en menos de 5 s, cronología, enlace público | 1.5 semanas |
| 5 | Contratación (sección 10) y arreglo de vencimientos | Tres modos operando y probados | 1.5 semanas |
| 6 | Admin: panel, operación, usuarios, auditoría, moderación | Un administrador resuelve una disputa con evidencia | 1.5 semanas |
| 7 | Pulido: accesibilidad, modo oscuro, pruebas E2E por rol | Rastreo sin 404 ni errores, contraste AA | 1 semana |

Total estimado: **9 a 10 semanas** de trabajo continuo, en el orden indicado.
Las fases 4 y 5 se pueden adelantar si las dos funciones obligatorias pesan más
que el resto.

---

## 13. Pruebas

- **Por rol:** un recorrido E2E con Playwright que entra, navega todo el menú y falla si hay 404 o error de JavaScript. Se extiende el rastreador actual a las rutas nuevas.
- **Seguimiento:** prueba que simula 20 posiciones, verifica que se guardan, que el ETA responde y que tras entregar la consulta devuelve 403.
- **Contratación:** prueba de tres caminos (ronda, directo, propio) con dos repartidores compitiendo por el mismo envío; solo uno debe ganar.
- **Vencimientos:** prueba con reloj adelantado que demuestra que una oferta expira y la ronda 2 se lanza.
- **Visual:** capturas a 360, 768 y 1280 px de cada pantalla nueva, revisadas a ojo; ninguna debe desbordar.
- **Datos:** semillas nuevas para que cada cuenta tenga algo que ver desde el primer login.

---

## 14. Riesgos y decisiones abiertas

| Riesgo | Mitigación |
|---|---|
| Complejidad de estados del pedido | Una sola máquina de estados; ninguna vista cambia estado por su cuenta |
| El mapa consume batería | Envío cada 10 s, se detiene al entregar, wake lock solo en viaje activo |
| Privacidad de ubicación | Ventana de exposición, token revocable, borrado del recorrido público |
| Datos de demostración escasos | Semillas por rol antes de cada fase |
| Deuda de CSS que crece | Fase 0 paga la deuda antes de añadir pantallas |

Decisiones que necesito de ti antes de la fase 4:

1. ¿El cliente final (consumidor) debe poder seguir el envío con un enlace, o solo comercio y proveedor?
2. ¿La comisión de logística se cobra también en reparto propio? (propongo que no)
3. ¿Un proveedor puede ser repartidor de otros proveedores, o solo repartir lo suyo? (propongo que sí, con KYC aparte)
4. ¿La liquidación al repartidor es semanal automática o manual desde el panel de administración? (propongo semanal con revisión)

---

## 15. Fuera de alcance

Pagos reales con pasarela, aplicación móvil nativa, notificaciones push del
sistema operativo, facturación electrónica real ante el SAT, multi-idioma,
optimización de rutas con múltiples paradas.
