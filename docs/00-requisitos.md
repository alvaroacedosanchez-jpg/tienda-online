# 00 · Requisitos del enunciado (Tarea 1)

Resumen operativo de `enunciado_tarea_1_SIE_onl.pdf`. Marcar `[x]` según se complete.
La skill `checklist-rubrica` audita el repo contra este fichero.

## Requisitos funcionales (sección 3)

- [ ] RF-a Página principal / vista inicial
- [ ] RF-b Catálogo organizado por categorías
- [ ] RF-c Ficha de producto con info suficiente para decidir la compra
- [ ] RF-d Mínimo **8 productos** con datos realistas y coherentes
- [ ] RF-e Carrito
- [ ] RF-f Checkout / formalización del pedido
- [ ] RF-g Impuestos, gastos de envío, descuentos o condiciones comerciales simuladas
- [ ] RF-h Pago simulado / entorno de pruebas
- [ ] RF-i Pedido con identificador único
- [ ] RF-j Estados de pedido (creado, pagado simulado, pendiente de preparación, enviado, cancelado, con incidencia)
- [ ] RF-k Back-office básico para revisar pedidos

## Requisitos técnicos (sección 4)

- [ ] RT-a Persistencia real (no maqueta estática)
- [ ] RT-b Modelo: productos, usuarios de prueba, pedidos, líneas, pagos simulados, eventos
- [ ] RT-c Separación interfaz / lógica de negocio / persistencia
- [ ] RT-d Datos de prueba realistas y coherentes
- [ ] RT-e Validación básica de formularios y entradas
- [ ] RT-f Sin credenciales reales en código, docs o entrega
- [ ] RT-g Instrucciones de ejecución claras
- [ ] RT-h **Despliegue público** en dominio real (no vale solo local)

## Eventos (sección 5) — detalle en `04-eventos.md`

`product.viewed`, `cart.item_added`, `checkout.started`, `order.created`,
`payment.simulated`, `support.requested` o `incident.created`.
Explicar cómo se generan, dónde se almacenan y cómo los consumiría la Tarea 2.

## Entrega (sección 6)

- [ ] Enlace al screencast en YouTube (máx. **7 min**, ≥1280×720, sin acelerar, 4-6 diapositivas + demo, intervienen los **3** miembros)
- [ ] URL pública de la app desplegada
- [ ] Repositorio GitHub con historial que refleje a los 3 miembros
- [ ] ZIP del código fuente subido al Campus Virtual
- [ ] README: instalación, ejecución, usuarios de prueba, limitaciones conocidas
- [ ] Memoria PDF, 5-6 páginas, que **complemente** (no repita) el screencast:
  1. Caso de empresa, arquitectura, decisiones y alternativas
  2. Modelo de datos
  3. Eventos: generación, almacenamiento, consumo en Tarea 2
  4. Limitaciones
  5. Uso declarado de IA generativa (anexo: herramientas, tareas, fragmentos asistidos, errores de la IA, cambios del grupo, validación)
  6. Declaración del punto de partida (plantilla de terceros / IA / desde cero)
- [ ] Turnitin: memoria y código se analizan por similitud → no copiar plantillas sin declararlas

## Rúbrica (10 puntos)

| Criterio | Pts | Qué se mira |
|---|---|---|
| Flujo de compra y reglas de negocio | 2 | catálogo → carrito → checkout → pago simulado → pedido con estado coherente |
| Modelo de datos y persistencia | 1,5 | entidades, relaciones, datos realistas, consistencia UI↔lógica↔datos |
| Arquitectura y mantenibilidad | 1,5 | separación de capas, claridad, estructura repo, doc de ejecución, sin dependencias innecesarias, justificación y alternativas |
| Diseño, UX y confianza | 1 | claridad visual, navegación, info de producto, accesibilidad básica, transparencia "prototipo académico" |
| Eventos y trazabilidad | 1 | se registran, y el grupo explica generación/almacenamiento/reutilización |
| Seguridad, privacidad, responsabilidad | 1 | cuentas de prueba, sin credenciales reales, validación, datos personales prudentes |
| Documentación y memoria | 1 | calidad y completitud de memoria y README |
| Screencast, comprensión, uso crítico de IA | 1 | demo clara, 3 miembros equilibrados, reflexión sobre IA |

## Penalizaciones y topes

- Sin flujo de compra → máx. **5**. Sin persistencia → máx. **6**. Sin evidencia ejecutable/doc mínima → máx. **6**.
- Credenciales/datos personales reales o claves privadas → penalización grave.
- Screencast que no acredite comprensión → reducción individual o grupal. Falta de intervención de un miembro → suspenso para esa persona.
- Entrega sustancialmente generada por IA sin comprensión/validación → puede no superarse.
- Omitir o falsear la declaración del punto de partida → irregularidad grave.
