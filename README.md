# Piquantum — canal digital de venta instrumentado (prototipo académico)

> ⚠️ **Prototipo académico sin actividad comercial real.** Datos ficticios, pagos simulados, nada se envía.
> Asignatura: Soluciones Informáticas para la Empresa · Tarea 1.

Tienda online de salsas picantes (empresa ficticia **Piquantum**) hecha con **Laravel (PHP) + Blade + MariaDB/MySQL**.
Genera pedidos y **eventos de negocio** que otro sistema podrá consumir en la Tarea 2.

## Funcionalidad

- Portada, catálogo por categorías y ficha de producto (9 productos, 4 categorías).
- Carrito en sesión, código de descuento, envío (gratis desde 60 €) e IVA (21 % incluido).
- Cuentas de cliente: registro, inicio y cierre de sesión. Para comprar hay que tener cuenta.
- Área de cliente (`/mi-cuenta`): pedidos en curso y anteriores, facturas en PDF, cambio de contraseña, dirección por defecto y baja de la cuenta.
- Facturas con numeración correlativa por año (`FAC-2026-0001`), emitidas en la misma transacción que el pago aprobado.
- Imagen de cada salsa y de cada tamaño (ilustraciones SVG generadas con `php artisan piquantum:product-images`). En la ficha, al elegir otro tamaño cambian la imagen, el precio y el stock. Se pueden sustituir por fotos reales cambiando la columna `image` de `products` o `product_variants`.
- Checkout con validación, **pago simulado** (tarjeta/transferencia) y pedido con referencia única (`PQ-YYYYMMDD-XXXXXX`). Pedido, líneas, stock, ficha de cliente y eventos se guardan en una única transacción: si un paso falla, no se guarda nada.
- Cada pedido guarda su propia dirección de envío (copia histórica) y solo lo puede ver y pagar su dueño.
- Estados de pedido: `creado → pagado_simulado → pendiente_preparacion → enviado`, además de `cancelado` e `incidencia`.
- Back-office (`/admin/login`): pedidos con cambio de estado, eventos (filtro + exportación JSON/CSV) y solicitudes de soporte.
- Formulario de soporte/incidencias (`/soporte`).

## Arquitectura (separación de responsabilidades)

| Capa | Dónde |
|---|---|
| Interfaz | `resources/views/` (Blade), `public/css/app.css` |
| Controladores (HTTP, validación) | `app/Http/Controllers/` |
| Lógica de negocio | `app/Services/` → `CartService` (importes), `OrderService` (pedido/estados/stock), `PaymentSimulator`, `EventLogger` |
| Persistencia | `app/Models/` (Eloquent) + `database/migrations/` + `database/seeders/` |
| Reglas configurables | `config/shop.php` (IVA, envío, códigos de descuento, datos de la empresa ficticia para las facturas) |

Tablas: `categories`, `products`, `customers`, `orders`, `order_items`, `payments`, `invoices`, `events`, `support_tickets`, `users` (admin y clientes de prueba).

Dependencia añadida: `barryvdh/laravel-dompdf`, para generar las facturas en PDF desde una vista Blade. Se eligió frente a generar el PDF a mano o depender de un servicio externo porque es la integración estándar de Laravel y no necesita binarios en el servidor (funciona en un hosting PHP compartido).

## Eventos

Se guardan en la tabla `events` (`type`, `occurred_at`, `session_id`, `order_id`, `payload` JSON) mediante `EventLogger`.

| Evento | Se genera cuando |
|---|---|
| `product.viewed` | se abre una ficha de producto |
| `cart.item_added` | se añade un producto al carrito |
| `checkout.started` | se entra en el checkout |
| `order.created` | se crea el pedido |
| `payment.simulated` | se intenta un pago simulado (aprobado o rechazado) |
| `order.status_changed` | cambia el estado de un pedido |
| `support.requested` | se envía una solicitud de soporte |
| `invoice.issued` | se emite la factura de un pedido pagado |

Consumo desde otro sistema (Tarea 2), con sesión de administrador: `GET /admin/eventos.json` y `GET /admin/eventos.csv` (parámetros opcionales `tipo` y `desde`).

## Instalación local

Requisitos: PHP 8.3+ (ver nota de versión), Composer y MariaDB/MySQL (`docker compose up -d` levanta una MariaDB).

```bash
cd backend
composer install
cp .env.example .env        # en Windows: copy .env.example .env
php artisan key:generate
# Edita .env: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD (ver nota)
php artisan migrate --seed
php artisan serve           # http://127.0.0.1:8000
```

Notas:
- Con el `docker-compose.yml` del repo la contraseña de root es la que define el compose; pon la misma en `DB_PASSWORD`. Con Laragon, `root` sin contraseña.
- Tests: `php artisan test` (usan SQLite en memoria).

## Usuarios y datos de prueba

| Uso | Dato |
|---|---|
| Back-office | `admin@piquantum.test` / `admin1234` (cuenta de prueba, no real) |
| Clientes | `laura@example.com`, `carlos@example.com`, `marta@example.com` / `cliente1234` (cuentas de prueba) |
| Tarjeta aprobada | `4242 4242 4242 4242`, caducidad `12/30`, CVV `123` |
| Tarjeta rechazada | `4000 0000 0000 0002` |
| Códigos de descuento | `BIENVENIDA10` (−10 %), `ACADEMICO15` (−15 %) |

El número de tarjeta **no se guarda**: solo los 4 últimos dígitos.

## Despliegue en hosting PHP

1. Subir `backend/` al hosting y apuntar el *document root* a `backend/public` (o usar un `.htaccess` que redirija a `public/`).
2. Crear `.env` **en el servidor** (nunca en Git) con `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` y los datos de la BD del hosting.
3. `php artisan key:generate`, `php artisan migrate --seed --force`, `php artisan config:cache`.
4. Cambiar la contraseña del admin de prueba si la URL es pública.

**Versión de PHP:** el `composer.lock` actual exige PHP ≥ 8.4.1 (por Symfony 8). Si el hosting tiene una versión menor, ejecutar en local `composer config platform.php 8.3.0 && composer update` y subir el nuevo `composer.lock`.

## Limitaciones conocidas

- Pagos, envíos e impuestos **simulados**; sin pasarela real. Las facturas **no tienen validez fiscal** (empresa y CIF ficticios) y no hay facturas rectificativas: cancelar un pedido pagado no anula su factura.
- Carrito en sesión (se pierde al caducar la sesión o al cerrar sesión).
- Un usuario dado de baja (soft delete) conserva su correo ocupado y no puede volver a registrarse con él.
- El nombre y el correo de la cuenta no se pueden cambiar desde el área de cliente.
- No se puede dar de baja una cuenta con pedidos en curso.
- Una sola cuenta de back-office; sin roles ni auditoría de cambios de estado más allá del evento.
- Sin pruebas de carga ni gestión de concurrencia avanzada (solo bloqueo de stock en el pedido).
- Las imágenes de producto son ilustraciones SVG generadas por código, no fotografías.

## Punto de partida y uso de IA

_Completar para la memoria (obligatorio): proyecto Laravel generado con `composer create-project`; la implementación de la tienda fue asistida por IA generativa (Claude). Documentar en el anexo qué se generó, errores detectados, cambios del grupo y cómo se validó._
