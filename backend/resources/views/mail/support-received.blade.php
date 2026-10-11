<x-mail::message>
# Hemos recibido tu solicitud

Hola, {{ $ticket->name }}: hemos registrado tu solicitud de soporte con el número **#{{ $ticket->id }}**. Te responderemos lo antes posible.

**Asunto:** {{ $ticket->subject }}
@if ($ticket->order)

**Pedido:** {{ $ticket->order->reference }}
@endif

<x-mail::panel>
{{ $ticket->message }}
</x-mail::panel>

Gracias,<br>
{{ config('shop.name') }}

@include('mail._prototype')
</x-mail::message>
