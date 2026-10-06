---
name: desplegar-dondominio
description: Guía y checklist para desplegar Laravel (API) + el build de React en el hosting básico compartido de DonDominio con MySQL. Úsala al preparar o depurar el despliegue público.
---

# Despliegue de Laravel + React en DonDominio (hosting compartido)

Contexto: el hosting básico ofrece PHP 8.x, MySQL/MariaDB (varias BBDD de tamaño limitado), FTP,
phpMyAdmin y `.htaccess`/`mod_rewrite`; **no Node.js ni procesos persistentes**. No se sabe aún si
hay SSH ni si se puede subir por encima de `public_html`: **compruébalo en el panel real antes de
fijar el procedimiento** y no inventes características. Diseño de referencia: `docs/01-arquitectura.md`.

## Qué cambia respecto a local

| Local | Hosting compartido |
|---|---|
| `composer install`, `php artisan migrate --seed` | Sin SSH no hay Composer ni Artisan: se sube `vendor/` ya instalado y la BBDD se importa por phpMyAdmin |
| `php artisan serve` | Apache sirve `public_html/`; Laravel necesita `.htaccess` y `mod_rewrite` |
| `backend/.env` con credenciales de Docker | `.env` propio en el servidor, creado a mano; **nunca** se sube el de local |

## Pasos

1. **Versión de PHP**: ajustarla en el panel a una compatible con el `composer.json` de Laravel (y que
   coincida con la que se usó para generar `vendor/`; si no, `composer install --ignore-platform-reqs` es una trampa).
2. **BBDD**: crear BBDD y usuario en el panel. Credenciales solo en el `.env` del servidor (no en el chat ni en git).
3. **Esquema y datos**: con SSH, `php artisan migrate --seed --force`. Sin SSH, exportar la BBDD local ya
   migrada y sembrada (`mysqldump`) e importarla por phpMyAdmin (cuidado con el límite de tamaño de importación).
4. **Build del frontend**: `cd frontend && npm run build` (salida en `backend/public/spa`).
5. **Dependencias**: `cd backend && composer install --no-dev --optimize-autoloader`.
6. **Subida** (preferible un ZIP y descomprimir en el servidor; miles de ficheros por FTP son lentos y fallan):
   - Ideal: app Laravel fuera de `public_html` y contenido de `public/` dentro; ajustar las rutas de `public/index.php`.
   - Si no se puede salir de `public_html`: subir todo y proteger con `.htaccess` (`Require all denied`) las carpetas
     `app`, `bootstrap`, `config`, `database`, `routes`, `storage`, `vendor` y los ficheros `.env`, `composer.*`.
   - Excluir siempre: `.env` local, `.git`, `node_modules`, `tests` (opcional), `storage/logs/*`.
7. **`.env` del servidor**: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `APP_KEY`
   (`php artisan key:generate --show` en local y pegarla allí), `DB_*` del hosting, `SESSION_SECURE_COOKIE=true`,
   `SANCTUM_STATEFUL_DOMAINS=<dominio>`. No usar `config:cache` en local con valores de local.
8. **Permisos**: `storage/` y `bootstrap/cache/` escribibles (755/775 según el panel).
9. **HTTPS** activo y redirección forzada.
10. **Prueba de humo en la URL pública**: `/api/health`, catálogo, login de prueba, compra completa, pago aprobado
    y rechazado, back-office, exportación de eventos, recarga directa de una ruta profunda de React (`/producto/xyz`).
11. Anotar la URL en el README y en la memoria.

## Problemas típicos

- **500 sin mensaje**: mirar `storage/logs/laravel.log`; suele ser `APP_KEY` vacía, permisos de `storage/` o PHP incompatible.
- **404 en todas las rutas salvo la raíz**: `.htaccess` de Laravel ausente o `mod_rewrite` sin habilitar.
- **419 (CSRF token mismatch) o 401 tras login**: `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `APP_URL` o
  `SESSION_SECURE_COOKIE` mal; el frontend debe llamar a `/sanctum/csrf-cookie` primero y enviar `X-XSRF-TOKEN`.
- **Página en blanco con la SPA**: `base` de Vite incorrecta (`/spa/`) o el *fallback* de `routes/web.php` mal definido.
- **Estilos/imagenes rotos**: rutas absolutas a `localhost`; el frontend debe usar rutas relativas (`/api/...`).
- **Diferencias MySQL/MariaDB** y sensibilidad a mayúsculas en nombres de ficheros (Linux).
- Cambios en `.env` no se aplican: borrar `bootstrap/cache/config.php` si existe.

## Antes de subir

Revisar que no viaja nada de `.env`, `node_modules`, `.git` ni volcados con datos reales. Las contraseñas de
las cuentas de prueba deben ser ficticias y no reutilizadas en ningún otro sitio.
