@extends('layouts.app')
@section('title', 'Redirigiendo al TPV')
@section('content')
<h1>Redirigiendo al TPV Virtual de Redsys…</h1>
<p>Pedido <strong>{{ $order->reference }}</strong> · Total <strong>{{ number_format($order->total, 2, ',', '.') }} €</strong></p>
<p class="muted">Vas a la página de pago del <strong>entorno de pruebas</strong> de Redsys. Usa una tarjeta de pruebas, nunca una real.</p>

{{-- Formulario firmado con la clave del comercio (Ds_MerchantParameters + Ds_Signature) --}}
<form id="redsys-form" method="POST" action="{{ $url }}" class="card pad narrow">
    @foreach ($fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <button class="btn" type="submit">Continuar al TPV de Redsys</button>
</form>

@push('scripts')
<script>
    // Envío automático; si no hay JavaScript, el cliente pulsa el botón
    document.getElementById('redsys-form').submit();
</script>
@endpush
@endsection
