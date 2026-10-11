<x-mail::message>
# ¡Gracias por tu pedido, {{ $order->shipping_name }}!

Hemos recibido el pago de tu pedido **{{ $order->reference }}**. Lo estamos preparando.

<x-mail::table>
| Producto | Uds. | Importe |
|:--|:-:|--:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if ($item->variant_size) ({{ $item->variant_size }})@endif | {{ $item->quantity }} | {{ number_format($item->line_total, 2, ',', '.') }} € |
@endforeach
@if ($order->discount > 0)
| Descuento ({{ $order->discount_code }}) | | −{{ number_format($order->discount, 2, ',', '.') }} € |
@endif
| Envío | | {{ $order->shipping > 0 ? number_format($order->shipping, 2, ',', '.').' €' : 'Gratis' }} |
| **Total** (IVA incluido) | | **{{ number_format($order->total, 2, ',', '.') }} €** |
</x-mail::table>

**Envío a:** {{ $order->shipping_name }}, {{ $order->shipping_address }}, {{ $order->shipping_postal_code }} {{ $order->shipping_city }}

@if ($order->invoice)
Adjuntamos tu factura **{{ $order->invoice->number }}** en PDF. También la tienes en [Mis facturas]({{ route('account.invoices') }}).
@endif

<x-mail::button :url="route('orders.show', $order)">
Ver mi pedido
</x-mail::button>

Gracias,<br>
{{ config('shop.name') }}

@include('mail._prototype')
</x-mail::message>
