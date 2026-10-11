# Piquantum — canal digital de venta instrumentado (prototipo académico)

> ⚠️ **Prototipo académico sin actividad comercial real.** Datos ficticios, pagos simulados, nada se envía.
> Asignatura: Soluciones Informáticas para la Empresa · Tarea 1.

Tienda online de salsas picantes (empresa ficticia **Piquantum**) hecha con **Laravel (PHP) + Blade + MariaDB/MySQL**.
Genera pedidos y **eventos de negocio** que otro sistema podrá consumir en la Tarea 2.

## Funcionalidad

- Portada, catálogo por categorías y ficha de producto (9 productos, 4 categorías).
- Carrito: los clientes registrados lo tienen guardado en la base de datos (se conserva entre sesiones); los invitados, en la sesión. Al iniciar sesión, el carrito de invitado se fusiona con el guardado. Se comprueba el stock de cada tamaño y se avisa si una línea supera el stock actual.
- Envío (gratis desde 60 €), IVA (21 % incluido) y código de descuento, que se aplica en el checkout.
- Cuentas de cliente: registro, inicio y cierre de sesión. Para comprar hay que tener cuenta.
- Área de cliente (`/mi-cuenta`): pedidos en curso y anteriores, facturas en PDF, cambio de contraseña, dirección por defecto y baja de la cuenta.
- Facturas con numeración correlativa por año (`FAC-2026-0001`), emitidas en la misma transacción que el pago aprobado.
- Imagen de cada salsa y de cada tamaño (ilustraciones SVG generadas con `php artisan piquantum:product-images`). En la ficha, al elegir otro tamaño cambian la imagen, el precio y el stock. Se pueden sustituir por fotos reales cambiando la columna `image` de `products` o `product_variants`.
- Checkout con validación y pedido con referencia única (`PQ-YYYYMMDD-XXXXXX`). Pago con **tarjeta en el TPV Virtual de Redsys (entorno de pruebas)** o por **transferencia simulada**. Pedido, líneas, stock, ficha de cliente y eventos se guardan en una única transacción: si un paso falla, no se guarda nada.
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

Tablas: `categories`, `products`, `customers`, `orders`, `order_items`, `payments`, `invoices`, `carts`, `cart_items`, `events`, `support_tickets`, `users` (admin y clientes de prueba).

Dependencia añadida: `barryvdh/laravel-dompdf`, para generar las facturas en PDF desde una vista Blade. Se eligió frente a generar el PDF a mano o depender de un servicio externo porque es la integración estándar de Laravel y no necesita binarios en el servidor (funciona en un hosting PHP compartido).

## Eventos

Se guardan en la tabla `events` (`type`, `occurred_at`, `session_id`, `user_id`, `order_id`, `payload` JSON) mediante `EventLogger`.

- `user_id`: usuario que provocó el evento. Es el cliente conectado, el administrador en los cambios hechos desde el back-office o `null` si era un invitado. Al iniciar sesión o registrarse, los eventos que el visitante hizo como invitado **en esa misma visita** pasan a tener su `user_id`; los de otras visitas quedan anónimos.
- `user_id` es un dato personal (identifica a un cliente). Se guarda para poder analizar el comportamiento por cliente en la Tarea 2, por ejemplo carritos abandonados. Los eventos no guardan nombre, correo ni dirección.

| Evento | Se genera cuando |
|---|---|
| `product.viewed` | se abre una ficha de producto |
| `cart.item_added` | se añade un producto al carrito (`quantity` = unidades que entraron, `requested_quantity` = las pedidas) |
| `cart.item_updated` | se cambia la cantidad de una línea del carrito (`from` → `to`) |
| `cart.item_removed` | se quita una línea del carrito (o se pone a 0) |
| `checkout.started` | se entra en el checkout |
| `order.created` | se crea el pedido |
| `payment.simulated` | se resuelve un pago: respuesta firmada del TPV de Redsys en pruebas (`gateway: redsys`, con `response_code`) o transferencia simulada (`gateway: simulado`) |
| `order.status_changed` | cambia el estado de un pedido |
| `support.requested` | se envía una solicitud de soporte |
| `invoice.issued` | se emite la factura de un pedido pagado |

Consumo desde otro sistema (Tarea 2), con sesión de administrador: `GET /admin/eventos.json` y `GET /admin/eventos.csv` (parámetros opcionales `tipo` y `desde`).

## Pago con el TPV Virtual de Redsys (entorno de pruebas)

Integración por **redirección** con la librería oficial de Redsys (`backend/packages/redsys-lib`, versión 2.0.1, instalada con Composer como repositorio local). La lógica está en `app/Services/RedsysGateway.php`.

1. **Inicio:** al elegir "Tarjeta", se crea un pago en estado `pending` con su número de pedido para Redsys (`gateway_order`, 12 dígitos, uno por intento). El cliente va al TPV con un formulario **firmado** (HMAC SHA-512) que incluye importe en céntimos, `urlOk`/`urlKo` y la URL de notificación.
2. **Notificación** (`POST /pagos/redsys/notificacion`): Redsys envía el resultado firmado a la tienda. Se verifica la firma y que el importe coincide, y en una transacción se guarda el resultado. Si se aprueba (`Ds_Response` 0000-0099): pedido pagado, factura y pendiente de preparación. Está excluida de CSRF porque la llama el servidor de Redsys; la seguridad está en la firma.
3. **Retorno** (`/pagos/redsys/{pago}/ok|ko`): Redsys devuelve al cliente con la misma respuesta firmada, que se procesa igual. Es **idempotente**: si llegan la notificación y el retorno, solo cuenta una vez. Una `urlOk` sin firmar no cambia nada.

Configuración: `REDSYS_API_KEY` en `.env`. La de `.env.example` es la clave **pública** del comercio de pruebas de Redsys (999008881-001); un comercio real usaría la suya, sin subirla al repositorio. En el hosting, `APP_URL` debe ser la URL pública (https) para que Redsys pueda enviar las notificaciones. **En local**, Redsys no puede notificar a `localhost`, así que el pago se confirma con la respuesta firmada del retorno.

Dependencia añadida: librería PHP oficial de Redsys. Evita implementar a mano la firma, la codificación y los endpoints del TPV. La guía PDF y los ejemplos descargados de Redsys no se suben al repositorio (`.gitignore`).

## Correos

La tienda envía correos por **SMTP con la cuenta de correo del hosting (DonDominio)**. Todo pasa por `app/Services/ShopMailer.php`; los correos están en `app/Mail` y sus plantillas en `resources/views/mail`.

| Evento | Destinatario | Correo |
|---|---|---|
| Registro | cliente | Bienvenida |
| Pago aprobado (Redsys o transferencia) | cliente | Resumen del pedido con la **factura PDF adjunta** |
| El back-office marca el pedido como enviado | cliente | Pedido en camino |
| Solicitud de soporte | cliente + tienda (`SHOP_NOTIFY_EMAIL`) | Acuse de recibo, y aviso interno con "responder a" el cliente |

- Los correos se envían **después de confirmar la transacción** (`DB::afterCommit`): nunca se avisa de un pago que luego se ha revertido.
- Si el SMTP falla, el error queda en el log y **el registro, el pago o el pedido no se ven afectados**.
- **Local:** `MAIL_MAILER=log` (los correos se escriben en `storage/logs/laravel.log`).
- **Hosting:** `MAIL_MAILER=smtp` con el servidor, puerto, usuario y contraseña que indica el panel de DonDominio. `MAIL_FROM_ADDRESS` debe ser **la misma cuenta**, para que SPF y DKIM del dominio validen el correo y no acabe en spam. La contraseña solo va en el `.env` del servidor.
- ⚠️ Los correos se envían a la dirección que el cliente indica al registrarse. Como es un prototipo académico, conviene usar direcciones propias o de prueba: si alguien se registra con un correo ajeno, le llegarán correos.

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
| Back-office | `admin@piquantum.test` / `admin1234` (cuenta de prueba, no real; solo gestiona: no puede comprar ni usar "Mi cuenta") |
| Clientes | `laura@example.com`, `carlos@example.com`, `marta@example.com` / `cliente1234` (cuentas de prueba) |
| Tarjetas (TPV Redsys) | Las [tarjetas de prueba oficiales de Redsys](https://pagosonline.redsys.es/desarrolladores-inicio/integrate-con-nosotros/tarjetas-y-entornos-de-prueba/). Nunca una tarjeta real |
| Códigos de descuento | `BIENVENIDA10` (−10 %), `ACADEMICO15` (−15 %) |

La tienda **nunca recibe el número de tarjeta**: se introduce en la página de Redsys, y de su respuesta solo se guardan los 4 últimos dígitos (enmascarados por Redsys).

## Despliegue en hosting PHP

1. Subir `backend/` al hosting y apuntar el *document root* a `backend/public` (o usar un `.htaccess` que redirija a `public/`).
2. Crear `.env` **en el servidor** (nunca en Git) con `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` y los datos de la BD del hosting.
3. `php artisan key:generate`, `php artisan migrate --seed --force`, `php artisan config:cache`.
4. Cambiar la contraseña del admin de prueba si la URL es pública.

**Versión de PHP:** el `composer.lock` actual exige PHP ≥ 8.4.1 (por Symfony 8). Si el hosting tiene una versión menor, ejecutar en local `composer config platform.php 8.3.0 && composer update` y subir el nuevo `composer.lock`.

## Limitaciones conocidas

- El pago con tarjeta usa el TPV de Redsys en su **entorno de pruebas** (no se cobra nada); la transferencia, los envíos y los impuestos son **simulados**. Las facturas **no tienen validez fiscal** (empresa y CIF ficticios) y no hay facturas rectificativas: cancelar un pedido pagado no anula su factura.
- El carrito de un invitado vive en la sesión y se pierde al caducar; solo se conserva si inicia sesión o se registra en esa visita.
- El carrito no reserva stock: se avisa si una línea supera el stock actual y el checkout lo rechaza si no se corrige.
- Un usuario dado de baja (soft delete) conserva su correo ocupado y no puede volver a registrarse con él.
- El nombre y el correo de la cuenta no se pueden cambiar desde el área de cliente.
- No se puede dar de baja una cuenta con pedidos en curso.
- Una sola cuenta de back-office; sin roles ni auditoría de cambios de estado más allá del evento.
- Sin pruebas de carga ni gestión de concurrencia avanzada (solo bloqueo de stock en el pedido).
- Las imágenes de producto son ilustraciones SVG generadas por código, no fotografías.

## Punto de partida y uso de IA

_Completar para la memoria (obligatorio): proyecto Laravel generado con `composer create-project`; la implementación de la tienda fue asistida por IA generativa (Claude). Documentar en el anexo qué se generó, errores detectados, cambios del grupo y cómo se validó._
