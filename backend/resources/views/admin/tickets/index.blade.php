@extends('layouts.app')
@section('title', 'Incidencias')
@section('content')
<h1>Back-office · Solicitudes de soporte</h1>
@include('partials.admin-nav')

<table class="table">
    <thead><tr><th>#</th><th>Fecha</th><th>Contacto</th><th>Pedido</th><th>Asunto</th><th>Mensaje</th></tr></thead>
    <tbody>
    @forelse ($tickets as $ticket)
        <tr>
            <td>{{ $ticket->id }}</td>
            <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $ticket->name }}<br><span class="small muted">{{ $ticket->email }}</span></td>
            <td>@if ($ticket->order)<a href="{{ route('admin.orders.show', $ticket->order) }}">{{ $ticket->order->reference }}</a>@endif</td>
            <td>{{ $ticket->subject }}</td>
            <td>{{ $ticket->message }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No hay solicitudes.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $tickets->links() }}
@endsection
