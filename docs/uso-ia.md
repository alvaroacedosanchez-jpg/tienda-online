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
  2. `tests/Feature/ExampleTest.php` falla porque `RefreshDatabase` está comentado. _(Pendiente)_
  3. La tabla de transiciones de estado (`Order::TRANSITIONS`) no permite `creado → pagado_simulado`, pero `PaymentSimulator` hace esa transición, y `OrderService::changeStatus` no valida transiciones. _(Pendiente)_
  4. Los totales del pedido se calculan con precios leídos antes del bloqueo de stock, y el carrito se vacía fuera de la transacción. _(Pendiente)_
  5. Privacidad: cualquiera con la referencia `PQ-...` puede ver los datos del cliente y pagar el pedido. _(Pendiente)_
  6. `Customer::create` crea un cliente nuevo en cada pedido, aunque el email se repita. _(Pendiente)_
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
