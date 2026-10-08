@extends('layouts.app')
@section('title', $product->name)
@section('content')
<p class="muted small"><a href="{{ route('catalog') }}">Catálogo</a> / <a href="{{ route('catalog', ['categoria' => $product->category->slug]) }}">{{ $product->category->name }}</a></p>

<div class="product-detail">
    <div class="big-thumb" aria-hidden="true">{{ $product->emoji }}</div>
    <div>
        <h1>{{ $product->name }}</h1>
        <p class="muted">SKU: {{ $product->sku }}</p>
        <p class="price big">{{ number_format($product->price, 2, ',', '.') }} € <span class="small muted">IVA incluido</span></p>
        <p>{{ $product->description }}</p>

        @if ($product->stock > 0)
            <p class="{{ $product->stock <= 5 ? 'warn' : 'ok' }}">
                {{ $product->stock <= 5 ? 'Últimas '.$product->stock.' unidades' : 'En stock' }}
            </p>
            <form method="POST" action="{{ route('cart.add', $product) }}" class="inline-form">
                @csrf
                <label for="quantity">Cantidad</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ min(10, $product->stock) }}">
                <button class="btn" type="submit">Añadir al carrito</button>
            </form>
        @else
            <p class="error">Agotado</p>
        @endif

        @if ($product->specs)
            <h2>Características</h2>
            <table class="table">
                @foreach ($product->specs as $label => $value)
                    <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        @endif
        <p class="small muted">Devolución en 14 días (condición simulada). Envío {{ number_format(config('shop.shipping_cost'), 2, ',', '.') }} € o gratis desde {{ number_format(config('shop.free_shipping_from'), 0) }} €.</p>
    </div>
</div>

@if ($related->isNotEmpty())
    <h2>Productos relacionados</h2>
    <div class="grid grid-3">
        @foreach ($related as $item)
            @include('partials.product-card', ['product' => $item->setRelation('category', $product->category)])
        @endforeach
    </div>
@endif
@endsection
