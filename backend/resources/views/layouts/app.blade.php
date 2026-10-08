<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inicio') · {{ config('shop.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="banner" role="note">
    ⚠️ Prototipo académico sin actividad comercial real. No introduzcas datos personales reales: los pagos son simulados y los productos no se envían.
</div>

<header class="site-header">
    <div class="container header-row">
        <a href="{{ route('home') }}" class="brand">{{ config('shop.name') }}</a>
        <nav aria-label="Principal">
            <a href="{{ route('catalog') }}">Catálogo</a>
            <a href="{{ route('support.create') }}">Soporte</a>
            <a href="{{ route('cart.show') }}">Carrito ({{ app(\App\Services\CartService::class)->count() }})</a>
            @auth
                @if (auth()->user()->is_admin)
                    <a href="{{ route('admin.orders.index') }}">Back-office</a>
                @endif
            @endauth
        </nav>
    </div>
</header>

<main class="container">
    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->has('cart'))
        <div class="alert alert-error" role="alert">{{ $errors->first('cart') }}</div>
    @endif
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <p>{{ config('shop.name') }} — proyecto académico (Soluciones Informáticas para la Empresa). Datos ficticios. Sin pagos ni envíos reales.</p>
    </div>
</footer>
</body>
</html>
