@extends('layouts.app')
@section('title', 'Inicio')
@section('content')
<section class="hero">
    <h1>{{ config('shop.name') }}</h1>
    <p>{{ config('shop.tagline') }}</p>
    <a class="btn" href="{{ route('catalog') }}">Ver catálogo</a>
</section>

<h2>Categorías</h2>
<div class="grid grid-3">
    @foreach ($categories as $category)
        <a class="card category" href="{{ route('catalog', ['categoria' => $category->slug]) }}">
            <h3>{{ $category->name }}</h3>
            <p class="muted">{{ $category->description }}</p>
            <p class="small">{{ $category->products_count }} productos</p>
        </a>
    @endforeach
</div>

<h2>Novedades</h2>
<div class="grid grid-4">
    @foreach ($featured as $product)
        @include('partials.product-card', ['product' => $product])
    @endforeach
</div>

<section class="info">
    <p>✔ Envío gratis desde {{ number_format(config('shop.free_shipping_from'), 0, ',', '.') }} € · ✔ Código de prueba <strong>BIENVENIDA10</strong> (−10 %) · ✔ Precios con IVA incluido</p>
</section>
@endsection
