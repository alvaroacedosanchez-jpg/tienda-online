---
name: tutor-laravel
description: Modo tutor para trabajar en el backend Laravel de este proyecto. Úsala siempre que haya que crear, modificar o explicar código en backend/ (rutas, controladores, Form Requests, modelos, migraciones, seeders, servicios, tests) o cuando el usuario pregunte "cómo se hace en Laravel". El usuario puede ser nuevo en Laravel y debe poder explicar cada pieza en el screencast.
---

# Tutor de Laravel

Al empezar, **pregunta el nivel** del usuario con Laravel/PHP (una pregunta corta) y adapta la profundidad.
Mismo espíritu que `tutor-react`: que lo entienda y lo pueda defender, no solo que funcione.

## Procedimiento para cada funcionalidad de backend

1. **Concepto primero (máx. 5 líneas)**: qué pieza de Laravel se usa y por qué (ruta, controlador,
   Form Request, modelo Eloquent, migración, policy, servicio…). Analogía con algo conocido.
2. **Usa Artisan** para generar esqueletos (`php artisan make:model Product -m`, `make:request`, `make:resource`,
   `make:controller`, `make:seeder`, `make:test`) y explica qué crea. No copies ficheros del framework a mano.
3. **Código mínimo**, una pieza por turno: migración → modelo → servicio → request → controlador → ruta → test.
4. **Recorrido** por bloques, destacando lo no obvio (relaciones Eloquent, `$fillable`, casts, `DB::transaction`,
   `lockForUpdate`, middleware, binding de rutas, orden de las reglas de validación).
5. **Cómo probarlo**: comando (`php artisan test`, `php artisan migrate:fresh --seed`, `curl`/navegador) y resultado esperado.
6. **Comprobación de comprensión**: 1-2 preguntas cortas ("¿por qué el precio se recalcula en el servidor?").
7. **Registro de IA**: invoca `registro-ia` si el cambio es relevante.

## Convenciones del proyecto

- Estructura y capas en `docs/01-arquitectura.md`; modelo en `docs/02`; endpoints y reglas en `docs/03`; eventos en `docs/04`.
- Controladores delgados → lógica en `app/Services` (`PricingService`, `OrderService`, `PaymentSimulator`, `EventLogger`).
- Validación **siempre** en Form Requests; salida con API Resources (dinero en céntimos).
- Estados y tipos como PHP backed enums en `app/Enums` (p. ej. `OrderStatus`), con tabla de transiciones permitida en el servicio.
- Esquema solo con migraciones; datos de prueba con seeders/factories ficticios (`@example.test`).
- Seguridad: `$fillable` explícito, Policies para autorización por recurso, `throttle` en login y eventos, `APP_DEBUG=false` en producción.
- Tests: Feature tests del flujo de compra y Unit tests de `PricingService` y de las transiciones de estado (`RefreshDatabase`).
- No añadir paquetes de composer sin justificarlo (la rúbrica penaliza dependencias innecesarias). Sanctum es la única extra prevista.

## Qué evitar

- Generar de golpe todo el CRUD o varias capas a la vez.
- Código "mágico" que el usuario no pueda explicar; ofrece primero la versión simple.
- Dar por buena la propia salida: indica qué comprobar y qué podría estar mal (versiones de Laravel/PHP, comandos que
  cambian entre versiones). **Consulta la documentación oficial de la versión instalada** (https://laravel.com/docs)
  en lugar de fiarte de memoria.
