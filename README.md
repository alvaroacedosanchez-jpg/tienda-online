# Piquantum — canal digital de venta instrumentado (prototipo académico)

> ⚠️ **Prototipo académico sin actividad comercial real.** Datos ficticios; el pago con tarjeta usa el TPV de Redsys en su entorno de pruebas (o una transferencia simulada) y los productos no se envían.
> Asignatura: Soluciones Informáticas para la Empresa · Tarea 1.

Tienda online de salsas picantes (empresa ficticia **Piquantum**) hecha con **Laravel 13 (PHP) + Blade + MariaDB 12.1** (compatible con MySQL).
Genera pedidos y eventos de negocio.

En la versión publicada, los clientes de prueba son los de la tabla [Usuarios y datos de prueba](#usuarios-y-datos-de-prueba). La contraseña del administrador **se ha cambiado** para que no sea pública y se facilita al profesorado en la entrega del Campus Virtual.

## Funcionalidad

- Portada, catálogo por categorías y ficha de producto (9 productos, 4 categorías).
- Tamaños de producto (50, 100 y 200 ml), cada uno con su propio precio, stock e imagen; al elegir otro tamaño en la ficha cambian la imagen, el precio y el stock.
- Carrito: los clientes registrados lo tienen guardado en la base de datos (se conserva entre sesiones); los invitados, en la sesión. Al iniciar sesión, el carrito de invitado se fusiona con el guardado. Se comprueba el stock de cada tamaño y se avisa si una línea supera el stock actual.
- Envío (gratis desde 60 €), IVA (21 % incluido) y código de descuento, que se aplica en el checkout.
- Cuentas de cliente: registro, inicio y cierre de sesión. Para comprar hay que tener cuenta.
- Área de cliente (`/mi-cuenta`): pedidos en curso y anteriores, facturas en PDF, cambio de contraseña, dirección por defecto y baja de la cuenta.
- Facturas con numeración correlativa por año (`FAC-2026-0001`), emitidas en la misma transacción que el pago aprobado.
- Checkout con validación y pedido con referencia única (`PQ-YYYYMMDD-XXXXXX`). Pago con **tarjeta en el TPV Virtual de Redsys (entorno de pruebas)** o por **transferencia simulada**. Pedido, líneas, stock, ficha de cliente y eventos se guardan en una única transacción: si un paso falla, no se guarda nada.
- Cada pedido guarda su propia dirección de envío (copia histórica) y solo lo puede ver y pagar su dueño.
- Estados de pedido: `creado → pagado_simulado → pendiente_preparacion → enviado`, además de `cancelado` e `incidencia`.
- Correos al cliente (bienvenida, pedido pagado con la factura, pedido enviado, soporte): ver [Correos](#correos).
- Back-office (`/admin/login`):
  - dashboard con facturación, pedidos pendientes, clientes y alertas de stock bajo;
  - pedidos con cambio de estado y etiqueta de envío imprimible;
  - clientes;
  - stock con recepción de mercancía por código (SKU, o SKU + tamaño, pensado para lector de códigos);
  - eventos (filtro y exportación JSON/CSV);
  - incidencias de soporte.
- La cuenta de administrador solo gestiona: no puede usar el carrito, comprar ni entrar en "Mi cuenta" (middleware `customer`).
- Formulario de soporte/incidencias (`/soporte`).
- Página de privacidad y datos (`/privacidad`), páginas de error propias (404, 419 y 500), enlace "saltar al contenido" para accesibilidad y `robots.txt` que excluye las zonas privadas.

## Arquitectura (separación de responsabilidades)

| Capa | Dónde |
|---|---|
| Interfaz | `resources/views/` (Blade), `public/css/app.css` |
| Acceso | `routes/web.php` + middleware `auth`, `guest`, `customer` (`EnsureCustomer`) y `admin` (`EnsureAdmin`) |
| Controladores (HTTP, validación) | `app/Http/Controllers/` |
| Lógica de negocio | `app/Services/` → `CartService` (carrito e importes), `OrderService` (pedido, estados, stock y cancelación), `RedsysGateway` (TPV de Redsys), `PaymentSimulator` (transferencia simulada), `EventLogger` (eventos), `ShopMailer` (correos) |
| Persistencia | `app/Models/` (Eloquent) + `database/migrations/` + `database/seeders/` |
| Reglas configurables | `config/shop.php` (IVA, envío, códigos de descuento, datos de la empresa ficticia para las facturas) |

Tablas de negocio: `categories`, `products`, `product_variants`, `customers`, `orders`, `order_items`, `payments`, `invoices`, `carts`, `cart_items`, `events`, `support_tickets` y `users` (admin y clientes de prueba), más la vista `eventos_bi` y las tablas propias de Laravel. Diccionario de datos: [`docs/Diccionario_Datos_Piquantum.xlsx`](docs/Diccionario_Datos_Piquantum.xlsx); diagrama entidad-relación: [`docs/diagrama-er.png`](docs/diagrama-er.png).

Dependencia añadida: `barryvdh/laravel-dompdf`, para generar las facturas en PDF desde una vista Blade. Se eligió frente a generar el PDF a mano o depender de un servicio externo porque es la integración estándar de Laravel y no necesita binarios en el servidor (funciona en un hosting PHP compartido).

## Eventos

Se guardan en la tabla `events` mediante `EventLogger::log()`, que llaman los controladores y servicios. Los eventos de pedido, pago y factura se registran dentro de la misma transacción que la operación: si esta se revierte, el evento tampoco queda.

Cada evento tiene columnas **tipadas**, y cada tipo rellena las suyas:

| Columnas | Contenido |
|---|---|
| `type`, `occurred_at`, `session_id`, `user_id` | Tipo, fecha, sesión y usuario (en todos los eventos) |
| `order_id`, `product_id`, `product_variant_id`, `payment_id`, `invoice_id`, `support_ticket_id` | Claves foráneas a lo que afecta el evento (`set null` al borrar) |
| `quantity`, `previous_quantity`, `unit_price`, `amount` | Cantidades e importes |
| `status`, `previous_status`, `origin` | Estado resultante, estado anterior y quién lo provocó (`payment`, `redsys`, `cliente`, `backoffice`…) |
| `payload` (JSON) | Solo detalles secundarios: SKU, tamaño, cantidad pedida, referencias… |

Los datos que ya están en otra tabla no se repiten: el método de pago, la pasarela y el código de respuesta de Redsys se obtienen del pago (`payment_id`), y el código de descuento, del pedido (`order_id`).

- `user_id`: usuario que provocó el evento. Es el cliente conectado, el administrador en los cambios hechos desde el back-office o `null` si era un invitado. Al iniciar sesión o registrarse, los eventos que el visitante hizo como invitado **en esa misma visita** pasan a tener su `user_id`; los de otras visitas quedan anónimos.
- `user_id` es un dato personal (identifica a un cliente). Se guarda para poder analizar el comportamiento por cliente en la Tarea 2, por ejemplo carritos abandonados. Los eventos no guardan nombre, correo ni dirección.

| Evento | Se genera cuando | Columnas principales |
|---|---|---|
| `product.viewed` | se abre una ficha de producto | `product_id` |
| `cart.item_added` | se añade un producto al carrito | `product_id`, `product_variant_id`, `quantity` (unidades que entraron), `unit_price`, `amount`; en `payload`, `requested_quantity` (las pedidas) |
| `cart.item_updated` | se cambia la cantidad de una línea del carrito | `previous_quantity` → `quantity` |
| `cart.item_removed` | se quita una línea del carrito (o se pone a 0) | `product_id`, `product_variant_id`, `quantity` |
| `checkout.started` | se entra en el checkout | `quantity` (unidades del carrito), `amount` (total) |
| `order.created` | se crea el pedido | `order_id`, `quantity`, `amount` |
| `payment.simulated` | se resuelve un pago: respuesta firmada del TPV de Redsys en pruebas o transferencia simulada | `order_id`, `payment_id`, `status`, `amount`, `origin` (`cliente` si se canceló en Redsys) |
| `order.status_changed` | cambia el estado de un pedido | `order_id`, `previous_status`, `status`, `origin` |
| `invoice.issued` | se emite la factura de un pedido pagado | `order_id`, `invoice_id`, `amount` |
| `support.requested` | se envía una solicitud de soporte | `support_ticket_id`, `order_id` (si lo indicó) |

Consumo desde otro sistema (Tarea 2):

- **Vista SQL `eventos_bi`**, para conectar Power BI (o cualquier herramienta de BI) directamente a la base de datos. Une cada evento con su producto, categoría, tamaño, pedido, pago, factura, ticket y cliente, con columnas en español y el estado traducido. No incluye el correo ni la dirección de los clientes.
- **Exportación** con sesión de administrador: `GET /admin/eventos.json` y `GET /admin/eventos.csv` (parámetros opcionales `tipo` y `desde`). Incluyen todas las columnas y los nombres legibles; el CSV usa `;` como separador y UTF-8 con BOM, para abrirlo directamente en Excel o Power BI.

## Pago con el TPV Virtual de Redsys (entorno de pruebas)

Integración por **redirección** con la librería oficial de Redsys (`backend/packages/redsys-lib`, versión 2.0.1, instalada con Composer como repositorio local). La lógica está en `app/Services/RedsysGateway.php`.

1. **Inicio:** al elegir "Tarjeta", se crea un pago en estado `pending` con su número de pedido para Redsys (`gateway_order`, 12 dígitos, uno por intento). El cliente va al TPV con un formulario **firmado** (HMAC SHA-512) que incluye importe en céntimos, `urlOk`/`urlKo` y la URL de notificación.
2. **Notificación** (`POST /pagos/redsys/notificacion`): Redsys envía el resultado firmado a la tienda. Se verifica la firma y que el importe coincide, y en una transacción se guarda el resultado. Si se aprueba (`Ds_Response` 0000-0099): pedido pagado, factura y pendiente de preparación. Está excluida de CSRF porque la llama el servidor de Redsys; la seguridad está en la firma.
   - **Pago rechazado o cancelado** (en la notificación, en la vuelta firmada o al volver por `urlKo` tras pulsar "Cancelar" en Redsys): el pedido se **cancela al instante**, el **stock se devuelve** y los productos **vuelven al carrito** del cliente para que pueda reintentar. No se emite factura.
   - Si llega un pago **aprobado** de un pedido que ya no esperaba pago (por ejemplo, cancelado en otra pestaña), el cobro no se pierde: el pedido pasa a **"Con incidencia"** para revisarlo y devolverlo desde el back-office.
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

Requisitos: PHP 8.3+, Composer y MariaDB (o MySQL). El `docker-compose.yml` de la raíz levanta una **MariaDB 12.1** con la base de datos `piquantum_db` en el puerto 3306.

```bash
docker compose up -d        # desde la raíz del repositorio (opcional, si no tienes MariaDB)
cd backend
composer install
cp .env.example .env        # en Windows: copy .env.example .env
php artisan key:generate
# Edita .env: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD (ver nota)
php artisan migrate --seed
php artisan serve           # http://127.0.0.1:8000
```

Notas:
- Con el `docker-compose.yml`: `DB_HOST=127.0.0.1`, `DB_DATABASE=piquantum_db`, `DB_USERNAME=root` y en `DB_PASSWORD` la contraseña de root que define el compose (es solo para desarrollo local, no es una credencial real). Con Laragon, `root` sin contraseña.
- Las tablas se crean con el motor **InnoDB** aunque el servidor tenga otro por defecto (`DB_ENGINE` en `config/database.php`).
- Imágenes de producto: son ilustraciones SVG ya incluidas en `public/images/products/`. Para regenerarlas: `php artisan piquantum:product-images`.
- Tests: `php artisan test` (93 tests de funcionalidad en `tests/Feature`, usan SQLite en memoria).

## Usuarios y datos de prueba

| Uso | Dato |
|---|---|
| Back-office | En local, `admin@piquantum.test` / `admin1234` (cuenta de prueba, no real; solo gestiona: no puede comprar ni usar "Mi cuenta"). En la versión publicada la contraseña es otra (ver [Acceso](#acceso)) |
| Clientes | `laura@example.com`, `carlos@example.com`, `marta@example.com` / `cliente1234` (cuentas de prueba) |
| Tarjetas (TPV Redsys) | Las [tarjetas de prueba oficiales de Redsys](https://pagosonline.redsys.es/desarrolladores-inicio/integrate-con-nosotros/tarjetas-y-entornos-de-prueba/). Nunca una tarjeta real |
| Códigos de descuento | `BIENVENIDA10` (−10 %), `ACADEMICO15` (−15 %) |

La tienda **nunca recibe el número de tarjeta**: se introduce en la página de Redsys, y de su respuesta solo se guardan los 4 últimos dígitos (enmascarados por Redsys).

## Despliegue (hosting compartido de DonDominio)

La versión publicada está en un hosting PHP compartido de DonDominio, con HTTPS y MariaDB 12.1.

1. Subir por FTP (FTPS) el **contenido** de `backend/`, incluida `vendor/` (mejor instalada con `composer install --no-dev`), a la raíz del alojamiento. El dominio apunta a la carpeta `public/`.
2. Crear el `.env` **solo en el servidor** (nunca en Git), con permisos `600`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://www.piquantum.onl`, `APP_KEY` propio, los datos de la base de datos y del SMTP del hosting, `SESSION_SECURE_COOKIE=true` y `QUEUE_CONNECTION=sync` (el hosting no tiene procesos de colas).
3. Migraciones: el hosting no tiene terminal, así que se lanzan **desde local** contra la base de datos del hosting, activando temporalmente su acceso remoto en el panel de DonDominio:
   ```bash
   cd backend
   php artisan migrate --seed --env=production --force    # con un .env.production local que apunte a la BD del hosting
   ```
   Al terminar, desactivar de nuevo el acceso remoto.
4. **Cambiar la contraseña del administrador de prueba**, porque el seeder la deja en `admin1234`:
   ```bash
   php artisan tinker --env=production --execute="\$u = App\Models\User::where('email', 'admin@piquantum.test')->firstOrFail(); \$u->password = 'NUEVA_CONTRASEÑA'; \$u->save();"
   ```
   (No dejes la contraseña real en el historial de la terminal: puedes leerla antes con `read -s`.)

## Documentación

- [`docs/diagrama-er.png`](docs/diagrama-er.png): diagrama entidad-relación con las 21 tablas y la vista `eventos_bi`.
- [`docs/Diccionario_Datos_Piquantum.xlsx`](docs/Diccionario_Datos_Piquantum.xlsx): diccionario de datos.
- [`docs/uso-ia.md`](docs/uso-ia.md): registro del uso de IA generativa (base del anexo de IA de la memoria).

## Equipo

| Integrante | Aportación principal |
|---|---|
| Álvaro Acedo Sánchez | Versión inicial de la tienda; página de privacidad, páginas de error, accesibilidad y `robots.txt` |
| Abel Martínez Candela | Cuentas de cliente, checkout transaccional, área de cliente y facturas, carrito persistente, TPV de Redsys, correos, despliegue y revisión de los eventos |
| Antonio Martínez Pallarés | Tamaños de producto, inventario y recepción de stock, dashboard, clientes y etiqueta de envío del back-office, eventos con columnas tipadas y vista para Power BI |

El detalle de cada contribución está en el historial de commits.

## Limitaciones conocidas

- El pago con tarjeta usa el TPV de Redsys en su **entorno de pruebas** (no se cobra nada); la transferencia, los envíos y los impuestos son **simulados**. Las facturas **no tienen validez fiscal** (empresa y CIF ficticios) y no hay facturas rectificativas: cancelar un pedido pagado no anula su factura.
- El carrito de un invitado vive en la sesión y se pierde al caducar; solo se conserva si inicia sesión o se registra en esa visita.
- El carrito no reserva stock: se avisa si una línea supera el stock actual y el checkout lo rechaza si no se corrige.
- El stock se descuenta al crear el pedido. Si el pago se cancela o se rechaza, el pedido se cancela y el stock se libera al momento; pero si el cliente **abandona** la página de Redsys sin volver a la tienda, el pedido queda en "Creado" con el stock reservado hasta que el back-office lo cancele (no hay caducidad automática).
- Un usuario dado de baja (soft delete) conserva su correo ocupado y no puede volver a registrarse con él.
- El nombre y el correo de la cuenta no se pueden cambiar desde el área de cliente.
- No se puede dar de baja una cuenta con pedidos en curso.
- Una sola cuenta de back-office; sin roles ni auditoría de cambios de estado más allá del evento.
- Sin pruebas de carga ni gestión de concurrencia avanzada (solo bloqueo de stock en el pedido).
- Las imágenes de producto son ilustraciones SVG generadas por código, no fotografías.
