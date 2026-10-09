@extends('layouts.app')
@section('title', $product->name)
@section('content')
<p class="muted small"><a href="{{ route('catalog') }}">Catálogo</a> / <a href="{{ route('catalog', ['categoria' => $product->category->slug]) }}">{{ $product->category->name }}</a></p>

@php
    // Si hay variantes, la ficha arranca con la primera (la seleccionada por defecto en el desplegable)
    $selected = $product->variants->first();
    $price = $selected?->price ?? $product->price;
    $stock = $selected?->stock ?? $product->stock;
    $imageUrl = $selected ? $selected->imageUrl() : $product->imageUrl();
    $imageAlt = $selected ? "{$product->name} ({$selected->size})" : $product->name;
    $stockText = fn (int $units) => $units <= 0 ? 'Agotado' : ($units <= 5 ? "Últimas {$units} unidades" : 'En stock');
    $stockClass = fn (int $units) => $units <= 0 ? 'error' : ($units <= 5 ? 'warn' : 'ok');
@endphp

<div class="product-detail">
    <div class="big-thumb">
        @if ($imageUrl)
            <img id="product-image" src="{{ $imageUrl }}" alt="{{ $imageAlt }}" width="320" height="320">
        @else
            <span aria-hidden="true">{{ $product->emoji }}</span>
        @endif
    </div>
    <div>
        <h1>{{ $product->name }}</h1>
        <p class="muted">SKU: {{ $product->sku }}</p>
        <p class="price big"><span id="product-price">{{ number_format($price, 2, ',', '.') }} €</span> <span class="small muted">IVA incluido</span></p>
        <p>{{ $product->description }}</p>

        @if ($product->stock > 0)
            <p id="product-stock" class="{{ $stockClass($stock) }}" aria-live="polite">{{ $stockText($stock) }}</p>
            <form method="POST" action="{{ route('cart.add', $product) }}" class="inline-form">
                @csrf
                @if ($product->variants->isNotEmpty())
                    <label for="variant_id">Tamaño</label>
                    <select name="variant_id" id="variant_id" required>
                        @foreach ($product->variants as $variant)
                            <option value="{{ $variant->id }}"
                                data-image="{{ $variant->imageUrl() }}"
                                data-alt="{{ $product->name }} ({{ $variant->size }})"
                                data-price="{{ number_format($variant->price, 2, ',', '.') }} €"
                                data-stock="{{ $variant->stock }}"
                                @disabled($variant->stock <= 0)>
                                {{ $variant->size }} · {{ number_format($variant->price, 2, ',', '.') }} €{{ $variant->stock <= 0 ? ' (agotado)' : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <label for="quantity">Cantidad</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ max(1, min(10, $stock)) }}">
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

@if ($product->variants->isNotEmpty())
    @push('scripts')
    <script>
        // Al elegir otro tamaño se actualizan la imagen, el precio, el stock y la cantidad máxima
        (function () {
            const select = document.getElementById('variant_id');
            const image = document.getElementById('product-image');
            const price = document.getElementById('product-price');
            const stock = document.getElementById('product-stock');
            const quantity = document.getElementById('quantity');
            if (!select) return;

            select.addEventListener('change', function () {
                const option = select.selectedOptions[0];
                const units = parseInt(option.dataset.stock, 10);

                if (image && option.dataset.image) {
                    image.src = option.dataset.image;
                    image.alt = option.dataset.alt;
                }
                price.textContent = option.dataset.price;
                stock.textContent = units <= 0 ? 'Agotado' : (units <= 5 ? 'Últimas ' + units + ' unidades' : 'En stock');
                stock.className = units <= 0 ? 'error' : (units <= 5 ? 'warn' : 'ok');
                quantity.max = Math.max(1, Math.min(10, units));
                if (parseInt(quantity.value, 10) > quantity.max) quantity.value = quantity.max;
            });
        })();
    </script>
    @endpush
@endif

@if ($related->isNotEmpty())
    <h2>Productos relacionados</h2>
    <div class="grid grid-3">
        @foreach ($related as $item)
            @include('partials.product-card', ['product' => $item->setRelation('category', $product->category)])
        @endforeach
    </div>
@endif
@endsection
