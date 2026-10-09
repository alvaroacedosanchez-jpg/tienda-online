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
