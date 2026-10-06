# Registro de uso de IA generativa

Base del anexo de IA de la memoria (herramientas, tareas, fragmentos, errores, cambios, validación).
Se rellena durante el desarrollo con la skill `registro-ia`. Plantilla de entrada al final de
`.claude/skills/registro-ia/SKILL.md`.

## Herramientas utilizadas

- Claude Code (modelo: Sonnet 5.5) — apoyo en diseño, código y documentación.

## Punto de partida del proyecto (para la declaración de la memoria)

- Desarrollo propio desde cero con apoyo de IA. Plantillas/repositorios de terceros: **ninguno** *(actualizar si cambia)*.

## Entradas

### 2026-10-04 · Generación inicial de specs y skills
- **Herramienta / modelo:** Claude Code (Sonnet 5.5)
- **Tarea:** diseño / documentación
- **Ficheros / partes asistidas:** `CLAUDE.md`, `docs/00..05`, `.claude/skills/*`
- **Qué generó la IA:** borrador de requisitos, arquitectura, modelo de datos, API, eventos y plan.
- **Errores detectados en la respuesta:** PENDIENTE (la primera propuesta de stack era Node/Express; era incompatible con el hosting básico de DonDominio y se cambió a PHP)
- **Cambios realizados por el grupo:** PENDIENTE
- **Validación:** PENDIENTE (revisión del grupo y confirmación de características del hosting en el panel)
- **Revisado y entendido por:** PENDIENTE

### 2026-10-06 · Adaptación de las specs a Laravel
- **Herramienta / modelo:** Claude Code (Sonnet 5.5)
- **Tarea:** diseño / documentación
- **Ficheros / partes asistidas:** `CLAUDE.md`, `docs/01`, `02`, `03`, `04`, `05`, skills `desplegar-dondominio`, `checklist-rubrica`, nueva `tutor-laravel`
- **Qué generó la IA:** reescritura de la arquitectura y del despliegue para Laravel (migraciones en vez de `schema.sql`, Form Requests, Sanctum, despliegue sin SSH).
- **Errores detectados en la respuesta:** PENDIENTE (la propuesta inicial descartó Laravel como "demasiada magia" sin conocer el requisito del profesor; el despliegue de Laravel en DonDominio es una hipótesis hasta verificar PHP, SSH y raíz web en el panel)
- **Cambios realizados por el grupo:** PENDIENTE
- **Validación:** PENDIENTE (probar un despliegue mínimo en la Fase 1)
- **Revisado y entendido por:** PENDIENTE
