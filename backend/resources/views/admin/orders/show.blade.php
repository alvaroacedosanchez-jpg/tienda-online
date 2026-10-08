@extends('layouts.app')
@section('title', 'Pedido '.$order->reference)
@section('content')
<h1>Pedido {{ $order->reference }}</h1>
@include('partials.admin-nav')

<p>Estado actual: <span class="badge badge-{{ $order->status }}">{{ $order->statusLabel() }}</span></p>

@if ($order->allowedTransitions())
    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="inline-form">
        @csrf @method('PATCH')
        <label for="status">Cambiar a</label>
        <select id="status" name="status">
            @foreach ($order->allowedTransitions() as $next)
                <option value="{{ $next }}">{{ \App\Models\Order::STATUS_LABELS[$next] }}</option>
            @endforeach
        </select>
        <button class="btn btn-small" type="submit">Actualizar</button>
    </form>
    @error('status')<p class="error">{{ $message }}</p>@enderror
@endif

<div class="two-col">
    <div>
        <h2>Cliente (datos de prueba)</h2>
        <p>{{ $order->customer->name }} · {{ $order->customer->email }}<br>
        {{ $order->customer->address }}, {{ $order->customer->postal_code }} {{ $order->customer->city }}</p>

        <h2>Líneas</h2>
        <table class="table">
            <thead><tr><th>Producto</th><th>Uds.</th><th>Precio</th><th>Total</th></tr></thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price, 2, ',', '.') }} €</td><td>{{ number_format($item->line_total, 2, ',', '.') }} €</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div>
        @include('partials.summary', ['summary' => [
            'subtotal' => $order->subtotal, 'discount' => $order->discount, 'discount_code' => $order->discount_code,
            'discount_percent' => $order->discount_code ? config('shop.discount_codes')[$order->discount_code] ?? 0 : 0,
            'shipping' => $order->shipping, 'tax' => $order->tax, 'total' => $order->total,
        ]])
        <h2>Pagos simulados</h2>
        @forelse ($order->payments as $payment)
            <p class="small">{{ $payment->reference }} · {{ $payment->method }} · {{ $payment->status }} · {{ number_format($payment->amount, 2, ',', '.') }} €{{ $payment->card_last4 ? ' · ****'.$payment->card_last4 : '' }}</p>
        @empty
            <p class="muted">Sin pagos.</p>
        @endforelse
    </div>
</div>
@endsection
