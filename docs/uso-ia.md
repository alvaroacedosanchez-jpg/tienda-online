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
- **Cambios del grupo:** _(completar con la decisión del equipo)_
- **Validación:** contraste con el enunciado. Las cuentas de cliente no son obligatorias (4.b admite "clientes o usuarios de prueba").
