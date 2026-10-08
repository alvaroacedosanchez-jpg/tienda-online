@extends('layouts.app')
@section('title', 'Pedidos')
@section('content')
<h1>Back-office · Pedidos</h1>
@include('partials.admin-nav')

<nav class="filters" aria-label="Filtrar por estado">
    <a href="{{ route('admin.orders.index') }}" @class(['chip', 'active' => ! $status])>Todos</a>
    @foreach (\App\Models\Order::STATUS_LABELS as $key => $label)
        <a href="{{ route('admin.orders.index', ['estado' => $key]) }}" @class(['chip', 'active' => $status === $key])>{{ $label }}</a>
    @endforeach
</nav>

<table class="table">
    <thead><tr><th>Referencia</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
    <tbody>
    @forelse ($orders as $order)
        <tr>
            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->reference }}</a></td>
            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $order->customer->name }}</td>
            <td>{{ number_format($order->total, 2, ',', '.') }} €</td>
            <td><span class="badge badge-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
        </tr>
    @empty
        <tr><td colspan="5">No hay pedidos.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $orders->links() }}
@endsection
