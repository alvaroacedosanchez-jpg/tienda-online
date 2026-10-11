<x-mail::message>
# Nueva solicitud de soporte #{{ $ticket->id }}

- **Cliente:** {{ $ticket->name }} ({{ $ticket->email }})
- **Asunto:** {{ $ticket->subject }}
- **Pedido:** {{ $ticket->order?->reference ?? '—' }}
- **Fecha:** {{ $ticket->created_at->format('d/m/Y H:i') }}

<x-mail::panel>
{{ $ticket->message }}
</x-mail::panel>

Al responder a este correo, la respuesta llega directamente al cliente.

<x-mail::button :url="route('admin.tickets.index')">
Ver incidencias en el back-office
</x-mail::button>
</x-mail::message>
