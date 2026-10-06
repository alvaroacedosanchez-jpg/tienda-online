# 05 · Plan por fases

Orden pensado para **aprender React de forma progresiva** y tener siempre algo desplegable.
Cada fase termina con algo que funciona, un commit/PR por funcionalidad y una entrada en
`docs/ai-usage-log.md` si se ha usado IA.

## Reparto sugerido (a acordar con el grupo)

Cada persona lidera un área **pero las tres deben saber explicar todo** (el screencast lo comprueba).

| Persona | Área principal | Debe saber explicar además |
|---|---|---|
| A | Frontend tienda (catálogo, ficha, carrito, checkout) | Cómo llega el pedido a la BBDD |
| B | API Laravel, reglas de negocio, BBDD | Cómo se pinta en React |
| C | Back-office, eventos, despliegue, memoria/README | Flujo completo de compra |

Rotar tareas pequeñas (un componente, un endpoint, un test) para que los commits sean de los tres.

## Fases

| Fase | Contenido | Resultado verificable |
|---|---|---|
| 0 | Repo GitHub con los 3, `.gitignore`, `docker-compose.yml`, `.env.example`; esqueleto `backend/` (`composer create-project`, `install:api`, conexión MySQL) y `frontend/` (Vite). Empresa: Piquantum | `docker compose up` + `migrate` + "hola mundo" React + `/api/health` |
| 1 | Migraciones, modelos, seeders; endpoints de lectura (categorías, productos). **Despliegue mínimo** en DonDominio | Catálogo en JSON desde la BBDD, también en la URL pública |
| 2 | React básico: layout, banner académico, Home, Catálogo, Ficha (componentes, props, `useState`, `useEffect`, Router) | Navegar el catálogo real |
| 3 | Carrito (Context + localStorage), `/api/cart/quote`, evento `cart.item_added`/`product.viewed` | Carrito con totales del servidor |
| 4 | Login, checkout con validación, `POST /api/orders`, pago simulado, estados, `checkout.started`/`order.created`/`payment.simulated` | Compra completa persistida |
| 5 | Back-office: pedidos, detalle, cambio de estado, eventos, exportación JSON/CSV; soporte/incidencias | Admin gestiona pedidos |
| 6 | **Primer despliegue en DonDominio** (adelantarlo si es posible: desplegar pronto evita sustos) | URL pública funcionando |
| 7 | Pulido UX/accesibilidad, tests de `PricingService`/transiciones, seguridad, README | Checklist de rúbrica en verde |
| 8 | Memoria PDF (5-6 págs), anexo de IA, guion y grabación del screencast, ZIP, entrega | Todo subido antes del cierre |

> Recomendación: hacer un **despliegue mínimo en la fase 1** (aunque sea solo `/api/health` y la
> BBDD importada) para descubrir pronto las diferencias entre local y hosting.

## Git

- `main` protegida por convención; ramas `feat/<área>-<tema>`; PR revisado por otro miembro.
- Antes de empezar una tarea: `git pull` en `main` y crear la rama. Evitar que dos personas toquen
  el mismo fichero a la vez (sobre todo migraciones: nombres con timestamp distinto, y avisar en el grupo).
- Cada miembro registra sus propias entradas en `docs/ai-usage-log.md` (con su nombre en "Revisado por").
- Commits pequeños, mensajes en imperativo ("Añade endpoint de productos"). Autor real de cada persona.
- Convención de commits con IA: no se oculta; se registra en `ai-usage-log.md`.

## Screencast (≤ 7 min) — guion orientativo

| Min | Contenido | Quién |
|---|---|---|
| 0:00-1:00 | Caso de empresa, objetivo, reparto (diap. 1) | A |
| 1:00-2:00 | Arquitectura y flujo (diap. 2) | B |
| 2:00-4:30 | Demo: catálogo → carrito → checkout → pago → pedido; ver eventos | A + C |
| 4:30-5:30 | Evidencias: BBDD, eventos, export (diap. 3-4) | B/C |
| 5:30-6:30 | Limitaciones, decisiones críticas, uso de IA (diap. 5) | los tres |
| 6:30-7:00 | Conclusión (diap. 6) | C |

Reglas: 1280×720 mínimo, sin acelerar el vídeo, sin salirse de 7:00, los tres intervienen con tiempo equilibrado.

## Entrega final

- [ ] URL pública y banner de prototipo visibles · [ ] repo GitHub con commits de los 3
- [ ] ZIP del código · [ ] README completo · [ ] memoria PDF con anexo de IA y declaración de punto de partida
- [ ] YouTube: enlace público/no listado · [ ] revisión de secretos (`git log -p` y búsqueda de claves/credenciales)
