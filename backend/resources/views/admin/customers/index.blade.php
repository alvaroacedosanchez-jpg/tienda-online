@extends('layouts.app')
@section('title', 'Listado de Clientes')

@section('content')
<h1>Listado de Clientes Registrados</h1>
@include('partials.admin-nav')

<div class="card pad">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Población</th>
                <th>Pedidos</th>
                <th>Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($customers as $customer)
            <tr>
                <td><strong>#{{ $customer->id }}</strong></td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->city ?? '-' }}</td>
                <td>
                    <span class="badge badge-completed">{{ $customer->orders_count }} {{ $customer->orders_count === 1 ? 'pedido' : 'pedidos' }}</span>
                </td>
                <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y') : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No hay clientes registrados en el sistema.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @if (method_exists($customers, 'links'))
        <div style="margin-top: 1rem;">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection