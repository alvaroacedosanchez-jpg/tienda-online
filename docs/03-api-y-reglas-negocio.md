# 03 · API y reglas de negocio

Formato: JSON UTF-8, rutas en `routes/api.php`. Se usa el **formato de error nativo de Laravel** para
validación (HTTP 422: `{"message":"...","errors":{"email":["..."]}}`) y para el resto
`{"message":"..."}` con HTTP 401/403/404/409/429/500. Con `APP_DEBUG=false` Laravel no expone trazas ni SQL.
Peticiones con cabecera `Accept: application/json` (la añade el cliente `api/client.js`).
Autenticación: Sanctum SPA (cookie); rutas protegidas con `auth:sanctum` y, las de admin, middleware `admin`.

## Endpoints

| Método | Ruta | Auth | Descripción | Evento |
|---|---|---|---|---|
| GET | `/api/categories` | — | Lista de categorías | |
| GET | `/api/products?category=&q=` | — | Catálogo (filtro por categoría y búsqueda) | |
| GET | `/api/products/{slug}` | — | Ficha de producto | |
| POST | `/api/events` | — | Eventos del frontend (lista blanca: `product.viewed`, `cart.item_added`) | los propios |
| GET | `/sanctum/csrf-cookie` | — | Inicializa la cookie CSRF (antes del login) | |
| POST | `/api/auth/login` · `/api/auth/logout` · GET `/api/auth/me` | — / sesión | Sesión | |
| POST | `/api/cart/quote` | — | Dado `[{productId, qty}]` + cupón opcional devuelve desglose calculado por el servidor | |
| POST | `/api/checkout/start` | cliente | Marca inicio de checkout | `checkout.started` |
| POST | `/api/orders` | cliente | Crea pedido (valida stock, recalcula, transacción) | `order.created` |
| POST | `/api/orders/{publicId}/pay` | cliente | Pago simulado | `payment.simulated` |
| GET | `/api/orders` · `/api/orders/{publicId}` | cliente | Mis pedidos (solo los propios) | |
| POST | `/api/support` | cliente | Soporte o incidencia, opcionalmente ligada a pedido | `support.requested` / `incident.created` |
| GET | `/api/admin/orders?status=` · `/api/admin/orders/{publicId}` | admin | Back-office: listado y detalle (líneas, pago, historial) | |
| PATCH | `/api/admin/orders/{publicId}/status` | admin | Cambio de estado válido | |
| GET | `/api/admin/events?type=&from=&to=&format=json\|csv` | admin | Consulta/exportación de eventos | |
| GET | `/api/admin/support` | admin | Tickets | |

## Reglas de negocio (simuladas, documentarlas en la memoria)

- **IVA**: por producto, `tax_rate_bp` (21 % general; 10 % si el caso lo justifica). Los precios
  se muestran en el catálogo **con IVA incluido**; en BBDD `price_cents` es base imponible. Decidir
  y documentar un único criterio de redondeo (por línea, a céntimo, `round half up`).
- **Envío**: 4,95 € si el subtotal con IVA < 50 €; gratis a partir de 50 €.
- **Cupones**: `percent_off` sobre el subtotal base; requiere `min_subtotal_cents`, activo y no
  caducado. Un cupón por pedido. Mensajes de error claros (inexistente, caducado, mínimo no alcanzado).
- **Stock**: se valida al crear el pedido (dentro de `DB::transaction`, con `lockForUpdate()`) y se
  descuenta al crear. Si se cancela, se repone. Cantidad máxima por línea: 10.
- **Totales**: `total = subtotal - descuento + envío + IVA` (el cálculo exacto se define en un único
  servicio `PricingService` y se cubre con tests).
- **Pago simulado** (sin pasarela real, sin números de tarjeta reales):
  - `4242 4242 4242 4242` → aprobado · `4000 0000 0000 0002` → rechazado · cualquier otro → rechazado
    con motivo "tarjeta de prueba no válida".
  - Validar formato (Luhn opcional), caducidad futura y CVV de 3 dígitos, **sin persistirlos**; guardar solo `last4`.
  - Aprobado → pedido `paid_simulated` → automáticamente `pending_preparation`.
  - Rechazado → el pedido sigue en `created` y permite reintentar; tras 3 rechazos pasa a `incident`.
- **Identificador de pedido**: `ORD-YYYYMMDD-XXXXXX` (6 alfanuméricos aleatorios, reintento si colisiona).

## Estados del pedido y transiciones

```
created ──pago ok──► paid_simulated ──auto──► pending_preparation ──admin──► shipped
   │                                              │
   ├──cancelar/admin──► cancelled ◄───────────────┘ (solo antes de shipped)
   └──3 pagos rechazados / ticket de incidencia──► incident ──admin──► (pending_preparation | cancelled)
```

Cada transición se valida en el servicio (tabla de transiciones permitidas), se guarda en
`order_status_history` y, si procede, genera evento. Transiciones no permitidas → HTTP 409.

## Validación y seguridad mínima

- Servidor: validar todo con **Form Requests** (tipos, longitudes, rangos, formato email, código postal
  español `^\d{5}$`). Cliente: validación en formularios para UX, nunca como única barrera.
- Eloquent / query builder (consultas parametrizadas); protegerse del *mass assignment* con `$fillable`
  explícito. Salida con API Resources; en React no usar `dangerouslySetInnerHTML`.
- Sesión: Sanctum SPA, `session()->regenerate()` al login, cookie `HttpOnly`, `SameSite=Lax`,
  `SESSION_SECURE_COOKIE=true` en producción. CSRF activo en rutas con estado.
- Autorización por recurso con **Policies**: un cliente solo ve sus pedidos.
- `throttle` en login y en `/api/events` (rate limiting nativo de Laravel).
- Contraseñas con `Hash::make` (bcrypt/argon). No registrar contraseñas, tarjetas ni datos personales
  innecesarios en logs ni en `payload` de eventos.
- Ningún secreto en el repo: todos los `.env` en `.gitignore`; `APP_DEBUG=false` y `APP_KEY` propia en producción.
