@extends('layouts.app')
@section('title', 'Eventos')
@section('content')
<h1>Back-office · Eventos de negocio</h1>
@include('partials.admin-nav')

<p>
    <a class="btn btn-small" href="{{ route('admin.events.json', array_filter(['tipo' => $type])) }}">Exportar JSON</a>
    <a class="btn btn-small" href="{{ route('admin.events.csv', array_filter(['tipo' => $type])) }}">Exportar CSV</a>
</p>

<nav class="filters" aria-label="Filtrar por tipo">
    <a href="{{ route('admin.events.index') }}" @class(['chip', 'active' => ! $type])>Todos</a>
    @foreach ($types as $t)
        <a href="{{ route('admin.events.index', ['tipo' => $t]) }}" @class(['chip', 'active' => $type === $t])>{{ $t }}</a>
    @endforeach
</nav>

<table class="table">
    <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Pedido</th><th>Datos</th></tr></thead>
    <tbody>
    @forelse ($events as $event)
        <tr>
            <td>{{ $event->id }}</td>
            <td>{{ $event->occurred_at->format('d/m/Y H:i:s') }}</td>
            <td><code>{{ $event->type }}</code></td>
            <td>{{ $event->order_id }}</td>
            <td><code class="small">{{ json_encode($event->payload, JSON_UNESCAPED_UNICODE) }}</code></td>
        </tr>
    @empty
        <tr><td colspan="5">Aún no hay eventos.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $events->links() }}
@endsection
