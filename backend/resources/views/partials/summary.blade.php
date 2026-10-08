<div class="card pad summary">
    <h2>Resumen</h2>
    <dl>
        <dt>Subtotal</dt><dd>{{ number_format($summary['subtotal'], 2, ',', '.') }} €</dd>
        @if ($summary['discount'] > 0)
            <dt>Descuento ({{ $summary['discount_code'] }}, −{{ $summary['discount_percent'] }} %)</dt>
            <dd>−{{ number_format($summary['discount'], 2, ',', '.') }} €</dd>
        @endif
        <dt>Envío</dt><dd>{{ $summary['shipping'] > 0 ? number_format($summary['shipping'], 2, ',', '.').' €' : 'Gratis' }}</dd>
        <dt class="total">Total</dt><dd class="total">{{ number_format($summary['total'], 2, ',', '.') }} €</dd>
        <dt class="muted small">IVA incluido ({{ (int) (config('shop.vat_rate') * 100) }} %)</dt><dd class="muted small">{{ number_format($summary['tax'], 2, ',', '.') }} €</dd>
    </dl>
</div>
