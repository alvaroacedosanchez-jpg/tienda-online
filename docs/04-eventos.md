# 04 · Instrumentación de eventos

## Formato común (envelope)

```json
{
  "event_id": "b6f1c0de-3e0a-4c8e-9a53-8f1f5e4a1c11",
  "event_type": "order.created",
  "schema_version": 1,
  "occurred_at": "2026-10-04T18:25:31.123Z",
  "session_id": "s_7f3a9c...",
  "user_id": 2,
  "entity": { "type": "order", "id": "ORD-20261004-A3F9K2" },
  "source": "backend",
  "payload": { }
}
```

- Fechas en UTC, ISO 8601. `event_id` UUID v4 (idempotencia en el consumidor).
- `session_id`: generado en el frontend (`crypto.randomUUID()` en `sessionStorage`) y enviado en cabecera
  `X-Session-Id`; permite reconstruir el embudo aun sin login.
- `payload` sin datos personales innecesarios (nada de contraseñas, tarjetas, direcciones completas).

## Catálogo

| Evento | Origen | Cuándo | Payload |
|---|---|---|---|
| `product.viewed` | frontend → `POST /api/events` | Se abre la ficha | `{productId, sku, slug, category}` |
| `cart.item_added` | frontend → `POST /api/events` | Añadir al carrito | `{productId, qty, cartItemsCount}` |
| `checkout.started` | backend | `POST /api/checkout/start` | `{itemsCount, subtotalCents, couponCode?}` |
| `order.created` | backend | Pedido persistido | `{orderId, totalCents, itemsCount, couponCode?}` |
| `payment.simulated` | backend | Resultado del pago | `{orderId, paymentRef, status, amountCents, method, failureReason?}` |
| `support.requested` | backend | Ticket tipo soporte | `{ticketId, orderId?, subject}` |
| `incident.created` | backend | Ticket tipo incidencia o pedido a `incident` | `{ticketId?, orderId?, reason}` |

Eventos opcionales (valor para Tarea 2): `order.status_changed`, `auth.login`, `cart.item_removed`.

## Dónde se generan y almacenan

- **Backend**: `EventLogger::record($type, $entity, $payload)` (clase en `app/Services`, modelo `Event`)
  llamado desde los **Services**, nunca desde los controladores. Si es una operación con
  `DB::transaction`, el evento se inserta **en la misma transacción** (si el pedido hace rollback, no
  queda evento huérfano). Alternativa más "Laravel" (Events + Listeners) valorada y pospuesta: los
  listeners se ejecutan fuera de la transacción salvo que se configure `afterCommit`, lo que complica la explicación.
- **Frontend**: los eventos de navegación se envían con `POST /api/events` (lista blanca de tipos,
  validación de payload, límite de tamaño). Fallo al enviar → se ignora, nunca bloquea al usuario.
- **Almacén**: tabla `events` (MySQL). Elegida frente a fichero de log porque es consultable,
  transaccional y funciona en hosting compartido. Opcional: volcado paralelo a fichero JSONL como evidencia.

## Cómo se consumen (Tarea 2)

1. **Exportación**: `GET /api/admin/events?from=&to=&format=json|csv` (solo admin) → ingesta por lotes.
2. **Lectura incremental** por `id` o `occurred_at` (cursor `?after_id=`), pensada para un proceso
   que consulte periódicamente (ETL, n8n, Make, script de BI).
3. Posibles usos: embudo de conversión (`product.viewed → cart.item_added → checkout.started →
   order.created → payment.simulated`), abandono de carrito, tiempo hasta compra, incidencias por pedido,
   automatizaciones (alerta ante `incident.created`, correo de seguimiento).
4. Limitación a declarar: es *pull*, no hay cola ni webhooks; el hosting compartido no admite procesos
   persistentes. Mejora futura: webhook saliente o broker de mensajes.

## Evidencias para el screencast y la memoria

- Captura de la tabla `events` (phpMyAdmin) tras recorrer un flujo completo.
- Pantalla de back-office con listado de eventos filtrable y botón de exportación.
- Un fragmento JSON real del flujo, con el mismo `session_id` desde `product.viewed` hasta `payment.simulated`.
