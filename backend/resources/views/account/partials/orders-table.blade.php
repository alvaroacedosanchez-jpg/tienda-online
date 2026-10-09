@if ($orders->isEmpty())
    <p class="muted">{{ $empty }}</p>
@else
    <table class="table">
        <thead><tr><th>Pedido</th><th>Fecha</th><th>Estado</th><th>Total</th><th>Factura</th></tr></thead>
        <tbody>
        @foreach ($orders as $order)
            <tr>
                <td><a href="{{ route('orders.show', $order) }}">{{ $order->reference }}</a></td>
                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                <td>
                    <span class="badge badge-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                    @if ($order->status === \App\Models\Order::CREATED)
                        <br><a class="small" href="{{ route('orders.pay', $order) }}">Pagar ahora</a>
                    @endif
                </td>
                <td>{{ number_format($order->total, 2, ',', '.') }} €</td>
                <td>
                    @if ($order->invoice)
                        <a href="{{ route('account.invoices.pdf', $order->invoice) }}">{{ $order->invoice->number }}</a>
                    @else
                        <span class="muted small">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
