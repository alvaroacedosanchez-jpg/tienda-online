@extends('layouts.app')
@section('title', 'Carrito')
@section('content')
<h1>Carrito</h1>

@if ($items->isEmpty())
    <p>Tu carrito está vacío. <a href="{{ route('catalog') }}">Ir al catálogo</a></p>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        {{-- $itemKey es la clave de la línea en la sesión ("12" o "12_5" si tiene variante), la que esperan las rutas --}}
        @foreach ($items as $itemKey => $item)
            @php
                // Detectar variante y precio real
                $variant = $item['variant'] ?? null;
                $price = $variant ? $variant->price : $item['product']->price;
                $thumb = $variant ? $variant->imageUrl() : $item['product']->imageUrl();
            @endphp
            <tr>
                <td>
                    <div class="line-product">
                        @if ($thumb)
                            <img class="line-thumb" src="{{ $thumb }}" alt="" width="40" height="40">
                        @endif
                        <span>
                            <a href="{{ route('product.show', $item['product']) }}">{{ $item['product']->name }}</a>
                            @if ($variant)
                                <span class="badge">{{ $variant->size }}</span>
                            @endif
                        </span>
                    </div>
                </td>
                <td>{{ number_format($price, 2, ',', '.') }} €</td>
                <td>
                    <form method="POST" action="{{ route('cart.update', $itemKey) }}" class="inline-form">
                        @csrf
                        @method('PATCH')
                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="0" max="10" aria-label="Cantidad de {{ $item['product']->name }}">
                        <button class="btn btn-small" type="submit">Actualizar</button>
                    </form>
                </td>
                <td>{{ number_format($item['line_total'], 2, ',', '.') }} €</td>
                <td>
                    <form method="POST" action="{{ route('cart.remove', $itemKey) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-small btn-light" type="submit">Quitar</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="two-col">
        <form method="POST" action="{{ route('cart.code') }}" class="card pad">
            @csrf
            <label for="code">Código de descuento</label>
            <input type="text" id="code" name="code" value="{{ old('code', $code) }}" placeholder="BIENVENIDA10">
            @error('code')<p class="error">{{ $message }}</p>@enderror
            <button class="btn btn-small" type="submit">Aplicar</button>
        </form>

        @include('partials.summary', ['summary' => $summary])
    </div>

    <p><a class="btn" href="{{ route('checkout.show') }}">Continuar al checkout</a></p>
@endif
@endsection