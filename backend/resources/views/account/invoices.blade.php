@extends('layouts.app')
@section('title', 'Mis facturas')
@section('content')
<h1>Mis facturas</h1>
@include('account.partials.nav')
<p class="small muted">Se emite una factura por cada pedido pagado. Son documentos de un prototipo académico, sin validez fiscal.</p>

@if ($invoices->isEmpty())
    <p class="muted">Todavía no tienes facturas.</p>
@else
    <table class="table">
        <thead><tr><th>Factura</th><th>Fecha</th><th>Pedido</th><th>Total</th><th></th></tr></thead>
        <tbody>
        @foreach ($invoices as $invoice)
            <tr>
                <td>{{ $invoice->number }}</td>
                <td>{{ $invoice->issued_at->format('d/m/Y') }}</td>
                <td><a href="{{ route('orders.show', $invoice->order) }}">{{ $invoice->order->reference }}</a></td>
                <td>{{ number_format($invoice->total, 2, ',', '.') }} €</td>
                <td><a href="{{ route('account.invoices.pdf', $invoice) }}">Descargar PDF</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $invoices->links() }}
@endif
@endsection
