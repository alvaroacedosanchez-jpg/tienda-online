# Backend de Piquantum

Aplicación Laravel de la tienda (prototipo académico). La documentación completa
—instalación, usuarios de prueba, eventos, pagos con Redsys, correos, despliegue y
limitaciones— está en el [README principal](../README.md) del repositorio.

Resumen rápido para arrancar en local:

```bash
composer install
copy .env.example .env      # en Linux/macOS: cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Tests: `php artisan test`.
