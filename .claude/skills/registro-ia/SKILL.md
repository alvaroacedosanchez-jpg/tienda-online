---
name: registro-ia
description: Registra en docs/ai-usage-log.md el uso de IA generativa en el proyecto (tarea, ficheros asistidos, errores de la IA, cambios del grupo, validación). Úsala tras cualquier cambio relevante hecho con ayuda de Claude y cuando haya que preparar el anexo de IA de la memoria.
---

# Registro de uso de IA (anexo obligatorio)

El enunciado exige un anexo con: (a) herramientas, (b) tareas, (c) fragmentos asistidos,
(d) errores detectados en la IA, (e) cambios del grupo, (f) cómo se validó el resultado.
Este log se mantiene **durante** el desarrollo para no reconstruirlo de memoria al final.

## Cuándo registrar

- Se ha creado o modificado código, SQL, tests, docs o datos de prueba con ayuda de Claude.
- Se ha diseñado una decisión de arquitectura con ayuda de Claude.
- Claude cometió un error (código que no funcionaba, dato inventado, suposición falsa, API inexistente).
No hace falta registrar consultas triviales (sintaxis suelta, dudas conceptuales sin código).

## Procedimiento

1. Añade una entrada **al final** de `docs/ai-usage-log.md` con la plantilla de abajo.
2. Rellena lo que sabes (fecha, herramienta/modelo, tarea, ficheros, qué generó la IA).
3. **Pregunta al usuario** lo que Claude no puede saber: qué cambió el grupo, qué errores detectó,
   cómo lo verificó (ejecutó, probó en navegador, test, revisión de otro miembro). No lo inventes;
   déjalo como `PENDIENTE` si no lo sabe aún.
4. Registra siempre los errores propios de Claude de forma honesta y concreta.
5. Indica quién del grupo lo revisó y entendió (autoría y responsabilidad).

## Plantilla de entrada

```markdown
### YYYY-MM-DD · <título corto>
- **Herramienta / modelo:** Claude Code (Sonnet 5.5)
- **Tarea:** <diseño | código | depuración | datos de prueba | redacción>
- **Ficheros / partes asistidas:** `ruta/fichero` (líneas o función)
- **Qué generó la IA:** <resumen>
- **Errores detectados en la respuesta:** <ninguno | descripción concreta>
- **Cambios realizados por el grupo:** <qué se modificó, rechazó o reescribió y por qué>
- **Validación:** <cómo se comprobó que funciona y que se entiende>
- **Revisado y entendido por:** <nombre/s>
```

## Al preparar la memoria

Resume el log en las seis secciones a-f del anexo, agrupando por tipo de tarea y dando
ejemplos concretos de errores y correcciones. Si el log está vacío o es vago, avisa al usuario:
una declaración incompleta es un riesgo (la omisión se considera irregularidad).
