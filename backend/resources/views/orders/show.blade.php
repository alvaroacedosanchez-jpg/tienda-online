@extends('layouts.app')
@section('title', 'Pedido '.$order->reference)
@section('content')
<h1>Pedido {{ $order->reference }}</h1>
<p>Estado: <span class="badge badge-{{ $order->status }}">{{ $order->statusLabel() }}</span> · {{ $order->created_at->format('d/m/Y H:i') }}</p>

<div class="two-col">
    <div>
        <table class="table">
            <thead><tr><th>Producto</th><th>Uds.</th><th>Precio</th><th>Total</th></tr></thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price, 2, ',', '.') }} €</td><td>{{ number_format($item->line_total, 2, ',', '.') }} €</td></tr>
            @endforeach
            </tbody>
        </table>
        <h2>Envío a</h2>
        <p>{{ $order->shipping_name }}<br>{{ $order->shipping_address }}<br>{{ $order->shipping_postal_code }} {{ $order->shipping_city }}</p>
    </div>
    <div>
        @include('partials.summary', ['summary' => [
            'subtotal' => $order->subtotal, 'discount' => $order->discount, 'discount_code' => $order->discount_code,
            'discount_percent' => $order->discount_code ? config('shop.discount_codes')[$order->discount_code] ?? 0 : 0,
            'shipping' => $order->shipping, 'tax' => $order->tax, 'total' => $order->total,
        ]])
        @foreach ($order->payments as $payment)
            <p class="small">Pago simulado {{ $payment->reference }}: {{ $payment->status === 'approved' ? 'aprobado' : 'rechazado' }}</p>
        @endforeach
        <p><a href="{{ route('support.create', ['pedido' => $order->reference]) }}">¿Algún problema con este pedido? Contacta con soporte</a></p>
    </div>
</div>
<p class="small muted">Guarda la referencia del pedido para consultarlo o para contactar con soporte.</p>
@endsection
