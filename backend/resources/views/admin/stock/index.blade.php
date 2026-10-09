@extends('layouts.app')
@section('title', 'Control de Stock')
@section('content')
<h1>Control de Stock e Inventario</h1>
@include('partials.admin-nav')
    
<div class="card pad">
    <table class="table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio Base</th>
                <th>Variantes (Tamaño / Stock)</th>
                <th>Stock Total</th>
                <th>Estado de Inventario</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($products as $product)
            @php
                $hasVariants = $product->variants->isNotEmpty();
                $totalStock = $hasVariants ? $product->variants->sum('stock') : $product->stock;
                
                // Comprobamos si el producto base o alguna de sus variantes está en nivel crítico
                $hasLowStock = $hasVariants 
                    ? $product->variants->contains(fn($v) => $v->stock <= $lowStockThreshold)
                    : $product->stock <= $lowStockThreshold;
            @endphp
            <tr>
                <td>
                    <strong>{{ $product->name }}</strong>
                </td>
                <td>{{ number_format($product->price, 2, ',', '.') }} €</td>
                <td>
                    @if ($hasVariants)
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            @foreach ($product->variants as $variant)
                                <li>
                                    <code>{{ $variant->size }}</code>: 
                                    <strong>{{ $variant->stock }} uds.</strong>
                                    @if ($variant->stock <= $lowStockThreshold)
                                        <span class="badge badge-cancelled" style="font-size: 0.75rem;">¡Pocas unidades!</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <span class="muted">Sin variantes</span>
                    @endif
                </td>
                <td>
                    <strong>{{ $totalStock }} uds.</strong>
                </td>
                <td>
                    @if ($totalStock == 0)
                        <span class="badge badge-cancelled">Agotado</span>
                    @elseif ($hasLowStock)
                        <span class="badge badge-pending">Stock Bajo (≤ {{ $lowStockThreshold }})</span>
                    @else
                        <span class="badge badge-completed">Stock Suficiente</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No hay productos registrados en la base de datos.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection