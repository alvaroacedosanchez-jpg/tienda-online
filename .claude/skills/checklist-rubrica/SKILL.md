---
name: checklist-rubrica
description: Audita el estado del proyecto contra los requisitos, la rúbrica y las penalizaciones del enunciado (docs/00-requisitos.md) y devuelve qué está hecho, parcial o pendiente. Úsala antes de cada hito, antes del despliegue y antes de entregar.
---

# Auditoría contra la rúbrica

Lee `docs/00-requisitos.md` y revisa el repositorio real (no te fíes de lo marcado en el checklist).

## Qué comprobar

1. **Flujo**: existen rutas/páginas y endpoints para catálogo, ficha, carrito, checkout, pago simulado,
   pedido con `public_id` único, estados, back-office. Ejecuta o lee el código para confirmarlo.
2. **Datos**: migraciones y seeders existen y `php artisan migrate:fresh --seed` funciona desde cero; ≥ 8 productos, usuarios de prueba, pedidos de ejemplo.
3. **Arquitectura**: controladores delgados (sin consultas ni reglas de negocio), validación en Form Requests, lógica en `app/Services`, sin lógica de negocio en React; precios recalculados en servidor.
4. **Eventos**: los 6 eventos requeridos se emiten desde el sitio correcto y se almacenan; hay export.
5. **Seguridad/privacidad**: busca credenciales, tokens, contraseñas reales o emails reales en todo el repo
   y en el historial git (`git log -p`), todos los `.env` ignorados (raíz y `backend/`), `APP_KEY` no
   commiteada, `$fillable` definido, validación en servidor, no se guarda número de tarjeta completo ni CVV.
   Comprobar también que `php artisan test` pasa.
6. **Transparencia**: banner de prototipo académico visible en todas las páginas y mención en README.
7. **Documentación**: README con instalación, ejecución, usuarios de prueba y limitaciones; URL pública;
   `docs/ai-usage-log.md` con entradas reales.
8. **Git**: `git shortlog -sn` muestra contribuciones de los **3** miembros de forma razonable.
9. **Despliegue**: la URL pública responde, el flujo completo funciona allí, HTTPS activo.
10. **Entrega**: ZIP, YouTube (≤ 7 min), memoria 5-6 págs con las 6 secciones, declaración del punto de partida.

## Formato de salida

Tabla por criterio de la rúbrica: `Criterio | Pts | Estado (✅ / 🟡 / ❌) | Evidencia (fichero:línea) | Qué falta`.
Después:

- **Topes en riesgo** (sección 11 del enunciado): indicar cuáles se activarían hoy.
- **Top 5 acciones** ordenadas por puntos recuperables por esfuerzo.
- Marca como `[x]` en `docs/00-requisitos.md` solo lo verificado.

No modifiques código en esta skill: solo informa. Sé conciso y concreto.
