<x-mail::message>
# Tu pedido va de camino

Hola, {{ $order->shipping_name }}: tu pedido **{{ $order->reference }}** ha salido de nuestro almacén.

**Dirección de entrega:** {{ $order->shipping_address }}, {{ $order->shipping_postal_code }} {{ $order->shipping_city }}

<x-mail::button :url="route('orders.show', $order)">
Ver mi pedido
</x-mail::button>

Si hay algún problema con la entrega, [contacta con soporte]({{ route('support.create', ['pedido' => $order->reference]) }}).

Gracias,<br>
{{ config('shop.name') }}

@include('mail._prototype')
</x-mail::message>
