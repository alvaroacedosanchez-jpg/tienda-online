# 01 · Arquitectura

## Visión general

```
Navegador ──► React SPA (estática) ──fetch /api/*──► Laravel (routes/api.php)
                                                      │ Controllers + Form Requests  (HTTP y validación)
                                                      │ Services                      (reglas de negocio)
                                                      │ Models (Eloquent)             (persistencia)
                                                      ▼
                                                    MySQL
```

Mismo origen en producción (`https://midominio.xx/` sirve la SPA y `/api` la API), por lo que
no se necesita CORS y la sesión va en cookie `HttpOnly; SameSite=Lax` (Sanctum, modo SPA).

## Capas y responsabilidades

| Capa | Dónde | Hace | No hace |
|---|---|---|---|
| Interfaz | `frontend/src` | Render, navegación, estado de carrito, formularios | Calcular precios finales "de verdad" |
| Rutas y controladores | `backend/routes/api.php`, `app/Http/Controllers` | Recibir la petición, llamar al servicio, devolver un Resource | SQL, reglas de negocio |
| Validación | `app/Http/Requests` (Form Requests) | Reglas de entrada, mensajes de error | Lógica de negocio |
| Salida | `app/Http/Resources` | Dar forma al JSON (céntimos, fechas, campos públicos) | Consultas |
| Servicios | `app/Services` | `PricingService`, `OrderService`, `PaymentSimulator`, `EventLogger`: totales, IVA, envío, cupones, transiciones, eventos | Conocer HTTP |
| Persistencia | `app/Models` + migraciones | Eloquent: relaciones, casts, scopes | Reglas de negocio |
| Permisos | Policies + middleware `admin` | Quién ve/cambia qué | — |

Nota para la memoria: no se añade una capa de repositorios; Eloquent ya es la capa de
persistencia y duplicarla sería sobreingeniería para este alcance.

## Estructura propuesta

```
backend/                     # composer create-project laravel/laravel backend
  app/Http/Controllers/  app/Http/Requests/  app/Http/Resources/
  app/Models/  app/Services/  app/Enums/ (OrderStatus, ...)  app/Policies/
  database/migrations/  database/seeders/  database/factories/
  routes/api.php  routes/web.php   # web.php: fallback que sirve la SPA
  tests/Feature/  tests/Unit/
frontend/
  src/api/client.js        # fetch con cookies + cabecera X-XSRF-TOKEN, manejo de errores
  src/context/             # CartContext, AuthContext
  src/components/  src/pages/  src/hooks/
  vite.config.js           # proxy /api y /sanctum -> http://localhost:8000
```

## Estado del carrito

Carrito en el cliente (`localStorage` vía `CartContext`) con `{productId, qty}`. El servidor
recibe solo ids y cantidades, **recalcula precios desde BBDD** y valida stock. Alternativa
(carrito en servidor) descartada por complejidad; mencionar en la memoria.

## Autenticación

Usuarios de prueba sembrados (cliente y admin). **Laravel Sanctum en modo SPA** (cookie de
sesión + protección CSRF): `GET /sanctum/csrf-cookie`, luego `POST /api/login`. Rol `admin`
comprobado con middleware/Policies. Alternativa: tokens Bearer de Sanctum (más simple de
programar, pero el token vive en `localStorage` y es más expuesto a XSS) — decisión a justificar.
Sin registro abierto (evita datos personales reales); registro opcional en la fase final.

## Desarrollo local

Requisitos: Docker, PHP 8.x, Composer, Node.js (`brew install php composer node`).

1. `docker compose up -d` (MySQL + phpMyAdmin, ver `docker-compose.yml`).
2. `cd backend && cp .env.example .env && php artisan key:generate`. En `backend/.env`:
   `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   (los mismos valores que el `.env` de la raíz), `SESSION_DRIVER=database`,
   `SANCTUM_STATEFUL_DOMAINS=localhost:5173`.
3. `php artisan migrate --seed` y `php artisan serve` (puerto 8000).
4. `cd frontend && npm run dev`; Vite hace proxy de `/api` y `/sanctum` al 8000, así el navegador ve un único origen.

Hay **dos ficheros `.env`** distintos (raíz → Docker; `backend/` → Laravel). Ninguno se versiona.

## Despliegue en DonDominio (hosting básico compartido)

Límites conocidos: PHP 8.x, MySQL/MariaDB (10 BBDD de 100 MB), sin Node ni procesos persistentes.
**Verificar en el panel**: versión de PHP (Laravel exige una mínima; mirar `composer.json`), motor
y versión de BBDD, si hay **acceso SSH**, si se puede cambiar la carpeta raíz del dominio y
si se pueden subir ficheros por encima de `public_html`.

Diseño propuesto: **una sola unidad de despliegue**. El build de React se genera dentro de
`backend/public/spa/` y Laravel sirve ese `index.html` en una ruta *fallback* para todo lo que
no sea `/api`. Así Laravel gestiona rutas, sesión y cookies en un único origen.

1. Instalar dependencias de producción en local: `composer install --no-dev --optimize-autoloader`.
2. `npm run build` (Vite con `base: '/spa/'` y `outDir: ../backend/public/spa`).
3. Estructura en el servidor (patrón habitual en hosting compartido):
   - Carpeta de la app **fuera** de `public_html` (p. ej. `~/laravel/`) con todo menos `public/`.
   - Contenido de `backend/public/` dentro de `public_html/`, editando `index.php` para que las rutas
     `require` apunten a `../laravel/vendor/autoload.php` y `../laravel/bootstrap/app.php`.
   - Si no se puede subir por encima de `public_html`: bloquear acceso web a la app con `.htaccess`
     (`Require all denied`) y revisar que `.env` y `vendor` no sean descargables.
4. BBDD: crear BBDD y usuario en el panel. **Sin SSH no se puede ejecutar `php artisan migrate`**:
   exportar la BBDD local (esquema + datos de prueba) con `mysqldump` e importarla por phpMyAdmin.
   Con SSH, usar `php artisan migrate --seed --force`.
5. Crear `.env` **en el servidor** con `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_KEY`
   (generada con `php artisan key:generate --show` y pegada allí), credenciales de la BBDD del
   hosting y `SANCTUM_STATEFUL_DOMAINS=<dominio>`. `SESSION_SECURE_COOKIE=true`.
6. Permisos de escritura en `storage/` y `bootstrap/cache/`.
7. HTTPS forzado. Probar el flujo completo en la URL pública y anotarla en el README.

Detalle y problemas típicos en la skill `desplegar-dondominio`. Recomendado: **despliegue mínimo
(un `/api/health`) en la Fase 1** para detectar pronto diferencias entre local y hosting.

## Decisiones a justificar en la memoria

- Laravel (requisito del profesor) frente a PHP sin framework: más estructura y herramientas
  (migraciones, validación, ORM, auth) a cambio de más peso y curva de aprendizaje.
- SPA con React vs. Blade/Inertia: SPA con API JSON separa bien interfaz y lógica; coste: SEO limitado.
- Una sola unidad de despliegue (SPA servida por Laravel) vs. frontend y backend desplegados por separado.
- Sanctum con cookie vs. tokens Bearer.
- Carrito en cliente vs. servidor.
- Eventos en tabla MySQL vs. fichero/cola; Servicio `EventLogger` vs. Events/Listeners de Laravel (ver `04-eventos.md`).
- Pago: simulación propia vs. modo test de pasarela (Stripe test) — simulación propia para no depender de claves.
