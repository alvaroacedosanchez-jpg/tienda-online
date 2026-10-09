<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $invoice->number }}</title>
    {{-- dompdf solo admite CSS básico: estilos en línea y tablas para maquetar --}}
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1d2433; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #667085; }
        .box { width: 100%; margin-bottom: 18px; }
        .box td { vertical-align: top; width: 50%; }
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.lines th { background: #f2f4f7; text-align: left; padding: 6px; font-size: 10px; text-transform: uppercase; }
        table.lines td { padding: 6px; border-bottom: 1px solid #e4e7ec; }
        .num, table.lines th.num { text-align: right; }
        table.totals { width: 45%; margin-left: 55%; border-collapse: collapse; }
        table.totals td { padding: 4px 6px; }
        table.totals .total td { font-weight: bold; font-size: 13px; border-top: 2px solid #1d2433; }
        .notice { margin-top: 28px; padding: 8px; border: 1px solid #fedf89; background: #fffaeb; color: #b54708; font-size: 10px; }
    </style>
</head>
<body>
    <table class="box">
        <tr>
            <td>
                <h1>Factura</h1>
                <strong>{{ $invoice->number }}</strong><br>
                <span class="muted">Fecha de emisión: {{ $invoice->issued_at->format('d/m/Y') }}</span><br>
                <span class="muted">Pedido: {{ $invoice->order->reference }}</span>
            </td>
            <td class="num">
                <strong>{{ $company['legal_name'] }}</strong><br>
                {{ $company['tax_id'] }}<br>
                {{ $company['address'] }}<br>
                {{ $company['email'] }}
            </td>
        </tr>
    </table>

    <table class="box">
        <tr>
            <td>
                <span class="muted">Facturar a</span><br>
                <strong>{{ $invoice->billing_name }}</strong><br>
                {{ $invoice->billing_address }}<br>
                {{ $invoice->billing_postal_code }} {{ $invoice->billing_city }}<br>
                {{ $invoice->billing_email }}
            </td>
            <td>
                <span class="muted">Enviado a</span><br>
                {{ $invoice->order->shipping_name }}<br>
                {{ $invoice->order->shipping_address }}<br>
                {{ $invoice->order->shipping_postal_code }} {{ $invoice->order->shipping_city }}
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr><th>Producto</th><th class="num">Uds.</th><th class="num">Precio</th><th class="num">Importe</th></tr>
        </thead>
        <tbody>
            @foreach ($invoice->order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}@if ($item->variant_size) ({{ $item->variant_size }})@endif</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td class="num">{{ number_format($item->line_total, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td></tr>
        @if ($invoice->discount > 0)
            <tr><td>Descuento ({{ $invoice->order->discount_code }})</td><td class="num">−{{ number_format($invoice->discount, 2, ',', '.') }} €</td></tr>
        @endif
        <tr><td>Envío</td><td class="num">{{ $invoice->shipping > 0 ? number_format($invoice->shipping, 2, ',', '.').' €' : 'Gratis' }}</td></tr>
        <tr><td>Base imponible</td><td class="num">{{ number_format($invoice->total - $invoice->tax, 2, ',', '.') }} €</td></tr>
        <tr><td>IVA ({{ (int) (config('shop.vat_rate') * 100) }} %)</td><td class="num">{{ number_format($invoice->tax, 2, ',', '.') }} €</td></tr>
        <tr class="total"><td>Total</td><td class="num">{{ number_format($invoice->total, 2, ',', '.') }} €</td></tr>
    </table>

    <p class="notice">
        Documento sin validez fiscal: prototipo académico (Soluciones Informáticas para la Empresa).
        Empresa, datos y pago simulados. No se ha realizado ningún cobro ni envío real.
    </p>
</body>
</html>
