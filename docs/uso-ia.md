# Registro de uso de IA generativa

Registro vivo para preparar el **anexo de uso de IA** de la memoria (enunciado, apartado 7) y la **declaración del punto de partida** (apartado 8.2).
Cada miembro añade una entrada cada vez que use IA. Mejor anotar de más que de menos: en el anexo se resume.

## Herramientas utilizadas (7.a)

| Herramienta | Modelo / versión | Quién la usa | Para qué, en general |
|---|---|---|---|
| Claude Code (extensión VS Code) | Claude Opus 5.5 | Abel | Mentoría, revisión de código, Git, análisis de requisitos |
| Claude | _(completar)_ | Álvaro | Generación de la versión inicial de la tienda (commit `9a36415`, según el README) |
| _(completar)_ | | Antonio | |

## Punto de partida del proyecto (8.2)

- Proyecto Laravel 13 creado con `composer create-project` (commit `ce1e76c`).
- La implementación de la tienda (catálogo, carrito, checkout, pedidos, eventos y back-office) se generó en gran parte con IA en un único commit (`9a36415`, unas 2.400 líneas). **Completar:** qué prompt o especificación se usó y quién lo revisó.

## Entradas

Plantilla para cada entrada:

```
### AAAA-MM-DD · Quién · Tema
- Tarea (7.b):
- Partes asistidas (7.c): archivos / commits
- Errores detectados en la IA (7.d):
- Cambios hechos por el grupo (7.e):
- Validación (7.f):
```

### 2026-10-08 · Abel · Uso de Git con ramas del equipo
- **Tarea:** aprender a descargar y revisar la rama de un compañero (`git fetch`, `git switch`).
- **Partes asistidas:** ninguna de código; solo explicación de comandos.
- **Errores detectados en la IA:** ninguno.
- **Cambios del grupo:** —
- **Validación:** comandos ejecutados por Abel en su terminal.

### 2026-10-08 · Abel · Cambio de temática a salsas picantes
- **Tarea:** sustituir los productos de ejemplo (accesorios de escritorio) por salsas picantes.
- **Partes asistidas:** la IA escribió 4 categorías y 9 productos en `backend/database/seeders/DatabaseSeeder.php`, el `tagline` de `backend/config/shop.php`, el test de importes de `backend/tests/Feature/PurchaseFlowTest.php` y la descripción del `README.md` (commit `6c4d6ea`).
- **Errores detectados en la IA:** ninguno en esta parte. La IA detectó que un test dependía del SKU `TEC-002` del seeder anterior y lo adaptó (`SAL-003`, total recalculado a 10,26 €).
- **Cambios del grupo:** _(completar si se ajustan nombres, precios o descripciones)_
- **Validación:** `php artisan migrate:fresh --seed` (9 productos, 4 categorías) y `php artisan test`. Se comprobó que los 2 tests que fallaban ya fallaban con el código anterior.

### 2026-10-08 · Abel · Auditoría del código generado por IA
- **Tarea:** revisar la tienda generada en `9a36415` frente a los requisitos.
- **Partes asistidas:** análisis de solo lectura de todo `backend/`.
- **Errores detectados en el código generado por IA:**
  1. `AppServiceProvider` usaba la vista `pagination::default`, que no existe en Laravel 13. Provocaba un error 500 en el back-office. **Corregido por Antonio** (commit `4cf991c`, `Paginator::useBootstrapFive()`).
  2. `tests/Feature/ExampleTest.php` falla porque `RefreshDatabase` está comentado. **Resuelto** en el paso 5 de cuentas de usuario.
  3. La tabla de transiciones de estado (`Order::TRANSITIONS`) no permite `creado → pagado_simulado`, pero `PaymentSimulator` hace esa transición, y `OrderService::changeStatus` no valida transiciones. _(Pendiente)_
  4. Los totales del pedido se calculan con precios leídos antes del bloqueo de stock, y el carrito se vacía fuera de la transacción. **Resuelto** en el paso 5: los totales se recalculan con los precios bloqueados. Vaciar el carrito fuera de la transacción es correcto, porque es sesión y no base de datos; se hace solo si la transacción confirma.
  5. Privacidad: cualquiera con la referencia `PQ-...` puede ver los datos del cliente y pagar el pedido. **Resuelto** en el paso 5: el checkout exige sesión y solo el dueño ve y paga su pedido (404 para el resto).
  6. `Customer::create` crea un cliente nuevo en cada pedido, aunque el email se repita. **Resuelto** en el paso 5 de cuentas de usuario.
- **Cambios del grupo:** fusión del arreglo de paginación en `main` (merge `2ffb930`).
- **Validación:** `php artisan test` pasa de 8/10 a 9/10 tras el arreglo.

### 2026-10-08 · Abel · Análisis de cuentas de usuario para clientes
- **Tarea:** comprobar si la tienda tiene registro de clientes y analizar cómo añadirlo.
- **Partes asistidas:** análisis; sin código todavía.
- **Errores detectados en la IA:** —
- **Cambios del grupo:** Abel decide implementarlas en la rama `feature/cuentas-usuario` (ver entradas siguientes).
- **Validación:** contraste con el enunciado. Las cuentas de cliente no son obligatorias (4.b admite "clientes o usuarios de prueba").

### 2026-10-08 · Abel · Modelo de datos de cuentas de usuario (rama `feature/cuentas-usuario`)
- **Tarea:** enlazar clientes con usuarios y decidir qué pasa al borrar registros.
- **Partes asistidas:** la IA explicó los conceptos (migraciones, claves foráneas, `cascadeOnDelete` / `restrictOnDelete` / `nullOnDelete`) y revisó el código. **Abel escribió** los cambios de la migración `2026_10_08_100100_create_order_tables.php`. La IA dio resuelto el bucle de clientes del seeder (`User::updateOrCreate` + `user_id`) a petición de Abel (commit `191cc67`).
- **Decisiones del grupo (no de la IA):**
  - Todo cliente debe ser un usuario registrado: `customers.user_id` obligatorio y único.
  - Pedidos, líneas y pagos con `restrictOnDelete`: los datos transaccionales no se borran. La IA solo proponía cambiarlo en `orders`; Abel lo extendió a `order_items` y `payments`.
  - Modificar las migraciones existentes y regenerar con `migrate:fresh` en vez de crear migraciones nuevas, porque aún no hay despliegue. La IA recomendaba una migración nueva; se aceptó la decisión con la condición de no hacerlo tras desplegar.
- **Errores detectados durante la revisión:** en el código del alumno, `nullable()` y `unique()` colocados después de `constrained()` (se aplican a la clave foránea y se ignoran sin dar error). Detectado por la IA y corregido por Abel.
- **Validación:** `php artisan migrate:fresh --seed`; índice `customers_user_id_unique` comprobado en MariaDB; cada cliente de prueba con su `user_id`.

### 2026-10-09 · Abel · Relaciones, seguridad y bajas de usuarios
- **Tarea:** relaciones Eloquent `User` ↔ `Customer`, quitar `is_admin` de la asignación masiva y bajas con soft deletes.
- **Partes asistidas:** tras tres intentos de Abel con la dirección de la relación invertida, la IA dio la solución de `Customer::user()` (`belongsTo`) y `User::customer()` (`hasOne`) (commit `c5607b9`). El resto lo escribió Abel con pistas: `is_admin` fuera de `Fillable` y asignado a mano en el seeder (commit `367f5f4`), y `softDeletes()` + trait `SoftDeletes` (commit `201edbb`).
- **Errores detectados en la IA:** ninguno en esta parte.
- **Cambios del grupo:** decisión de dar de baja con soft deletes en vez de borrar. Limitación aceptada: un usuario dado de baja sigue ocupando su email.
- **Validación:** pruebas en `php artisan tinker` (relaciones en ambos sentidos; `is_admin` true en el admin y false en los clientes; tras `delete()` el usuario queda oculto, con `deleted_at` y su ficha de cliente intacta).

### 2026-10-09 · Abel · Registro de usuarios
- **Tarea:** página de registro (rutas, `RegisterController`, vista `auth/register.blade.php`).
- **Partes asistidas:** Abel escribió las rutas, la vista y la validación. La IA dio las líneas de `Auth::login`, `session()->regenerate()` y la redirección con mensaje (commit `90b7374`). La IA escribió entera, a petición de Abel, la traducción de los mensajes de validación al español (commit `9d86b28`).
- **Errores detectados en la IA:** al diseñar el paso, la IA dijo que el registro necesitaría una transacción para crear `User` y `Customer`; tras decidir que la dirección se pide en el checkout, solo se crea el `User` y la transacción no es necesaria. La IA lo rectificó.
- **Cambios del grupo:** la dirección de envío se pide en el primer checkout, no en el registro.
- **Validación:** registro real desde el navegador (usuario `abel@abel.com` creado, contraseña cifrada con `$2y$`, `is_admin = false`). Mensajes en español comprobados con el validador. **Pendiente:** probar el mensaje de email repetido.
- **Efecto colateral conocido:** desde que `customers.user_id` es obligatorio, el checkout falla (5/10 tests) hasta adaptarlo en el paso 5. No fusionar la rama con `main` antes.

### 2026-10-09 · Abel · Login y logout de clientes
- **Tarea:** rutas, `LoginController` y vista `auth/login.blade.php`.
- **Partes asistidas:** Abel escribió las rutas, la vista, la validación y `destroy` (copiado del login del admin). La IA corrigió el método `store` a petición de Abel.
- **Errores detectados durante la revisión:**
  - En el código del alumno: tres rutas con el mismo método y URL que se sobrescribían; regla `confirmed` y `min:8` en el login; llamada a un método inexistente (`User::findByEmail`) y acceso a un array como objeto; falta de `session()->regenerate()`.
  - En la IA: ninguno.
- **Decisiones de seguridad:** mensaje de error genérico para no revelar qué correos están registrados (enumeración de usuarios); logout por POST con CSRF.
- **Validación:** peticiones HTTP reales contra un servidor de pruebas: login correcto (redirige con "Bienvenido/a, Laura Prueba."), contraseña incorrecta (mensaje genérico y email conservado) y logout (redirige a la portada).

### 2026-10-09 · Abel · Menú según la sesión y rutas protegidas
- **Tarea:** que la cabecera muestre "Iniciar sesión / Crear cuenta" a los visitantes y "Hola, {nombre} / Salir" a los usuarios conectados; impedir que un usuario conectado entre en el login o el registro.
- **Partes asistidas:** la IA escribió entero este paso a petición de Abel:
  - `resources/views/layouts/app.blade.php`: bloques `@guest` / `@auth` y botón "Salir" como formulario `POST` con `@csrf`;
  - `routes/web.php`: middleware `guest` para registro y login, y `auth` para logout;
  - `public/css/app.css`: 3 reglas para que el formulario de "Salir" y el saludo queden en línea dentro del menú.
- **Errores detectados en la IA:** ninguno.
- **Cambios del grupo:** —
- **Validación:** peticiones HTTP reales contra un servidor de pruebas: menú de visitante; menú de Laura conectada; `/login` y `/registro` redirigen a la portada con sesión iniciada; "Salir" vuelve al menú de visitante; el admin ve además "Back-office"; logout sin sesión redirige a `/login`. `php artisan test` sigue en 5/10 (fallos conocidos del checkout, paso 5).

### 2026-10-09 · Abel · Checkout con usuario, transaccional (paso 5)
- **Tarea:** volver a hacer funcionar la compra ahora que todo cliente es un usuario, y hacer robusto el proceso Order-to-Cash.
- **Instrucciones y decisiones de Abel (no de la IA):**
  - Opción A: cada pedido guarda su propia dirección de envío; el cliente puede usar una dirección distinta en cada pedido.
  - La ficha de cliente se crea en la primera compra y no se actualiza después (sirve para prerrellenar el formulario).
  - El proceso debe ser transaccional: si un paso falla, se revierte todo.
  - Alcance añadido: pago transaccional (sin pagos dobles), pedidos privados y arreglar `ExampleTest`.
- **Partes asistidas:** la IA escribió entero este paso bajo esas instrucciones (plan aprobado por Abel antes de programar):
  - `routes/web.php`: checkout, pago y pedido con middleware `auth`; `redirect()->intended()` en login y registro para volver al checkout.
  - Migración `create_order_tables`: columnas `shipping_*` en `orders`; vistas de pedido (cliente y admin) leen esas columnas.
  - `OrderService::createFromCart(User, array)`: ficha de cliente (`firstOrCreate`), bloqueo de productos ordenado por id, recálculo con precios bloqueados (`CartService::summaryFor`), pedido, líneas, stock y evento `order.created` en una sola `DB::transaction`; el carrito se vacía solo tras confirmar. `changeStatus` también registra su evento dentro de la transacción.
  - Nueva `App\Exceptions\CheckoutException` para errores de negocio; los controladores ya no capturan `RuntimeException` (que incluía errores de base de datos).
  - `PaymentSimulator::pay`: transacción con `lockForUpdate` sobre el pedido y relectura del estado.
  - `Order::isOwnedBy()` y 404 para pedidos ajenos en `OrderController` y `CheckoutController`.
  - Vista del checkout sin campo de correo (se usa el de la cuenta) y prerrellenada desde la ficha del cliente.
  - Tests: login en los tests de compra y 7 tests nuevos (invitado redirigido y vuelta tras el login, dirección histórica, primera compra crea la ficha, rollback por falta de stock, rollback por fallo técnico en el último paso, privacidad y pago doble). `ExampleTest` con `RefreshDatabase`.
  - README: cuentas de cliente de prueba, transaccionalidad y limitaciones.
- **Errores detectados en la IA:** en la primera prueba manual, el script de comprobación tomaba el token CSRF de una página sin formulario (error 419). Era un fallo del script de prueba, no de la aplicación; se corrigió el script.
- **Cambios del grupo:** decisiones de diseño y alcance indicadas arriba.
- **Validación:**
  - `php artisan test`: **17/17** (antes 5/10).
  - Recorrido HTTP real: invitado con carrito → `/checkout` redirige a login → tras el login vuelve al checkout prerrellenado → pedido con otra dirección (Valencia) → pago aprobado → pedido "Pendiente de preparación" con la dirección de Valencia, mientras la ficha del cliente conserva Madrid → carrito vacío → otro cliente recibe 404 al abrir la URL del pedido.
  - En base de datos: eventos `cart.item_added`, `checkout.started`, `order.created`, `payment.simulated` y `order.status_changed` registrados.

### 2026-10-09 · Abel · Resolución del conflicto de la PR #4 con `main`
- **Tarea:** fusionar `main` en `feature/cuentas-usuario`. Antonio había añadido variantes de producto por tamaño y un inventario de stock, y los dos habían reescrito el bucle del checkout en `OrderService.php`.
- **Partes asistidas:** la IA resolvió el conflicto a petición de Abel, **combinando** las dos versiones en vez de elegir una:
  - de Abel: transacción única, bloqueo previo, precios recalculados tras el bloqueo, `CheckoutException` y dirección histórica;
  - de Antonio: precio y stock de la variante, y `product_variant_id` y `variant_size` en la línea.
  - El bloqueo y la comprobación de stock de las variantes se movieron al paso de bloqueo inicial, junto a los productos, para comprobarlo todo antes de escribir nada.
  - Se añadió una comprobación de que la variante pertenece al producto.
  - El resto de archivos (vistas, rutas, seeder, `CartService`) se fusionaron automáticamente sin conflicto.
- **Errores detectados:** la versión de `main` lanzaba `RuntimeException` dentro de la transacción; con la nueva gestión de errores se habría mostrado como un error 500 en vez de como un mensaje de stock. Se sustituyó por `CheckoutException`.
- **Cambios del grupo:** —
- **Validación:** `php artisan test` **19/19**, con 2 tests nuevos de variantes (precio y stock de la variante, y rollback sin stock de la variante). `migrate:fresh --seed` correcto con las migraciones de variantes.

### 2026-10-10 · Abel · Área de cliente "Mi cuenta": pedidos, facturas y datos (rama `feature/area-cliente`)
- **Tarea:** menú de usuario para ver el estado de los pedidos, los pedidos anteriores y las facturas, y para cambiar los datos de la cuenta.
- **Instrucciones y decisiones de Abel (no de la IA):**
  - Facturas en una tabla propia, con numeración correlativa y **PDF descargable**. Se acepta añadir la dependencia `barryvdh/laravel-dompdf`.
  - El usuario puede cambiar su **contraseña**, su **dirección por defecto** y **darse de baja**. El nombre y el correo no son editables.
  - Lo programa la IA bajo sus instrucciones, con un plan aprobado antes.
- **Partes asistidas:** la IA escribió entero este paso:
  - rutas `/mi-cuenta/*` (grupo `auth`), `AccountController`, `InvoiceController` y vistas `account/*` (resumen, pedidos en curso y anteriores, facturas, formularios);
  - enlace "Mi cuenta" en el menú, y número de factura en el detalle de pedido del back-office;
  - `User::orders()` (`hasManyThrough`), `Order::IN_PROGRESS` y la relación `Order::invoice()`;
  - migración `create_invoices_table` y modelo `Invoice::issueFor()`: número `FAC-AAAA-NNNN` correlativo por año con `lockForUpdate` y único `(year, sequence)`; copia de los datos de facturación y de los importes;
  - emisión de la factura y del evento nuevo `invoice.issued` **dentro de la transacción del pago** (`PaymentSimulator`);
  - vista `invoices/pdf.blade.php` (base imponible, IVA, datos de la empresa ficticia en `config/shop.php` y aviso de documento sin validez fiscal);
  - regla de negocio de la baja: se bloquea si hay pedidos en curso; si no, soft delete y cierre de sesión;
  - 11 tests nuevos en `tests/Feature/AccountTest.php`;
  - README: área de cliente, justificación de la dependencia, evento nuevo y limitaciones.
- **Errores detectados en la IA (y corregidos):**
  - La baja bloqueada volvía con `back()` a la página anterior, que podía no ser "Mi cuenta"; ahora redirige siempre a "Mi cuenta".
  - El PDF pesaba 878 KB porque incrustaba la fuente completa; activando `isFontSubsettingEnabled` baja a unos 25 KB.
  - En el PDF, las cabeceras de las columnas numéricas no estaban alineadas a la derecha.
- **Otras observaciones:**
  - Al instalar dompdf, Composer alineó la carpeta `vendor/` local (Symfony 8 → 7.4) con el `composer.lock` del equipo, que ya fijaba Symfony 7.4 por la compatibilidad con PHP 8.3 del hosting. El lock solo añade los paquetes de dompdf.
  - Pint ordenó los `use` de `User.php` y `routes/web.php`.
- **Cambios del grupo:** decisiones de alcance indicadas arriba.
- **Validación:**
  - `php artisan test`: **30/30** (19 anteriores + 11 nuevos: acceso de invitados, pedidos en curso y anteriores solo propios, facturas correlativas y su evento, sin factura si el pago se rechaza, **rollback del pago si falla la factura**, PDF solo para el dueño, contraseña, dirección, alta de ficha de cliente, y baja bloqueada y efectiva).
  - Recorrido HTTP real: menú "Mi cuenta (Laura Prueba)" → compra y pago → resumen con el pedido "Pendiente de preparación" y la factura `FAC-2026-0001` → descarga del PDF (`application/pdf`, empieza por `%PDF`) → cambio de dirección y de contraseña → baja bloqueada por el pedido en curso → otro cliente recibe 404 al pedir el PDF.
  - Revisión visual del PDF renderizado.

### 2026-10-10 · Abel · Imágenes de productos y variantes (rama `feature/imagenes-productos`)
- **Tarea:** una imagen por salsa y por variante de tamaño, que cambie en la ficha al elegir otro tamaño.
- **Instrucciones y decisiones de Abel (no de la IA):**
  - Ilustraciones SVG generadas por código, porque no hay fotos reales.
  - Al cambiar de variante se actualizan también el precio y el stock.
  - Imágenes en la ficha, las tarjetas del catálogo y la portada, y el carrito y el checkout.
  - Lo programa la IA, con un plan aprobado antes.
- **Partes asistidas:** la IA escribió entero este paso:
  - migración `add_image_to_products_and_variants` (columna `image`), `imageUrl()` y `defaultImagePath()` en `Product` y `ProductVariant`, y asignación en el seeder;
  - comando `php artisan piquantum:product-images`, que genera las ilustraciones SVG desde la plantilla `resources/views/images/bottle.blade.php`:
    - el color depende del chile y hay de 1 a 5 llamas según el picor;
    - el bote crece con el tamaño y los packs se dibujan como caja regalo;
    - 15 archivos en `public/images/products/`;
  - ficha de producto: imagen, precio y stock de la variante elegida, con datos `data-*` en cada opción y un script sencillo (`@stack('scripts')` en el layout);
  - imágenes en las tarjetas, y miniaturas en el carrito y el checkout (CSS en `app.css`);
  - 6 tests nuevos en `tests/Feature/ProductImagesTest.php`; README.
- **Errores detectados:**
  - **En el código existente (no de esta tarea):** el carrito no permitía actualizar ni quitar líneas con variante. El formulario enviaba el id de la variante (`31`), pero la línea se guarda en la sesión como `producto_variante` (`1_31`). Se reprodujo con una prueba antes de corregirlo; ahora se usa la clave real y hay un test de regresión.
  - **En la IA:** ninguno en el resultado final. Las primeras capturas con la vista previa de macOS mostraban márgenes blancos; era un defecto de la vista previa, no del SVG (en Chrome se ven bien).
- **Cambios del grupo:** decisiones indicadas arriba.
- **Validación:**
  - `php artisan test`: **36/36** (30 anteriores + 6 nuevos).
  - Capturas reales con Chrome en modo headless del catálogo y de la ficha.
  - En Chrome, al elegir 200 ml la imagen pasa a `sal-002-200-ml.svg` y el precio a 9,90 €; al elegir 100 ml, a `sal-002-100-ml.svg` y 6,50 €.

### 2026-10-10 · Abel · El administrador no compra ni usa el área de cliente (rama `fix/admin-sin-area-cliente`)
- **Tarea:** la cuenta de administrador no debe tener el menú de usuario, ni hacer pedidos, ni cambiar su dirección.
- **Decisión de Abel (no de la IA):** el administrador solo gestiona desde el back-office.
- **Partes asistidas:** la IA lo escribió a petición de Abel:
  - nuevo middleware `EnsureCustomer` (alias `customer`), aplicado al carrito, al checkout, al pago, a los pedidos y a "Mi cuenta". Deja pasar a invitados y clientes; el admin va al back-office con un mensaje;
  - menú: el admin ve "Back-office" y "Salir", sin "Carrito" ni "Mi cuenta";
  - ficha de producto: el admin no ve "Añadir al carrito", sino un aviso;
  - login de la tienda: el admin va siempre al back-office, aunque viniera del checkout (se descarta la página pendiente);
  - 5 tests en `tests/Feature/AdminRestrictionsTest.php`; README (usuario de prueba del back-office).
- **Errores detectados en la IA:** ninguno. Pint reordenó los `use` de `bootstrap/app.php`.
- **Cambios del grupo:** —
- **Validación:** `php artisan test` **41/41** (36 anteriores + 5 nuevos). Se comprueba que el admin no puede añadir al carrito, entrar en el carrito, el checkout o "Mi cuenta", ni cambiar la dirección; que no se le crea ficha de cliente ni pedidos; y que los invitados y clientes siguen igual.

### 2026-10-11 · Abel · Usuario en los eventos y eventos de carrito completos (rama `feature/eventos-usuario`)
- **Tarea:** saber qué usuario provoca cada evento (por ejemplo, quién añade al carrito) para poder analizarlo en la Tarea 2.
- **Instrucciones y decisiones de Abel (no de la IA):**
  - Columna `user_id` en `events`.
  - Adoptar los eventos de invitado al iniciar sesión.
  - Eventos de carrito completos.
  - **Sin** cambios en la exportación JSON/CSV: la Tarea 2 leerá directamente de la base de datos.
  - Lo programa la IA, con un plan aprobado antes.
- **Partes asistidas:** la IA escribió entero este paso:
  - migración `add_user_id_to_events_table` (nullable, clave foránea con `nullOnDelete`) y relación `Event::user()`;
  - `EventLogger::log` rellena `user_id` con `auth()->id()` en **todos** los eventos;
  - nuevo `EventLogger::adoptGuestEvents()`, llamado en el login y el registro con el id de sesión **anterior** a `regenerate()`. No se aplica al administrador;
  - `CartService::add()` devuelve las unidades que entraron de verdad, y nuevo `CartService::line()`;
  - `cart.item_added` guarda `quantity` (real) y `requested_quantity`; si no entra ninguna unidad, no se registra evento y se avisa al cliente;
  - nuevos eventos `cart.item_updated` (`from`/`to`) y `cart.item_removed` (también con cantidad 0);
  - 11 tests en `tests/Feature/EventUserTest.php`; README (tabla de eventos y significado de `user_id` como dato personal).
- **Errores detectados:**
  - **En el código del equipo (no de esta tarea):** al empezar, `main` tenía **13 tests en rojo** (28/41) por los últimos cambios del back-office: el seeder ahora crea pedidos de demostración y el administrador va al dashboard. Eran los tests los que suponían una base de datos sin pedidos; se adaptaron (`PurchaseFlowTest`, `AdminRestrictionsTest`) en un commit aparte.
  - **En la IA:** los tests de "adoptar eventos" fallaban porque en los tests de Laravel cada petición recibe un id de sesión distinto si no se envía la cookie. Se comprobó con un navegador real (curl con cookies) que la aplicación funcionaba, y se corrigieron los tests para que envíen la misma cookie de sesión, como un navegador.
- **Cambios del grupo:** decisiones indicadas arriba.
- **Validación:**
  - `php artisan test` **52/52** (41 + 11 nuevos).
  - Recorrido real: como invitado se ve un producto y se añade al carrito (eventos con `user_id = NULL`); al iniciar sesión como Laura esos dos eventos pasan a `user_id = 2`, y los siguientes (`checkout.started`) ya se guardan con su usuario.

### 2026-10-11 · Abel · Carrito persistente, stock de variante y descuento en el checkout (rama `feature/carrito-persistente`)
- **Tarea:** ajustar el Order-to-Cash a partir del informe: guardar el carrito de los clientes registrados entre sesiones y comprobar el stock de la variante al añadir al carrito.
- **Instrucciones y decisiones de Abel (no de la IA):**
  - Carrito en **base de datos** para usuarios registrados; para invitados se queda en la sesión.
  - Al iniciar sesión, el carrito de invitado se **fusiona** con el guardado.
  - El **código de descuento pasa al checkout** y no se guarda con el carrito.
  - Si una línea supera el stock actual, **solo se avisa**.
  - Lo programa la IA, con un plan aprobado antes.
- **Partes asistidas:** la IA escribió entero este paso:
  - migración `create_carts_tables` (`carts`, `cart_items`: solo productos, tamaños y cantidades, sin precios), modelos `Cart` y `CartItem`, y `User::cart()`;
  - `CartService` reorganizado con dos almacenes (base de datos para clientes, sesión para invitados) detrás de los métodos privados `readLines()`/`writeLines()`, sin cambiar su interfaz pública; nuevo `mergeGuestCart()`, llamado en el login y el registro (no para el admin);
  - `OrderService`: el vaciado del carrito pasa a hacerse **dentro** de la transacción del pedido;
  - `CartController::add`: tamaño obligatorio si el producto tiene tamaños, y debe ser de ese producto; la comprobación de agotado usa el stock de la variante;
  - aviso "Solo quedan N unidades" / "Agotado" en el carrito y en el checkout;
  - código de descuento movido del carrito al checkout (ruta `checkout.code`);
  - 13 tests nuevos en `tests/Feature/PersistentCartTest.php`; README.
- **Errores detectados:**
  - **Tests existentes que había que adaptar a las reglas nuevas, no fallos de la aplicación:** usaban `Product::first()`, que es un producto con tamaños, sin elegir tamaño (ahora es obligatorio); comprobaban el carrito en `session('cart')`, cuando ahora el del cliente está en la base de datos; y uno llenaba el carrito como invitado y entraba con `actingAs`, sin pasar por el login, por lo que no había fusión.
  - **En la IA:** el aviso decía "Solo quedan 1 unidades"; corregido a "Solo queda 1 unidad".
- **Cambios del grupo:** decisiones indicadas arriba.
- **Validación:**
  - `php artisan test`: **65/65** (52 + 13 nuevos).
  - Recorrido HTTP real con cookies:
    - Laura añade Chipotle 200 ml × 2 (guardado en `cart_items`) → cierra sesión → como invitada añade Verde → al iniciar sesión el carrito tiene las dos líneas;
    - al bajar el stock del tamaño aparece el aviso;
    - `BIENVENIDA10` se aplica en el checkout;
    - el pedido sale con el descuento y `cart_items` queda vacío.

### 2026-10-11 · Abel · TPV Virtual de Redsys en entorno de pruebas (rama `feature/tpv-redsys`)
- **Tarea:** integrar el TPV Virtual de Redsys con la librería oficial que Abel descargó (`9999_redsys-lib-php-2.0.1-con-ejemplos`), siguiendo su guía de integración.
- **Instrucciones y decisiones de Abel (no de la IA):**
  - Redsys **sustituye a la tarjeta simulada**; la transferencia simulada se mantiene.
  - `REDSYS_API_KEY` en `.env` y en `.env.example` (clave pública de pruebas de Redsys).
  - Solo se copia la librería; la carpeta descargada (con el PDF, cuya reproducción prohíbe Redsys) va al `.gitignore`.
  - Lo programa la IA, con un plan aprobado antes.
- **Partes asistidas:** la IA leyó la guía (texto extraído del PDF) y los ejemplos, y escribió entero este paso:
  - librería copiada en `backend/packages/redsys-lib` e instalada con Composer (repositorio `path`, versión fijada 2.0.1); `config/services.php`, `.env`/`.env.example` y `phpunit.xml`;
  - migración `add_gateway_fields_to_payments_table` (`gateway_order`, `response_code`, `authorisation_code`, `gateway_response`); constantes y etiquetas en `Payment`;
  - `RedsysGateway::start()` (pago `pending` + formulario firmado) y `RedsysGateway::settle()` (verificación de firma con `Parameters::digest`, comprobación de importe, transacción con bloqueo, idempotencia y evento `payment.simulated` con `gateway=redsys`);
  - `OrderService::markPaid()`, que extrae la secuencia "pagado → factura → pendiente de preparación" que comparten la transferencia y Redsys; `PaymentSimulator` queda solo para la transferencia;
  - `RedsysController` (notificación sin CSRF, y retorno `urlOk`/`urlKo` que procesa la respuesta firmada, necesario en local) y la vista de redirección;
  - página de pago (Redsys / transferencia), y método, código de autorización y tarjeta enmascarada en las páginas de pedido;
  - 10 tests nuevos en `tests/Feature/RedsysPaymentTest.php` y el helper `tests/Concerns/FakesRedsys.php`, que firma respuestas con la propia librería. Se adaptaron los tests que usaban la tarjeta simulada.
- **Errores detectados en la IA:**
  - Al instalar con Composer, la librería quedó con el **nombre de la rama como versión** (`dev-feature/tpv-redsys`), lo que habría roto el lock al borrar la rama. Se fijó la versión 2.0.1.
  - El script reformateó `keywords` en `composer.json`; se restauró.
- **Observaciones:**
  - La guía indica que el resultado definitivo llega por la notificación y que no hay que fiarse de la `urlOk`. Como en local Redsys no puede notificar a `localhost`, se procesa también la respuesta **firmada** del retorno, de forma idempotente.
  - El número de pedido de Redsys debe tener entre 4 y 12 caracteres y empezar por 4 dígitos, por eso no se usa la referencia `PQ-…`.
- **Cambios del grupo:** decisiones indicadas arriba.
- **Validación:**
  - `php artisan test` **75/75** (65 + 10 nuevos): firma falsa, importe distinto y pedido desconocido rechazados; notificación duplicada procesada una sola vez; rechazo `0190`; retorno firmado y sin firmar; acceso de otros usuarios y del admin.
  - **Prueba real contra el TPV de pruebas de Redsys:** el formulario firmado se envió a `sis-t.redsys.es` y Redsys mostró su página de pago con "Importe 10,85 € · Comercio: Piquantum (prototipo) · Terminal 999008881-1 · Pedido 261011000002 · Descripción: Pedido PQ-…".
  - **Pendiente:** que Abel complete un pago en el navegador con una tarjeta de pruebas de Redsys.

### 2026-10-11 · Abel · Correos con la cuenta de DonDominio (rama `feature/correos`)
- **Tarea:** enviar correos en determinados eventos usando la cuenta de correo del hosting de DonDominio.
- **Instrucciones y decisiones de Abel (no de la IA):**
  - Eventos: bienvenida al registrarse, pedido pagado con la factura adjunta, pedido enviado y solicitud de soporte (aviso a la tienda y acuse al cliente).
  - Se envían al correo real del cliente; riesgo aceptado y documentado.
  - Envío inmediato, sin colas.
  - Lo programa la IA, con un plan aprobado antes.
- **Partes asistidas:** la IA escribió entero este paso:
  - 5 *Mailables* en `app/Mail` con plantillas Markdown en español y el aviso de prototipo;
  - cabecera y pie del correo personalizados con el nombre de la tienda (`resources/views/vendor/mail/*/message.blade.php`);
  - servicio `ShopMailer` (único punto de envío, con `DB::afterCommit` y `try/catch` con log);
  - `Invoice::pdf()`, que reutilizan la descarga y el adjunto;
  - envíos conectados en el registro, `OrderService::markPaid()` (Redsys y transferencia), `OrderService::changeStatus()` (estado "enviado") y el formulario de soporte;
  - `SHOP_NOTIFY_EMAIL` en `config/shop.php`; configuración SMTP de DonDominio comentada en `.env.example`;
  - 8 tests en `tests/Feature/EmailTest.php`; README.
- **Errores detectados en la IA:**
  - El bucle que generaba los *Mailables* con Artisan no separaba los argumentos en zsh y creó archivos con nombres incorrectos; se borraron (eran carpetas nuevas) y se escribieron a mano.
  - El primer test del adjunto comparaba el PDF byte a byte, pero dompdf incluye una fecha interna distinta en cada generación; ahora se comprueban el nombre y el tipo del adjunto, y por separado que el contenido es un PDF.
  - En la revisión visual, el correo mostraba el logo y el pie de Laravel (por `APP_NAME=Laravel` en el `.env` local); se personalizaron la cabecera y el pie con el nombre de la tienda.
- **Cambios del grupo:** decisiones indicadas arriba.
- **Validación:**
  - `php artisan test` **83/83** (75 + 8 nuevos). Se comprueba:
    - el destinatario de cada evento;
    - la factura adjunta;
    - que no hay correo de pago si Redsys rechaza el pago **ni si la transacción se revierte**;
    - que un fallo simulado del SMTP no impide el pago;
    - el `replyTo` del aviso de soporte.
  - Revisión visual de los correos renderizados (pedido pagado, bienvenida y soporte) con capturas de Chrome.
  - **Pendiente:** prueba real con el SMTP de DonDominio cuando Abel ponga sus credenciales en su `.env`.

### 2026-10-12 · Abel · Favicon de la tienda (rama `feature/favicon`)
- **Tarea:** sustituir el favicon por defecto por uno acorde a la temática de salsas picantes.
- **Partes asistidas:** la IA lo hizo entero a petición de Abel:
  - diseño de `public/favicon.svg` (guindilla roja con rabito verde sobre círculo crema, legible a 16 px en pestañas claras y oscuras);
  - `favicon.ico` (16 y 32 px) y `apple-touch-icon.png` (180 px), generados a partir del SVG;
  - etiquetas `<link rel="icon">`, `apple-touch-icon` y `theme-color` en `layouts/app.blade.php`.
- **Errores detectados:** el `favicon.ico` original de Laravel estaba **vacío (0 bytes)**, por eso se veía el icono genérico del navegador. En la IA: la primera previsualización mostraba los iconos pequeños deformados por la propia página de previsualización (un `flex` que estiraba las imágenes), no por los archivos, que tenían el tamaño correcto.
- **Cambios del grupo:** —
- **Validación:** previsualización a 128, 32 y 16 px y simulación de pestaña clara y oscura con Chrome; los tres archivos se sirven correctamente (200, tipo MIME correcto) y aparecen enlazados en el HTML; `php artisan test` 83/83.

### 2026-10-12 · Abel · Estados de pedido en el dashboard del back-office
- **Tarea:** en el dashboard, el estado de algunos pedidos no se mostraba correctamente.
- **Causa (código del equipo):**
  - el dashboard comparaba con estados en inglés (`completed`, `pending`, `processing`) que no existen en la tienda; los estados reales están en `Order::STATUS_LABELS`. Cualquier pedido real salía con el texto en bruto y en rojo;
  - el seeder creaba los pedidos de demostración con esos mismos estados inventados;
  - por eso "Facturación total" y "Pedidos pendientes" no contaban los pedidos reales.
- **Partes asistidas:** la IA lo corrigió a petición de Abel (directamente en `main`):
  - la vista usa `statusLabel()` y la clase `badge-{estado}`, como el resto de la tienda;
  - el controlador calcula los ingresos con la nueva constante `Order::PAID_STATUSES` (pagado, en preparación, enviado) y los pendientes con `Order::IN_PROGRESS`;
  - el seeder usa las constantes `Order::SHIPPED` y `Order::PENDING_PREPARATION`;
  - en la base de datos local se corrigieron los pedidos con estados inventados;
  - 3 tests en `tests/Feature/DashboardTest.php`, entre ellos uno que falla si el seeder usa un estado que no existe.
- **Efecto colateral:** con estados reales, Laura tiene pedidos de demostración "en preparación" y la regla de la baja se la bloquea (comportamiento correcto). Se ajustó el test de la baja para dejar antes todos sus pedidos terminados.
- **Validación:** `php artisan test` 86/86; dashboard real como admin: cada pedido muestra su etiqueta y color ("Enviado", "Pendiente de preparación", "Creado"), y la facturación (183,05 €) y los pendientes (5) cuentan los pedidos reales.

### 2026-10-12 · Abel · `.env` de producción
- **Tarea:** preparar el `.env` del entorno productivo (`https://www.piquantum.onl`), con la base de datos del hosting que estaba comentada en el `.env` de desarrollo.
- **Partes asistidas:** la IA generó `backend/.env.production`, ignorado por Git (`backend/.gitignore`), con un script que copia los secretos del `.env` (contraseñas de base de datos y correo, clave de Redsys) **sin mostrarlos**, y genera un `APP_KEY` nuevo solo para producción.
  - Ajustes de producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con https, idioma `es`, logs diarios con nivel `warning`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync` (no hay proceso de colas en el hosting), `SHOP_NOTIFY_EMAIL`.
  - Se quitaron las variables que no se usan (Redis, Memcached, AWS).
- **Errores detectados:** ninguno.
- **Cambios del grupo:** URL de producción y datos del hosting aportados por Abel.
- **Validación:** `php artisan about --env=production` (entorno production, debug OFF, URL `www.piquantum.onl`, mail smtp, queue sync, logs daily) y comprobación de que la URL de notificación de Redsys queda en `https://www.piquantum.onl/pagos/redsys/notificacion`. **No** se ejecutó nada contra la base de datos de producción.

### 2026-10-12 · Abel · Pago cancelado en Redsys: stock bloqueado
- **Tarea (error detectado por Abel en producción):** tras cancelar en la página de Redsys, el pedido se quedaba en "Creado" con el stock bloqueado; además, Abel creyó que se había enviado la factura de un pedido no pagado.
- **Análisis de la IA (sobre la base de datos de producción, solo lectura):** en el pedido `PQ-20261012-5Q4KQQ` hubo **dos intentos de pago**. El primero se canceló en Redsys y quedó "pendiente" (Redsys no devolvió respuesta firmada). El segundo, 22 segundos después, fue **aprobado** (código 0000), y por eso se emitió la factura y se envió el correo. **La factura era correcta.** El problema real era el primero: cancelar no cancelaba el pedido.
- **Decisión de Abel (no de la IA):** si el pago se cancela o se rechaza, el pedido se **cancela al instante**, sin caducidad por tiempo.
- **Partes asistidas:** la IA lo implementó a petición de Abel:
  - `OrderService::cancelUnpaid()`: en una transacción, estado "cancelado" (devuelve el stock y registra el evento) y productos devueltos al carrito;
  - `CartService::restoreOrder()`: escribe en el carrito guardado del cliente aunque no haya sesión, para que funcione desde la notificación de Redsys;
  - `RedsysGateway::settle()`: un rechazo cancela el pedido; un aprobado de un pedido que ya no esperaba pago lo pasa a "Con incidencia";
  - `RedsysGateway::cancelByCustomer()` y `RedsysController`: volver por `urlKo` sin respuesta firmada (el caso de Abel) cancela el pedido y lleva al carrito con un aviso;
  - 4 tests nuevos (cancelar en Redsys, rechazo por notificación, `urlKo` tras aprobar, aprobado tras cancelar) y 4 tests adaptados a la regla nueva; README.
- **Errores detectados en la IA:** la primera versión de `cancelUnpaid()` marcaba como rechazados **todos** los intentos pendientes del pedido. Si Redsys aprobaba después un intento de otra pestaña, ese cobro se habría **ignorado**. Lo detectó el test nuevo; ahora solo se marca el intento cancelado, y un aprobado tardío pasa el pedido a incidencia.
- **Limitación aceptada:** si el cliente abandona Redsys sin volver a la tienda, el pedido sigue en "Creado" hasta que el back-office lo cancele (no hay caducidad).
- **Validación:** `php artisan test` **90/90**.

### 2026-10-15 · Abel · Revisión y arreglo de los eventos tipados de Antonio
- **Tarea:** Antonio implementó el plan de eventos con columnas tipadas y la vista `eventos_bi` para Power BI (commits `ab794d0` a `627a91a`). Abel pidió a la IA revisarlo sin tocar nada y, después, arreglar lo encontrado.
- **Revisión de la IA (solo lectura):** con su cambio, `php artisan test` daba **19 fallos de 93**.
  - `RedsysGateway` seguía llamando a `EventLogger::log()` con el formato antiguo, lo que daba un `TypeError`. **El pago con tarjeta estaba roto**: la notificación devolvía 400 y la cancelación 500.
  - En la vista, `estado_texto` tenía códigos que no existen (`pagado`, `cancelled`, `completed`) y le faltaba `cancelado`.
  - El commit de Abel que cambiaba la contraseña del admin en el seeder rompía los tests de login del admin, y la contraseña nueva quedaba igualmente pública en el repositorio.
  - Problemas menores:
    - se perdió el índice de `session_id`;
    - se borraron comentarios explicativos;
    - hay errores de formato;
    - los `timestamps` repetían `occurred_at`;
    - faltan el test `EventColumnsTest` y la documentación del plan.
- **Decisiones del grupo (Abel):**
  - **revertir** el commit de la contraseña con `git revert` en lugar de reescribir el historial, porque ya estaba en GitHub;
  - quitar de `events` las columnas que se pueden obtener por relación.
- **Punto 3 (vista):** la IA guió a Abel como profesor: el `CASE` falla en silencio y los textos deben salir de una sola fuente. Abel entendió el problema y la solución, y pidió a la IA que la escribiera.
- **Partes asistidas (la IA escribió el código):**
  - revert de la contraseña;
  - `RedsysGateway` con columnas tipadas: origen `redsys`, o `cliente` cuando se cancela en Redsys;
  - `estado_texto` generado en la migración desde `Order::STATUS_LABELS` y el nuevo `Payment::STATUS_LABELS`, que ahora también usa `Payment::statusLabel()`;
  - se quitan de `events` `payment_method`, `gateway`, `response_code`, `discount_code` y los `timestamps`:
    - la vista y la exportación CSV/JSON los leen de `payments` y `orders`, con los mismos nombres de columna;
    - el nuevo `Payment::gateway()` deduce la pasarela;
  - se recupera el índice de `session_id`;
  - Pint en los archivos de eventos.
  - Se mantienen `unit_price`, `amount`, `quantity`, `status` y `previous_status`, porque guardan cómo estaban las cosas en el momento del evento.
- **Efecto a tener en cuenta:** en la vista, `codigo_descuento` aparece ahora en todos los eventos del pedido, no solo en `order.created`, porque sale de `orders`.
- **Validación:**
  - `php artisan test --testsuite=Feature` **93/93**;
  - migraciones y vista comprobadas en SQLite en memoria: estados traducidos ("Cancelado", "Pagado (simulado)", "Aprobado"…) y método, pasarela y código de descuento sacados por relación tras un pago simulado;
  - **falta comprobarlo en la MariaDB local** (Docker estaba parado) y repetir `migrate:fresh --seed` en producción.
- **Pendiente:** `EventColumnsTest` y la documentación del plan (diccionario de datos y conexión con Power BI).

---

## Tarea 2

En la Tarea 2 el anexo de IA es el **apartado 10** del enunciado (puntos 10.a–10.j) y la declaración del punto de partida es el **11.2**. El enunciado pide separar dos usos de la IA (10.a–c):

- **IA como apoyo al desarrollo:** Claude Code (Claude Opus 5.5) en VS Code, como mentor y revisor. Es lo que se anota en esta sección.
- **IA como componente del sistema:** el agente de IA de n8n del escenario 3 (lo lleva Álvaro). Se documenta aparte, no aquí.

Plantilla para cada entrada:

```
### AAAA-MM-DD · Quién · Tema (repo y rama)
- Tarea:
- Tipo de uso: apoyo al desarrollo / componente del sistema
- Partes asistidas: archivos / commits
- Qué escribió el miembro del grupo:
- Qué escribió o propuso la IA:
- Decisiones del grupo:
- Errores detectados en las propuestas de la IA (10.i) y cambios para corregirlos (10.j):
- Validación:
```

### 2026-11-08 · Abel · C1: la API del back-office exige el token siempre (repo `SIE-Tarea2`, rama `fix/backoffice-rev`)
- **Tarea:** corrección C1 de la revisión del back-office de Antonio. La API solo rechazaba un token incorrecto: si no se enviaba ninguno, dejaba pasar, y además aceptaba el token por la URL (`?api_token=`), lo comparaba con `!==`, tenía un token por defecto publicado en el repo y respondía con CORS abierto.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code en modo mentor).
- **Partes asistidas:** `backoffice/src/Controllers/ApiController.php`, método `handle()`.
- **Qué hizo Abel:**
  - vio que el fallo estaba en la condición e intentó invertirla;
  - quitó la lectura del token desde la URL (`$_GET['api_token']`);
  - separó el rechazo en dos comprobaciones, «falta el token» y «token incorrecto», con códigos de error distintos.
- **Qué hizo la IA:**
  - explicó el problema y los conceptos: ley de De Morgan para negar la condición, superglobales `$_GET` y `$_SERVER` (cómo una cabecera pasa a `HTTP_X_INTEGRATION_TOKEN`), por qué el token no debe ir en la URL, comparación en tiempo constante con `hash_equals`, por qué sobra CORS y la diferencia entre 401 (culpa del cliente) y 500 (configuración del servidor);
  - revisó los tres intentos de Abel y señaló los errores sin darle la solución:
    1. cambiar `&&` por `||` sin negar las comparaciones rechazaba también el token correcto;
    2. la comparación con el token esperado estaba invertida y dejaba pasar uno incorrecto;
    3. los dos rechazos respondían 500 en lugar de 401, y faltaba el `?? ''`, sin el cual PHP avisa si no llega la cabecera;
  - **a petición de Abel, escribió la versión final**:
    - `GET /api/v1/health` antes de la comprobación y sin token;
    - sin valor por defecto para `INTEGRATION_API_KEY`: si falta, 500 `ERR_CONFIGURACION`;
    - 401 `ERR_AUTH_TOKEN_AUSENTE` si no llega la cabecera y 401 `ERR_AUTH_TOKEN_INVALIDO` si no coincide;
    - comparación con `hash_equals`;
    - se quitan las cabeceras CORS y el bloque `OPTIONS`.
- **Decisiones del grupo:** pendiente de que Antonio lo revise en la pull request. Los códigos nuevos (`ERR_AUTH_TOKEN_AUSENTE`, `ERR_CONFIGURACION`) hay que añadirlos a `docs/CONTRATOS_API_Y_AGENTE.md` (corrección C16).
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno en esta corrección.
- **Validación:** `curl` contra el servidor local (`php -S`) con un token de prueba en variable de entorno:
  - `health` sin token → 200;
  - sin token → 401 `ERR_AUTH_TOKEN_AUSENTE`;
  - token incorrecto → 401 `ERR_AUTH_TOKEN_INVALIDO`;
  - token correcto en `?api_token=` → 401;
  - token correcto en la cabecera → pasa la comprobación;
  - `OPTIONS` → 401;
  - ninguna respuesta lleva cabeceras `Access-Control-*`.
- **Pendiente:** el caso 500 no se da hasta la corrección C9, porque sin `.env` el back-office sigue leyendo `.env.example`. Efectos apuntados en `CORRECCIONES_BACKOFFICE.md`: el enlace del cuadro de mando a `/api/v1/metrics` ahora da 401 en el navegador y `layout.php` muestra el token como «opcional».

### 2026-11-08 · Abel · C2: inicio de sesión en la web del back-office (repo `SIE-Tarea2`, rama `fix/backoffice-rev`)
- **Tarea:** corrección C2 de la revisión del back-office de Antonio. La web no comprobaba la sesión, así que cualquiera con la URL podía ver los datos, cambiar estados, vaciar la base de datos y aprobar propuestas del agente. Además, los formularios no llevaban token CSRF y el nombre del revisor salía de un campo de texto que se podía cambiar.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:** `backoffice/src/Controllers/WebController.php`, `backoffice/views/layout.php`, `backoffice/views/login.php` (nuevo) y `backoffice/.env.example`. Commits `0e8a355`, `1dfc8fe` y `03225fe`.
- **Qué hizo Abel:** decidió que la IA escribiera directamente toda la corrección y los commits, en lugar de seguir el modo mentor paso a paso.
- **Qué hizo la IA:**
  - comprobó en el código que el problema seguía ahí y propuso dividirlo en cinco pasos;
  - explicó por qué la contraseña se guarda como hash bcrypt;
  - escribió el código completo, a petición de Abel:
    1. **Inicio y cierre de sesión:**
       - `ERP_ADMIN_PASSWORD_HASH` en lugar de la contraseña en claro, con el comando para generarlo en `.env.example`;
       - rutas `/login` y `/logout`, con comprobación de sesión al principio de `handle()`;
       - `password_verify`, `session_regenerate_id(true)` y comparación del email con `hash_equals`;
       - cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` detrás de HTTPS;
       - error 500 si el acceso no está configurado.
    2. **Token CSRF** guardado en la sesión, en todos los formularios POST (también en el login y el cierre de sesión); se comprueba con `hash_equals` y cambia al iniciar sesión.
    3. **`reviewed_by`** sale del operador con sesión iniciada; se quita el campo editable del formulario.
- **Decisiones del grupo:** pendiente de que Antonio lo revise en la pull request. `ERP_ADMIN_PASSWORD` cambia de nombre a `ERP_ADMIN_PASSWORD_HASH`: hay que avisarle y documentarlo en el README y en `deploy/`.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel en esta corrección.
- **Validación:** `curl` contra el servidor local, con una base de datos de prueba aparte que se borró al terminar:
  - **Sin sesión:**
    - `/`, `/?tab=logs` y un POST de vaciado → redirigen a `/login`.
  - **Login:**
    - contraseña incorrecta → 401;
    - contraseña correcta → entra y el identificador de sesión cambia;
    - con sesión, `/login` redirige a `/`;
    - sin `ERP_ADMIN_PASSWORD_HASH` → 500 «acceso no configurado».
  - **CSRF:**
    - login, cambio de stock y cierre de sesión sin token o con un token antiguo → 403;
    - con el token bueno → se procesan.
  - **Revisor:**
    - se aprobó una propuesta mandando `reviewer_name=Suplantador`;
    - en `reviewed_by` quedó el email del operador con sesión iniciada.
- **Pendiente** (apuntado en `CORRECCIONES_BACKOFFICE.md`):
  - el login no limita los intentos fallidos (en el servidor lo mitiga el `basic_auth` de Caddy);
  - los datos de demostración se siguen cargando antes de comprobar la sesión (C3).

### 2026-11-08/09 · Abel · C3 a C16: resto de correcciones del back-office (repo `SIE-Tarea2`, rama `fix/backoffice-rev`)
- **Tarea:** correcciones C3 a C16 de `CORRECCIONES_BACKOFFICE.md`, en el orden de la lista y con al menos un commit por punto.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Qué hizo Abel:**
  - leyó el documento de correcciones y declaró que había entendido los errores;
  - decidió que la IA escribiera todas las correcciones y los commits, en lugar de seguir el modo mentor.
- **Qué hizo la IA (escribió todo el código y la documentación, a petición de Abel):** antes de cada punto comprobó que el problema seguía en el código. Commits:

  | Punto | Commit(s) | Qué cambió |
  |---|---|---|
  | C3 | `b0e6163` | Sin datos de demostración; vaciar la BD solo con `APP_ENV=local` |
  | C4 | `b571a3b` | Embudo con etapas reales (opción a); tiempo de resolución real; `telegram_notified` solo con 2xx de n8n |
  | C5 | `2288adb` | Catálogo real `SAL-001` … `SAL-009`; payloads con productos y clientes de la tienda; versión de esquema |
  | C6 | `c1503b8` | 422 en lugar de valores inventados; `NULL` en opcionales; la revisión ya no aprueba por defecto |
  | C7 | `7df06ac` | SKU desconocido → `ERR_ENTIDAD_NO_ENCONTRADA`; sin stock → `ERR_STOCK_INSUFICIENTE`; todo o nada |
  | C8 | `bccbc2b` | `telegram_chat_id` opcional y numérico; sin `chat_id` no se notifica y queda en el log |
  | C9 | `80b1bba` | Solo `.env` o variables de entorno; sin secretos la app no arranca; `.env.example` con `cambia-esto` |
  | C10 | `126223b` | Pruebas con BD temporal propia, código de salida y casos de cada corrección |
  | C11 | `cf31f5d` | Transiciones de estado de `Order::TRANSITIONS` de la tienda |
  | C12 | `fcf4be4` | Idempotencia por `event_id` (`duplicate`); los eventos solo hacen avanzar el estado; el total no se sobrescribe |
  | C13 | `f3495ec` | `total = líneas − descuento + envío`; nuevo `discount_amount` |
  | C14 | `b0dbc32`, `41d6ba4` | Bandeja de salida (primero se guarda, después se envía); reintentos con el mismo `event_id` (ruta interna y script para cron) |
  | C15 | `9a22908` | `POST /api/v1/inventory/import` (idempotente, todo o nada) |
  | C16 | `0cbdd7a` | Documentación coherente con el código |

- **Decisiones que tomó la IA y debe validar el grupo:**
  - **C4:** embudo con la opción (a), solo etapas que recibe el back-office, sin pedir eventos nuevos a la tienda.
  - **C8:** sin `chat_id`, la incidencia aprobada se da por resuelta sin notificar por Telegram.
  - **C12:** `event_id` obligatorio en los pedidos y opcional en las incidencias.
  - **C14:** máximo 5 intentos de reenvío.
  - **C16:** el `docker-compose` de la guía se sustituye por una referencia a `deploy/`.
- **Decisiones del grupo:** pendiente de que Antonio lo revise en la pull request. El contrato del back-office cambia y afecta a los flujos de n8n de Abel y de Álvaro (campos nuevos obligatorios; ver «Otros problemas» en `CORRECCIONES_BACKOFFICE.md`).
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel. La IA corrigió tres fallos propios al ejecutar las pruebas:
  1. En C8, la actualización del pedido a «incidencia» quedó solo en una rama: se movió.
  2. En C12, las pruebas reutilizaban el mismo `event_id` y salían como duplicados: se dieron identificadores distintos.
  3. En C14, la prueba de agotar reintentos agotaba todos los avisos: se cambió para reintentar solo el suyo.
- **Validación:**
  - `php backoffice/tests/verify_backoffice.php`: **59/59** pruebas correctas. Usan una BD temporal y un n8n simulado para C14; devuelven código 1 si falla alguna, y se comprobó.
  - Además, cada punto se probó con `curl` contra el servidor local y una BD de prueba aparte, siguiendo la «Prueba» del documento:
    - C5: stock de 20 a 18;
    - C7: `SAL-999` rechazado;
    - C9: arranque sin `.env`;
    - C10: el pedido de la BD local sigue ahí después de las pruebas;
    - C14: n8n apagado → pendiente; n8n encendido + reintento → `SINCRONIZADO` con el mismo `event_id`.
- **Pendiente** (en «Otros problemas» de `CORRECCIONES_BACKOFFICE.md`, sin tocar porque no estaban en la lista):
  - el agente puede saltarse la revisión humana con `estado_final: "ejecutado_en_modo_simulado"`;
  - se puede aprobar dos veces la misma propuesta;
  - el simulador sigue disponible en producción;
  - falta un `.dockerignore`;
  - MySQL no funciona con el esquema actual.


### 2026-11-09 · Abel · Despliegue en Google Cloud (repo `SIE-Tarea2`, rama `deploy/gcp`)
- **Tarea:** poner en marcha la infraestructura de la Tarea 2 en una VM de Google Cloud: Caddy (HTTPS), n8n y el back-office de Antonio con Docker Compose, en `https://n8n.piquantum.onl` y `https://erp.piquantum.onl`. El paso a paso está en la bitácora del despliegue de Abel.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:** `deploy/docker-compose.yml`, `deploy/Caddyfile`, `deploy/.env.example` y `deploy/README.md` (commit `b0fbb98`); corrección de la ruta del escenario 1 en el plan, `POST /api/v1/webhooks/orders` (commit `9e6a57c`).
- **Qué hizo Abel:**
  - creó el proyecto, la VM, la clave SSH, la memoria swap y la instalación de Docker;
  - creó los registros A `n8n` y `erp` en DonDominio. Vio que los subdominios llevaban a la tienda, encontró la causa (el comodín `*.piquantum.onl` y unos registros A que no se habían guardado) y borró los registros `odoo` y `panel` del plan inicial;
  - generó los secretos con `openssl rand -hex 32` y el hash de la contraseña del operador, y los puso en el `.env` de la VM, que no está en Git;
  - arrancó los contenedores y creó en seguida la cuenta de propietario de n8n;
  - añadió al compose y a `.env.example` las variables SMTP de n8n (commit `b71a278`), con una cuenta nueva de DonDominio (`n8n@piquantum.onl`);
  - cargó el catálogo en el back-office y comprobó que la carga es idempotente.
- **Qué hizo la IA:**
  - escribió los cuatro archivos de `deploy/` a petición de Abel:
    - Caddy es el único servicio que publica los puertos 80 y 443;
    - la API del back-office no se publica (Caddy responde 403 en `/api/*`) y n8n la usa por la red interna;
    - los secretos de los flujos llegan a n8n como variables de entorno, para que no queden en los workflows exportados;
    - `PHP_CLI_SERVER_WORKERS=4`, para que el back-office no se bloquee cuando avisa a n8n y n8n le vuelve a llamar;
  - corrigió en el plan la ruta del escenario 1.
- **Decisiones del grupo:**
  - el commit de `deploy/` se queda en `deploy/gcp`, y `fix/backoffice-rev` solo lleva las correcciones C1–C16 (la PR para Antonio);
  - pendiente de que Antonio y Álvaro revisen el despliegue cuando entren en n8n.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):**
  - **10.i:** para dar de alta a Antonio y Álvaro en n8n, la IA propuso que Abel les pasara a mano el enlace de invitación.
  - **10.j:** Abel lo corrigió y decidió usar el correo de DonDominio. Creó la cuenta `n8n@piquantum.onl` y configuró él mismo el SMTP de n8n (`smtp.dondominio.com:465` con SSL). Antes comprobó que la VM puede salir por el 465 y el 587 (Google solo bloquea el 25) y que el SPF del dominio autoriza a DonDominio.
- **Validación (Abel):**
  - `docker compose config` sin errores antes del arranque;
  - n8n devuelve 200 (al principio dio 502 mientras preparaba su base de datos);
  - `erp.piquantum.onl` redirige a `/login`, y `/api/v1/health` desde fuera da 403;
  - certificado de Let's Encrypt válido;
  - `docker compose ps`: los tres contenedores arriba, y solo Caddy publica puertos;
  - `wget http://backoffice:8080/api/v1/health` desde el contenedor de n8n devuelve `ok`;
  - login en el back-office, cookie `Secure`/`HttpOnly`/`SameSite=Lax` y sin botón de datos de demostración (correcciones C1–C3 desplegadas);
  - carga del catálogo dos veces con el mismo resultado: `received 13 · unchanged 13`.

### 2026-11-09 · Abel · Fase 1, paso 0: preparar la tienda (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** crear la rama de trabajo de la integración con n8n y comprobar que la base funciona.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code en modo mentor).
- **Qué hizo Abel:**
  - creó la rama `tarea2/integracion-n8n` a partir de `docs/uso-ia-tarea2`, para no perder los commits del registro de IA;
  - decidió no subir a Git sus archivos personales de notas;
  - ejecutó `php artisan test`: **93 tests en verde**, que sirven de referencia para el paso 6.
- **Qué hizo la IA:** a petición de Abel, movió `claude_tarea1.md` y `CONTEXTO_TAREA2.md` de `.gitignore` a `.git/info/exclude`, que es local y no se versiona.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno.
- **Validación:** `git check-ignore -v` confirma que los dos archivos siguen ignorados y `git status` queda limpio.

### 2026-11-10 · Abel · Fase 1, paso 1: configuración de la integración (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** que la tienda sepa a dónde enviar los eventos a n8n, con qué clave firmarlos y qué token exigir cuando n8n la llame, sin ningún valor real en Git.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code en modo mentor).
- **Partes asistidas:** `backend/config/services.php` y `backend/.env.example`.
- **Qué hizo Abel:**
  - escribió los bloques `n8n` (`url`, `secret`, `timeout`) e `integration` (`token`) en `config/services.php`, leyendo el `.env` solo desde `config/`;
  - añadió las tres variables vacías a `.env.example`;
  - generó los secretos de su `.env` local con `openssl`.
- **Qué hizo la IA:**
  - explicó para qué sirve cada variable, por qué hay un secreto distinto para cada dirección y por qué el código usa `config()` y no `env()` (con `config:cache`, `env()` devuelve `null`);
  - revisó la primera versión y señaló los errores para que Abel los corrigiera;
  - a petición de Abel, escribió los comentarios de `.env.example`: cómo se generan los secretos, la URL de test y la de producción, y con qué variable de la VM tiene que coincidir cada uno.
- **Errores en el código de Abel que señaló la IA y corrigió Abel:**
  - `timeout => 12000`, pensando en milisegundos: `Http::timeout()` usa segundos, así que una compra podía quedarse colgada más de 3 horas si n8n no respondía. Abel lo cambió a 5;
  - espacios alrededor del `=` en `.env.example`;
  - el `.env` local no tenía las variables, así que la comprobación daba `null`.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel.
  - **Fallo de la propia IA:** al comprobar el `.env` local, imprimió el valor de `N8N_WEBHOOK_URL` dando por hecho que sería una URL, pero era una clave aleatoria. Ese valor se descarta y no se usa como secreto.
- **Validación:** `php artisan tinker` muestra `timeout = 5` (entero), los dos secretos de 64 caracteres y distintos entre sí, y `.env.example` con todas las variables vacías.

### 2026-11-10 · Abel · Fase 1, paso 2: contrato tienda ↔ n8n adaptado al back-office propio (repo `SIE-Tarea2`, rama `deploy/gcp`)
- **Tarea:** adaptar el borrador del contrato (pensado para Odoo) a la API del back-office del grupo: qué envía la tienda, cómo se firma, qué responde n8n, qué API ofrece la tienda a n8n y cómo se transforma cada evento al formato de Antonio.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:** `integracion/contrato.md` y `integracion/payloads/` (se rehacen `payment.simulated.*` y `order.status_changed.json`, se crean `sync.estado.json` y `sync.stock.json`, y se borran `stock.put.json` y `pedido.estado.patch.json`).
- **Qué decidió Abel:**
  - un único webhook para todos los eventos;
  - `payment.simulated` lleva el pedido completo;
  - un solo endpoint de vuelta, `POST /api/integration/sync`, con token fijo y sin Sanctum;
  - la tienda no ofrece endpoints de consulta;
  - **decisión 6:** de `order.status_changed` solo se envía el origen `backoffice` (lista blanca), entre las dos opciones que planteó la IA con sus pros y contras;
  - pidió a la IA que redactara el contrato con esas decisiones.
- **Qué hizo la IA (redactó el contrato y los payloads, a petición de Abel):**
  - revisó en el código qué orígenes de cambio de estado existen de verdad. Vio que ningún cambio de estado usa el origen `cliente` (solo aparece en el evento `payment.simulated`), así que la regla que proponía la guía se quedaba en «solo `backoffice`»;
  - extendió la decisión 2 a `order.status_changed`: sin endpoints de consulta, n8n solo puede avisar al back-office por `/webhooks/orders`, que exige el pedido completo. **Abel tiene que confirmarlo**;
  - avisó para el paso 3 de que `payment.simulated` se registra antes de `markPaid`, así que el `data` se tiene que montar con el estado final;
  - corrigió `payment.method` (`card` → `redsys`, el valor real de `Payment::METHOD_REDSYS`);
  - revisó el código del back-office y encontró que `/webhooks/orders` descuenta stock aunque el pedido llegue `cancelado`, y no lo repone al pasar a `cancelado`. Lo dejó como decisión abierta 12.C para Antonio, sin tocar su código.
- **Decisiones del grupo:** pendientes. Antonio y Álvaro tienen que dar el visto bueno. Siguen abiertas: el `ticket_ref` y el `telegram_chat_id` (con Álvaro), el stock en las cancelaciones (con Antonio) y el JSON Schema.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel hasta ahora.
  - **Ajuste de la propia IA:** el borrador decía «importes con 2 decimales», pero `json_encode` escribe `6.5`. Se cambió a «redondeado a 2 decimales».
- **Validación:**
  - un script transforma cada payload de pedido según el apartado 9 y le aplica las validaciones del back-office: campos obligatorios, estado del catálogo, SKU existente y total = líneas − descuento + envío (±0,01). Los 4 payloads válidos pasan, y `order.created.incompleto.json` falla con los 4 errores que documenta el apartado 13;
  - todos los JSON son válidos;
  - `grep -i odoo` no encuentra ninguna mención en el contrato ni en los payloads.

### 2026-11-10 · Abel · Fase 1, paso 3: emisor de eventos firmados a n8n (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** enviar a n8n los 4 eventos de negocio, firmados con HMAC, después del commit y sin que un fallo de n8n pueda hacer fallar una compra.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:** `app/Services/Integration/EventPayloadBuilder.php` y `WebhookSender.php` (nuevos), `app/Services/EventLogger.php` (el gancho) y `phpunit.xml`.
- **Qué decidió Abel:**
  - engancharse dentro de `EventLogger::log()` y no con un *observer*;
  - aprobó el diseño del micro-objetivo 3.1: un método que devuelve el `data` o `null` si el evento no se envía;
  - pidió a la IA que escribiera el código.
- **Qué hizo la IA (escribió el código, a petición de Abel):**
  - `EventPayloadBuilder`:
    - aplica las reglas del contrato: solo los 4 tipos; `order.status_changed` solo con el origen `backoffice` (lista blanca); `payment.simulated` solo si es de Redsys;
    - lee el pedido en el momento de construir el `data`, así que lleva el estado final;
    - calcula el `product_code` y pasa los importes a número;
  - `WebhookSender`:
    - monta el sobre, codifica el JSON una sola vez y firma `timestamp.cuerpo`;
    - envía ese mismo texto con `withBody`, con un timeout de `config('services.n8n.timeout')`;
    - nunca lanza excepciones: solo deja aviso en el log;
    - sin URL o sin secreto configurados, no envía nada;
  - en `EventLogger::log()`, `DB::afterCommit` solo para los 4 tipos;
  - en `phpunit.xml`, URL y secreto vacíos, para que los tests nunca llamen al n8n real (el `.env` local ya apunta a n8n);
  - preparó `send()` separado de `dispatch()` para que el paso 4 (bandeja de salida) pueda reenviar el mismo cuerpo con una firma nueva.
- **Decisiones del grupo:** las del contrato (paso 2).
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel.
- **Validación:**
  - `php artisan test`: **93/93** en verde y Pint sin avisos;
  - un test temporal (fuera del repo) con `Http::fake` y `Http::preventStrayRequests` hace una compra completa: pedido con BIENVENIDA10, pago aprobado en Redsys, paso a `enviado` desde el panel de admin y una solicitud de soporte. Resultado:
    - salen **4 peticiones**; los 2 cambios de estado de `markPaid` no se envían;
    - las 4 firmas se pueden recalcular;
    - `payment.simulated` lleva `pendiente_preparacion`;
    - los 3 eventos de pedido, transformados según el apartado 9 del contrato, pasan las validaciones del back-office;
  - pendiente: la prueba contra el n8n real (necesita MariaDB en local y el flujo escuchando).

### 2026-11-10 · Abel · Fase 1, paso 4: bandeja de salida (outbox) y reintentos (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** que ningún evento hacia n8n se pierda si n8n está caído: se guarda y se reintenta con el mismo `event_id` y el mismo cuerpo.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:**
  - migración `webhook_deliveries` y modelo `WebhookDelivery`;
  - `app/Services/Integration/IntegrationOutbox.php` (nuevo);
  - cambios en `EventPayloadBuilder` (`shouldSend()`), `WebhookSender` (`send()` devuelve el código HTTP) y `EventLogger`;
  - `AppServiceProvider`;
  - comando `integracion:reintentar` y su programación en `routes/console.php`.
- **Qué decidió Abel** (entre las opciones que planteó la IA con sus pros y contras):
  - **Decisión 1, opción A:** la fila se guarda en la **misma transacción** que el cambio de negocio y el cuerpo se congela en el primer envío, después del commit, con el estado final del pedido. Se descartó guardar la fila después del commit, porque deja una ventana en la que el evento se puede perder;
  - **Decisión 2, opción B:** el primer envío se hace con `app()->terminating()`, cuando la respuesta ya ha salido hacia el navegador (con PHP-FPM, el cliente no espera a n8n), y el cron hace de red de seguridad;
  - pidió a la IA que escribiera el código.
- **Qué hizo la IA (escribió el código, a petición de Abel):**
  - `record()` en `EventLogger::log()`: inserta la fila `pendiente`, sin cuerpo, solo si el contrato dice que el evento se envía (`shouldSend()` filtra por tipo, origen y método de pago, sin esperar al commit) y solo si la integración está configurada;
  - `DB::afterCommit` apunta la fila para su primer envío. Si la transacción se deshace, la fila desaparece con ella;
  - `attempt()`:
    - reserva atómica de la fila (`UPDATE … WHERE attempts = n`), para que el envío de la petición y el cron no la manden a la vez;
    - en el primer intento, construye y congela el cuerpo;
    - envía con firma nueva y decide según la respuesta: 2xx → `enviado`; 4xx salvo 408 y 429 → `fallido`; 5xx, 408, 429 o sin respuesta → reintentar;
  - esperas del contrato (1, 2, 5, 10 y 30 min) en `next_attempt_at`: envío inicial más 5 reintentos, y después `fallido`;
  - el primer intento espera 1 minuto de margen antes de que el cron pueda recogerlo;
  - el comando `integracion:reintentar` se programa con `everyMinute()->withoutOverlapping()`;
  - el timestamp de la firma pasa de `time()` a `now()`, para que los tests puedan mover el reloj.
- **Decisiones del grupo:** ninguna nueva.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel.
  - **Fallos de la propia IA al probar, corregidos:** en el test temporal, el segundo `Http::fake()` no sustituía al primero (se usa `Http::sequence()`); y la firma salía igual en el reintento porque `time()` no sigue al reloj de los tests.
- **Validación:**
  - `php artisan test`: **93/93** en verde y Pint sin avisos en los archivos nuevos;
  - un test temporal (fuera del repo, `Http::fake` y `preventStrayRequests`) comprueba:
    - n8n responde 200: solo `order.created` y `payment.simulated` entran en la bandeja, los dos `enviado` en 1 intento, y el pago con `pendiente_preparacion`;
    - n8n responde 500: la compra se completa y la fila queda `pendiente` (intento 1, el siguiente en 60 s). El comando no hace nada antes de tiempo; 2 minutos después la envía con **el mismo `event_id` y el mismo cuerpo, y firma nueva**;
    - sin respuesta: `pendiente`, con el error guardado;
    - 422 → `fallido`; 429 → `pendiente`;
    - 503 siempre: `fallido` tras 6 intentos;
    - transacción deshecha: ninguna fila y ninguna petición;
  - la compra completa del paso 3 sigue enviando los 4 eventos con firma válida, y los 3 de pedido pasan las validaciones del back-office.

### 2026-11-10 · Abel · Fase 1, paso 5: endpoint `POST /api/integration/sync` (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** que n8n pueda aplicar en la tienda los cambios de estado y de stock que hace el operario en el back-office, con token, idempotencia y sin bucles.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:**
  - `routes/api.php` y `bootstrap/app.php` (rutas `api` sin Sanctum y alias del middleware);
  - `EnsureIntegrationToken`;
  - `Api/IntegrationSyncController`;
  - `app/Services/Integration/IntegrationSync.php`;
  - migración `integration_inbox` y modelo `IntegrationInboxEntry`.
- **Qué decidió Abel** (entre las opciones que planteó la IA con sus pros y contras):
  - **Decisión 1, opción A:** el `event_id` se apunta en `integration_inbox` **en la misma transacción** que el cambio, y el índice único decide cuál de dos peticiones simultáneas gana. Se descartó «comprobar y después insertar» porque, con dos reintentos a la vez, el correo de «enviado» saldría dos veces;
  - **Decisión 2, opción B:** se apuntan **también los rechazos** (404 y 422) con la respuesta que se dio, como evidencia de la gestión de errores;
  - pidió a la IA que escribiera el código.
- **Qué hizo la IA (escribió el código, a petición de Abel):**
  - middleware con `hash_equals`: si la tienda no tiene token configurado, responde 401 a todo. `throttle:60,1` va antes que el token, para limitar también los intentos con tokens incorrectos;
  - cambio de estado:
    - pasa por `OrderService::changeStatus(…, 'integracion')`, que repone el stock al cancelar y envía el correo de «enviado»;
    - como el origen `integracion` no está en la lista blanca, no vuelve a n8n;
    - responde `unchanged` si el pedido ya tiene ese estado, y 422 `invalid_transition` con `allowed_next` si la transición no está permitida;
  - stock:
    - resuelve el `product_code` a tamaño o a producto, admitiendo minúsculas;
    - un SKU que tiene tamaños, sin indicar el tamaño, da 404;
    - guarda el valor absoluto;
  - **ajuste a la decisión 2 B, propuesto por la IA:** un duplicado **repite la respuesta original** (200 si se aplicó; el mismo 404 o 422 si se rechazó). Si respondiera siempre `200 duplicate`, n8n le diría al back-office que un cambio rechazado está sincronizado.
- **Decisiones del grupo:** a petición de Abel, la IA actualizó el contrato (`SIE-Tarea2`, rama `deploy/gcp`): en el apartado 8, se guardan también los rechazos y los duplicados repiten la respuesta original; en el apartado 7, «envío inicial + 5 reintentos». Pendiente del visto bueno de Antonio y Álvaro.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel.
- **Validación:**
  - `php artisan test`: **93/93** en verde y Pint sin avisos;
  - un test temporal (fuera del repo) recorre 15 casos:
    - 401 sin token, con un token incorrecto y con la tienda sin token configurado;
    - 200 `updated`, con el pedido en `enviado` y **1 correo**;
    - la misma petición otra vez: 200 `duplicate`, sin segundo correo;
    - el mismo cambio con otro `event_id`: `unchanged`;
    - **ninguna entrada nueva** en `webhook_deliveries`, es decir, no hay bucle;
    - transición no permitida: 422 con `allowed_next: ["incidencia"]`, y su repetición, 422 `duplicate`;
    - pedido inexistente: 404;
    - stock de `SAL-002-100ML` (20 → 25) y de `sal-004` (70 → 40);
    - `SAL-002` sin tamaño: 404;
    - faltan campos, stock negativo o falta el `event_id`: 422;
    - todo queda en `integration_inbox` con su resultado y su código.
  - **Sin probar:** la carrera real entre dos peticiones simultáneas (SQLite en memoria no la reproduce). Está cubierta por el índice único y la captura de `UniqueConstraintViolationException`.

### 2026-11-10 · Abel · Fase 1, paso 6: tests de la integración (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** dejar demostrado con tests lo de los pasos 3 a 5: firma, reglas del contrato, bandeja de salida con reintentos y endpoint de sincronización.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:** `tests/Feature/IntegrationOutboxTest.php` (11 tests) y `tests/Feature/IntegrationSyncTest.php` (13 tests).
- **Qué decidió Abel:**
  - aceptó la propuesta de la IA: convertir en tests definitivos las comprobaciones temporales de los pasos 3 a 5;
  - pidió a la IA que los escribiera;
  - pidió subir antes a `SIE-Tarea2` la actualización del contrato (`be8ca83`).
- **Qué hizo la IA (escribió los tests, a petición de Abel):**
  - en todos, `Http::preventStrayRequests()`, para que ningún test llame al n8n real, y `Http::fake` para los casos 200, 500, 422, 429, 503 y `ConnectionException`;
  - **`IntegrationOutboxTest`:**
    - una compra completa envía exactamente los 4 eventos, con firma recalculable y el sobre del contrato;
    - `payment.simulated` lleva el estado final;
    - la transferencia simulada no se envía;
    - con n8n en 500, la compra se completa y la entrega queda `pendiente`;
    - el tiempo agotado deja la entrega `pendiente`;
    - el reintento lleva el mismo `event_id` y el mismo cuerpo, con firma nueva;
    - 4xx → `fallido`, salvo 429;
    - `fallido` tras el envío inicial y 5 reintentos;
    - transacción deshecha → nada;
    - sin configuración → nada;
    - formato del `product_code`;
  - **`IntegrationSyncTest`:**
    - 401 en sus tres variantes;
    - `updated` con correo;
    - duplicado sin segundo correo;
    - `unchanged`;
    - sin bucle hacia n8n;
    - cancelar repone el stock;
    - `invalid_transition` con `allowed_next`, y su repetición con el mismo 422;
    - 404 de pedido y de producto;
    - stock absoluto de un tamaño y de un producto sin tamaños;
    - 422 de validación;
    - todo queda en `integration_inbox`;
  - **prueba de mutación**, para comprobar que los tests detectan errores:
    - firmar sin el timestamp hace fallar 2 tests;
    - dejar pasar el origen `redsys` en la lista blanca hace fallar 2 tests;
    - quitar la comprobación previa de duplicados **no hace fallar ninguno**, porque el índice único de `integration_inbox` dentro de la transacción sigue detectando el duplicado (decisión 1 A del paso 5). Los archivos se restauraron después.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno detectado por Abel.
- **Validación:** `php artisan test`: **117/117** en verde (93 del paso 0 + 24 nuevos).

### 2026-11-10 · Abel · Fase 1, ajuste: el 404 de n8n se reintenta y se pueden recuperar las entregas fallidas (repos `tienda-online` y `SIE-Tarea2`)
- **Tarea:** corregir un fallo de diseño del paso 4. Al preparar la prueba contra el n8n real, la IA vio que n8n responde **404** cuando el flujo está desactivado o no está escuchando («webhook not registered»). El código lo trataba como un error definitivo (`fallido`, sin reintentos), así que desactivar un flujo un rato habría hecho perder esos eventos.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:** `IntegrationOutbox` (404 reintentable y `requeueFailed()`), el comando `integracion:reintentar --fallidos`, `IntegrationOutboxTest` (2 tests) y los apartados 7 del contrato.
- **Qué decidió Abel:** la opción B, entre las tres que planteó la IA: reintentar el 404 y, además, un comando para volver a poner en cola las entregas fallidas. Pidió a la IA que lo escribiera.
- **Qué hizo la IA:**
  - el 404 pasa a reintentarse como un 5xx;
  - `--fallidos` vuelve a poner en cola las entregas `fallido` con el mismo `event_id` y el mismo cuerpo, y todos sus intentos de nuevo;
  - tests nuevos: un 404 deja la entrega `pendiente`; una fallida recuperada se envía con el mismo `event_id` y el mismo cuerpo, y sin `--fallidos` el comando no la toca;
  - contrato: regla de reintentos con el 404, fila nueva en la tabla de respuestas y cómo recuperar las fallidas.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** el fallo es de la propia IA. En el paso 4 propuso «4xx → fallido salvo 408 y 429» sin tener en cuenta cómo responde n8n con el flujo desactivado. Lo detectó ella misma antes de la prueba real y lo corrigió con la opción que eligió Abel.
- **Validación:** `php artisan test`: **118/118** en verde (2 tests nuevos, 1 sustituido) y Pint sin avisos.

### 2026-11-10 · Abel · Fase 1: prueba en local contra el n8n real (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** comprobar la integración de extremo a extremo en local: tienda local → n8n real (URL de test) y `curl` → `POST /api/integration/sync`.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Qué hizo Abel:** creó en n8n el flujo de pruebas con el nodo Webhook (POST, `piquantum/eventos`, *Raw Body*), puso «Listen for test event», arrancó MariaDB y pidió a la IA que se encargara de la prueba.
- **Qué hizo la IA (a petición de Abel):**
  - **envío directo:** mandó el payload de ejemplo del contrato con el `WebhookSender` de la tienda, y n8n respondió 200;
  - **base de datos local desfasada:** la tabla `events` era la versión antigua (7 columnas, sin las columnas tipadas de la Tarea 1), y la migración `create_events_table` figuraba como pendiente. En lugar de borrarla, la **renombró** a `events_backup_tarea1` (50 eventos guardados) y lanzó las 4 migraciones pendientes. Solo en la base de datos local;
  - **pedido real:** `PQ-20261110-SK7N7A` (2 × SAL-002 100 ml + SAL-003, BIENVENIDA10, 21,96 €) por `CartService` y `OrderService::createFromCart`. La bandeja lo envió al terminar, y n8n respondió **200** al primer intento (`enviado`);
  - **n8n sin escuchar:** un cambio a `incidencia` con origen `backoffice` recibió **404** y quedó `pendiente`, con el siguiente intento al cabo de 1 minuto. Es el caso que corrige el ajuste del 404;
  - **`curl` contra un servidor local temporal** (puerto 8001, token leído del `.env` sin mostrarlo):
    - sin token o con un token incorrecto: 401;
    - `incidencia → pendiente_preparacion`: 200 `updated`; repetida: 200 `duplicate`;
    - `→ creado`: 422 `invalid_transition` con `allowed_next`;
    - stock al mismo valor: `unchanged`;
    - `SAL-999`: 404;
    - ningún cambio de `sync` generó entregas hacia n8n (sin bucle).
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** ninguno.
  - **reintento:** con «Listen» activado de nuevo, `php artisan integracion:reintentar` envió la entrega pendiente con **el mismo `event_id`** (`cb6e7e5a-…`). n8n respondió 200, y la entrega quedó `enviado` en el intento 2.
- **Pendiente:** **antes del paso 7, comprobar si la base de datos de producción tiene el mismo desfase de `events`.**

### 2026-11-10 · Abel · Fase 1, paso 7: cron por URL para DonDominio (repo `tienda-online`, rama `tarea2/integracion-n8n`)
- **Tarea:** el panel de tareas programadas de DonDominio **solo permite llamar a una URL** (campo «Query», con usuario y contraseña HTTP opcionales), no ejecutar `php artisan schedule:run`. Hacía falta una URL que lance los reintentos de la bandeja de salida.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Partes asistidas:**
  - `GET /api/integration/cron` (`routes/api.php`);
  - `EnsureCronCredentials` y `Api/IntegrationCronController`;
  - `config/services.php`, `.env.example` y `phpunit.xml` (`INTEGRATION_CRON_TOKEN`);
  - `tests/Feature/IntegrationCronTest.php` (3 tests).
- **Qué decidió Abel:**
  - le enseñó a la IA el formulario del panel, y así se vio que el cron solo llama a URLs;
  - eligió la **opción A**: usuario y contraseña HTTP (Basic Auth) con un secreto propio. Se descartaron el secreto en la URL (quedaría en los logs de acceso, el mismo problema de la corrección C1 del back-office) y reutilizar `INTEGRATION_API_TOKEN` (un secreto filtrado desde el panel abriría también `sync`);
  - pidió a la IA que lo escribiera.
- **Qué hizo la IA (escribió el código, a petición de Abel):**
  - usuario `cron` y contraseña comparada con `hash_equals`; sin contraseña configurada, 401 a todo;
  - `throttle:10,1`;
  - un bloqueo de caché (`Cache::lock`), equivalente a `withoutOverlapping()`: si hay otra ejecución en marcha, responde `busy`;
  - devuelve en JSON el resumen de `retryDue()`;
  - comprobó que el `.htaccess` de `public/` pasa la cabecera `Authorization` a PHP.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):**
  - la hoja de ruta (y la IA) daban por hecho un cron con `php artisan schedule:run`. El panel de DonDominio no lo permite: lo vio Abel al abrir el formulario;
  - antes, la IA había sugerido subir `vendor/composer/` por si salía un error de clase no encontrada. Abel ejecutó `composer dump-autoload --no-dev` en local, que falló en `package:discover` y dejó el autoload local sin los paquetes de desarrollo. La IA lo restauró con `composer dump-autoload` y comprobó que no hacía falta: el autoload está optimizado pero no es *authoritative*, así que las clases nuevas cargan por PSR-4.
- **Validación:**
  - `php artisan test`: **121/121** en verde. Los tests nuevos cubren el 401 sin credenciales, con una contraseña incorrecta, con otro usuario o sin contraseña configurada; el reintento de una entrega que ya toca (mismo `event_id`); y `busy` con el bloqueo tomado.

### 2026-11-11 · Abel · Fase 1, paso 7: publicación en DonDominio y prueba de extremo a extremo (repos `tienda-online` y `SIE-Tarea2`, VM)
- **Tarea:** dejar la integración funcionando en producción: secretos en la VM y en la tienda, cron, y un pedido real que llegue a n8n.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code).
- **Qué hizo Abel:**
  - subió por FTPS el código de la integración;
  - generó los secretos en la VM (que estaban vacíos) y los copió al `.env` de la tienda;
  - recargó n8n con `docker compose up -d` y comprobó que tenía las 3 variables cargadas;
  - hizo los pedidos de prueba con la tarjeta de Redsys y publicó el flujo de n8n.
- **Qué hizo la IA:**
  - comprobó en modo lectura el estado de producción (`migrate:status`, esquema de `events`) y, **con la confirmación de Abel**, lanzó las 2 migraciones de la integración sin `--seed`;
  - dio los pasos para generar los secretos en la VM con `sed` y `$(openssl rand -hex 32)`, sin mostrarlos ni dejarlos en el historial, y para comprobar que n8n los tenía sin imprimir sus valores;
  - preparó comprobaciones con `curl` y `read -s`, para que los tokens no queden en el historial ni pasen por la IA;
  - diagnosticó los dos fallos leyendo la bandeja de salida de producción en modo lectura:
    1. el primer pedido no generó envíos porque faltaba `N8N_WEBHOOK_URL` en el `.env` de la tienda;
    2. el segundo recibió 404 «webhook not registered» porque el flujo de n8n no estaba **publicado**. En la versión actual de n8n, la URL de producción solo usa la versión publicada.
- **Errores detectados en las propuestas de la IA (10.i / 10.j):** la IA habló de «activar» el flujo (el interruptor *Active* de versiones anteriores de n8n); en esta versión se hace con **Publish**. Abel lo preguntó y se corrigió la instrucción.
- **Validación:**
  - `sync` con el token → 404 `not_found` para `SAL-999`, sin cambios en la tienda;
  - cron con Basic Auth → 200 `ok`;
  - el pedido `PQ-20261111-UMQAX6` → `order.created` (`creado`) y `payment.simulated` (`pendiente_preparacion`) quedaron `pendiente` mientras n8n respondía 404, y se enviaron con su mismo `event_id` en el segundo intento (HTTP 200) al llamar al cron después de publicar el flujo. **La prueba de extremo a extremo tienda → n8n en producción queda superada,** y el patrón *outbox* se ha visto funcionar en un caso real.

### 2026-11-11 · Abel · Fase 3, escenario 1 (en curso): flujo de n8n tienda → back-office (n8n, flujo «Piquantum · Eventos tienda»)
- **Tarea:** construir en n8n el flujo que recibe los eventos de la tienda, verifica la firma, valida, transforma y llama a `POST /api/v1/webhooks/orders` del back-office.
- **Tipo de uso:** IA como apoyo al desarrollo (Claude Code). El flujo lo monta Abel nodo a nodo en n8n.
- **Qué decidió Abel:**
  - opción A: montar el flujo él mismo y que la IA le dé el código de los nodos Code y la configuración de cada nodo (descartado importar un JSON hecho por la IA);
  - opción c para `support.requested`: responder 503 hasta que Álvaro termine el escenario 3, para que la tienda lo reintente y después se recupere con `--fallidos`.
- **Qué hizo Abel:**
  - montó el Webhook (respuesta con *Respond to Webhook*), el nodo «Verificamos firma», el If con la respuesta 401, el Switch por tipo (salidas de pedido, 503 y 400) y el nodo «Validar» con su If y su respuesta 422;
  - igualó el secreto local con el de la VM (huella `039e4add` en la tienda, la VM y n8n).
- **Qué hizo la IA:**
  - escribió el código de los nodos Code:
    - «Verificar firma»: `getBinaryDataBuffer` para el cuerpo exacto, HMAC de `timestamp.cuerpo`, `timingSafeEqual` y ventana de 5 minutos;
    - «Validar»: reglas del apartado 6 del contrato, con los mismos textos de error que el apartado 13;
    - «Transformar»: tabla del apartado 9 del contrato;
  - dio las expresiones del Switch y de las respuestas;
  - revisó en el código del back-office qué responde `/webhooks/orders` (200 o 422 con `error_code`) y propuso tratar `ERR_DB_TRANSACTION` como temporal (503).
- **Errores detectados durante la construcción y cómo se corrigieron:**
  - lanzar «Listen for test event» desde dentro del nodo Webhook solo ejecuta ese nodo (n8n respondía 200 vacío). Hay que usar «Execute workflow» desde el lienzo. La IA no lo había avisado;
  - el *Response Body* pegado como `{{ {…} }}` quedaba en modo *Fixed* y daba «Invalid JSON» (HTTP 500). Se cambió a modo *Expression* con `JSON.stringify(...)` en todas las respuestas;
  - la vista previa de `$env.*` muestra «not accessible via UI»: es la protección de secretos de n8n, no un error.
- **Validación hasta ahora:**
  - pedido local firmado → firma válida; con el secreto alterado → 401 «firma incorrecta»;
  - `order.created.incompleto.json` → **422 con los 4 errores** del apartado 13 del contrato, sin llegar al back-office.
- **Pendiente:** pasos 5.3 y 5.4 (Switch de la respuesta del back-office y respuestas 200, 422 y 503), prueba con el payload válido (`created`, y después `duplicate`), publicar el flujo y exportarlo a `SIE-Tarea2`.
