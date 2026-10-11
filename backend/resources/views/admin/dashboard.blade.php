@extends('layouts.app')
@section('title', 'Dashboard Principal')

@section('content')
<h1>Panel de Control</h1>
@include('partials.admin-nav')

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card pad">
        <span class="muted" style="font-size: 0.875rem;">Facturación Total</span>
        <h2 style="margin: 0.5rem 0 0 0; color: #2e7d32;">{{ number_format($totalRevenue, 2, ',', '.') }} €</h2>
    </div>

    <div class="card pad">
        <span class="muted" style="font-size: 0.875rem;">Pedidos Totales</span>
        <h2 style="margin: 0.5rem 0 0 0;">{{ $totalOrders }}</h2>
    </div>

    <div class="card pad">
        <span class="muted" style="font-size: 0.875rem;">Pedidos Pendientes</span>
        <h2 style="margin: 0.5rem 0 0 0; color: #d97706;">{{ $pendingOrders }}</h2>
    </div>

    <div class="card pad">
        <span class="muted" style="font-size: 0.875rem;">Clientes Registrados</span>
        <h2 style="margin: 0.5rem 0 0 0;">{{ $totalCustomers }}</h2>
    </div>

    <div class="card pad">
        <span class="muted" style="font-size: 0.875rem;">Alertas de Stock</span>
        <h2 style="margin: 0.5rem 0 0 0; color: #dc2626;">{{ $totalLowStockAlerts }}</h2>
    </div>
</div>

<div class="card pad">
    <h3 style="margin-top: 0;">Últimos Pedidos Recibidos</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Referencia</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($recentOrders as $order)
            <tr>
                <td><strong>{{ $order->reference }}</strong></td>
                <td>{{ $order->customer->name ?? $order->shipping_name }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    @if ($order->status === 'completed')
                        <span class="badge badge-completed">Completado</span>
                    @elseif ($order->status === 'pending')
                        <span class="badge badge-pending">Pendiente</span>
                    @elseif ($order->status === 'processing')
                        <span class="badge badge-pending">En proceso</span>
                    @else
                        <span class="badge badge-cancelled">{{ $order->status }}</span>
                    @endif
                </td>
                <td><strong>{{ number_format($order->total, 2, ',', '.') }} €</strong></td>
                <td>
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-small">Ver detalle</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No hay pedidos registrados.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection