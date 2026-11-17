# Integración HTTP SMS (httpsms.com)

## Estado actual

| Pieza | Dónde | Estado |
|---|---|---|
| Cliente HTTP | `app/Modules/Notificaciones/HttpsmsClient.php` | Listo, sin llave activa |
| Canal `sms` en la cola | `app/Modules/Notificaciones/NotificacionService.php` | Listo |
| Preferencia por usuario | tabla `preferencias_notificacion.sms` | Migración 020 |
| Aviso de soporte por SMS | `MensajeService::avisar()` | Activo (simulado) |
| Llaves | `.env` → `HTTPSMS_API_KEY`, `HTTPSMS_FROM` | **Vacías** |

El proyecto HTTPSMS original vive en la otra laptop, que hoy no es accesible. Por eso
la integración está completa pero en **modo simulado**: cuando falta la llave, el
cliente no hace ninguna petición de red y la notificación se marca como enviada con
el texto `SIMULADO: sin HTTPSMS_API_KEY` en el campo `error`, para que se distinga de
un envío real en la bandeja de avisos.

## Cómo activarlo (5 minutos, cuando tengas la laptop)

1. Copia del proyecto HTTPSMS de la laptop:
   - `HTTPSMS_API_KEY` (Settings → API Key, formato `uk_...`)
   - `HTTPSMS_FROM` (el número del Android vinculado, formato E.164: `+5215512345678`)
2. Pégalos en `~/distribuidora-final/.env`.
3. `systemctl --user restart didi2-final`
4. Prueba real:

```bash
curl -s -X POST https://api.httpsms.com/v1/messages/send \
  -H "x-api-key: $HTTPSMS_API_KEY" \
  -H 'Content-Type: application/json' \
  -d '{"from":"+52155...","to":"+52155...","content":"Prueba Didi2"}'
```

5. Prueba desde la app: abre un hilo de soporte y escribe; el administrador debe
   recibir el SMS en el teléfono vinculado.

## Reglas de diseño

- **Solo soporte usa SMS.** Entre comercio y mayorista el aviso es por bandeja:
  el SMS cuesta dinero y no debe dispararse por cada mensaje de negocio.
- El teléfono se toma de `usuarios.telefono`; si está vacío, el envío se marca como
  fallido con el motivo, sin reintentos infinitos (máximo 3 por la cola).
- La llave **nunca** se escribe en la base ni en los logs: vive solo en `.env`.

## Pendiente cuando llegue la laptop

- Confirmar el formato exacto de `from` (HTTPSMS exige que coincida con el
  teléfono vinculado).
- Verificar la cuota del plan y añadir un tope diario por usuario si hace falta.
