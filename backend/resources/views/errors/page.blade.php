<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="container error-page">
    <p class="error-code">{{ $code }}</p>
    <h1>{{ $title }}</h1>
    <p class="muted">{{ $message }}</p>
    <p><a class="btn" href="{{ url('/') }}">Volver a la tienda</a></p>
    <p class="small muted">{{ config('shop.name') }} es un prototipo académico sin actividad comercial real.</p>
</main>
</body>
</html>
