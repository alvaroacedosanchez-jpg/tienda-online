# CLAUDE.md — Canal digital de venta instrumentado (SIE, Tarea 1)

Práctica universitaria (Grado en Ingeniería Informática, online) para la asignatura
*Soluciones Informáticas para la Empresa*. Grupo de 3 personas. Prototipo académico de
tienda online "Piquantum" que genera eventos de negocio.

## Reglas de trabajo (leer primero)

El enunciado penaliza (hasta suspender) entregas generadas con IA "sin comprensión, validación
ni aportación verificable del grupo". Además los 3 miembros defienden el trabajo en un
screencast. Por tanto, Claude actúa como **compañero que enseña**, no como generador de
código en bloque:

1. **El usuario no sabe React** y aprende con esta práctica (y puede ser nuevo en Laravel).
   Antes de escribir código nuevo, explica brevemente el concepto (skills `tutor-react` y
   `tutor-laravel`). Cambios pequeños y revisables: una funcionalidad por turno salvo que se
   pida otra cosa.
2. Tras cada cambio relevante, indica qué ficheros tocar/revisar y haz 1-2 preguntas de
   comprobación. No des por bueno código que el usuario no pueda explicar.
3. **Registra el uso de IA** en `docs/ai-usage-log.md` (skill `registro-ia`). Es obligatorio
   para el anexo de la memoria. Anota también los errores que cometa Claude.
4. Nunca incluyas credenciales reales, claves, datos personales reales ni pagos reales.
   Solo datos ficticios y tarjetas de prueba. Ningún `.env` (raíz ni `backend/`) se versiona; el `APP_KEY` y las credenciales del hosting solo viven en el servidor.
5. Si algo del enunciado es ambiguo o hay que elegir entre alternativas, plantea las
   opciones con pros/contras: la memoria exige justificar decisiones y alternativas.
6. Los commits deben reflejar a los 3 miembros (ramas por funcionalidad, commits pequeños
   con mensajes claros). No hagas commits ni push sin que el usuario lo pida.

## Stack (decisión tomada)

| Capa | Tecnología | Motivo |
|---|---|---|
| Frontend | React (versión actual que instala Vite) + Vite + React Router + CSS (sin librerías UI pesadas) | Pedido por el profesor; build estático desplegable en cualquier hosting |
| Backend | **Laravel** (API JSON, Eloquent, Form Requests, Sanctum para sesión) | **Requisito del profesor**. Corre en PHP, que sí soporta el hosting básico de DonDominio (compartido, sin Node.js) |
| BBDD | **MySQL** (el del hosting). Local en Docker con la misma versión; producción la del hosting | Persistencia obligatoria |
| Despliegue | DonDominio (dominio + hosting facilitado por el profesorado) | Requisito 4.h: dominio público real |

Alternativas descartadas (para la memoria): Node/Express (no cabe en el hosting), Next.js
(requiere Node en servidor), PHP sin framework (valorado primero por simplicidad, descartado
porque el profesor pide Laravel), Symfony (más pesado), SPA con backend externo gratuito (más
piezas y riesgo, hosting de la asignatura sin uso).

## Estructura del repositorio

```
frontend/            React + Vite (src/pages, components, context, api, hooks)
backend/             Laravel (app/Http/Controllers|Requests|Resources, app/Models, app/Services,
                     database/migrations|seeders|factories, routes/api.php, tests/)
docs/                specs (ver índice) + ai-usage-log.md
docker-compose.yml   MySQL + phpMyAdmin en local
.env.example         variables del Docker de la raíz (sin secretos reales)
                     (Laravel tiene su propio backend/.env, que tampoco se versiona)
README.md            instalación, ejecución, usuarios de prueba, limitaciones
.claude/skills/      skills de Claude Code
```

## Specs (fuente de verdad, leer según la tarea)

- [docs/00-requisitos.md](docs/00-requisitos.md) — requisitos del enunciado, rúbrica y penalizaciones
- [docs/01-arquitectura.md](docs/01-arquitectura.md) — capas, estructura, desarrollo local y despliegue en DonDominio
- [docs/02-modelo-datos.md](docs/02-modelo-datos.md) — entidades, DDL, datos de prueba
- [docs/03-api-y-reglas-negocio.md](docs/03-api-y-reglas-negocio.md) — endpoints, impuestos, envío, descuentos, estados del pedido
- [docs/04-eventos.md](docs/04-eventos.md) — catálogo de eventos, formato, almacenamiento y consumo en Tarea 2
- [docs/05-plan-por-fases.md](docs/05-plan-por-fases.md) — plan de trabajo, reparto, git y entrega

## Convenciones de código

- Identificadores y nombres de ficheros en inglés; textos de interfaz, comentarios y docs en español.
- Dinero siempre en **céntimos (INT)**; formatear solo en el frontend.
- Toda lógica de negocio (precios, impuestos, estados) en el **backend**; el frontend nunca es
  fuente de verdad del precio. Se recalcula en el servidor al crear el pedido.
- Acceso a datos solo con Eloquent o el query builder (consultas parametrizadas). Nada de concatenar
  entradas en SQL; `DB::raw`/`whereRaw` solo con bindings.
- Laravel: controladores delgados; validación en **Form Requests**; reglas de negocio en
  `app/Services`; formato de salida con **API Resources**; permisos con Policies/middleware.
  El esquema de la BBDD se define **solo con migraciones** (nunca se edita a mano en phpMyAdmin).
- React: componentes de función + hooks; estado global solo para carrito y sesión (Context).
- Comentarios solo donde expliquen el *porqué*. Sin dependencias innecesarias (rúbrica).
- Banner visible en todas las páginas: "Prototipo académico — sin actividad comercial real".

## Comandos (rellenar al crear el proyecto)

Requisitos locales: Docker, PHP 8.x, Composer, Node.js.

```bash
docker compose up -d                                 # BBDD local
cd backend && composer install && php artisan migrate --seed
cd backend && php artisan serve                      # API en :8000
cd backend && php artisan test                       # tests
cd frontend && npm install && npm run dev            # Vite (proxy /api y /sanctum -> :8000)
cd frontend && npm run build                         # build del SPA (ver docs/01 para el destino)
```
