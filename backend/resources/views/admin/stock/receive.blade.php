@extends('layouts.app')
@section('title', 'Recepción de Mercancía')

@section('content')
<h1>Recepción y Escaneo de Stock</h1>
@include('partials.admin-nav')

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
    <!-- FORMULARIO DE ESCANEO -->
    <div class="card pad">
        <h3>Escaneo de Producto / Bulto</h3>
        <p class="muted">El campo se enfocará automáticamente tras cada lectura.</p>

        <form method="POST" action="{{ route('admin.stock.receive.process') }}">
            @csrf

            <div style="margin-bottom: 1rem;">
                <label for="code"><strong>Código SKU / Escáner:</strong></label>
                <input 
                    type="text" 
                    id="code" 
                    name="code" 
                    class="form-control" 
                    placeholder="Ej. SAL-001 o SAL-002-150" 
                    required 
                    autofocus 
                    style="font-size: 1.25rem; padding: 10px; width: 100%; margin-top: 5px;"
                >
                @error('code')
                    <span style="color: #dc2626; font-size: 0.9rem; display: block; margin-top: 5px;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="quantity"><strong>Cantidad a añadir:</strong></label>
                <input 
                    type="number" 
                    id="quantity" 
                    name="quantity" 
                    value="1" 
                    min="1" 
                    required 
                    style="font-size: 1.1rem; padding: 8px; width: 100px; margin-top: 5px;"
                >
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 10px;">
                ➕ Sumar a Inventario
            </button>
        </form>
    </div>

    <!-- GUÍA DE CÓDIGOS DISPONIBLES -->
    <div class="card pad">
        <h3 style="margin-top: 0;">Catálogo de Códigos de Escaneo</h3>
        <table class="table" style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Código a escanear</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                @if ($product->variants->isNotEmpty())
                    @foreach ($product->variants as $variant)
                        @php
                            // Extrae la parte numérica o relevante del tamaño para el ejemplo (ej. "150ml" -> "150")
                            preg_match('/\d+/', $variant->size, $matches);
                            $sizeCode = $matches[0] ?? $variant->size;
                        @endphp
                        <tr>
                            <td><strong>{{ $product->name }}</strong> ({{ $variant->size }})</td>
                            <td><span class="badge badge-pending">Variante</span></td>
                            <td><code>{{ $product->sku }}-{{ $sizeCode }}</code></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td><strong>{{ $product->name }}</strong></td>
                        <td><span class="badge badge-completed">Simple</span></td>
                        <td><code>{{ $product->sku }}</code></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="3">No hay productos en el catálogo.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('code');
        if (input) {
            input.focus();
            input.select();
        }
    });
</script>
@endsection