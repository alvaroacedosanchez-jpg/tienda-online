@extends('layouts.app')
@section('title', 'Pago')
@section('content')
<h1>Pago</h1>
<p>Pedido <strong>{{ $order->reference }}</strong> · Total <strong>{{ number_format($order->total, 2, ',', '.') }} €</strong></p>

<div class="alert alert-warn">
    Prototipo académico. El pago con tarjeta se hace en el <strong>TPV Virtual de Redsys en su entorno de pruebas</strong>:
    no se cobra nada y no debes usar una tarjeta real. Usa una de las
    <a href="https://pagosonline.redsys.es/desarrolladores-inicio/integrate-con-nosotros/tarjetas-y-entornos-de-prueba/" target="_blank" rel="noopener">tarjetas de prueba de Redsys</a>.
</div>
@error('payment')<div class="alert alert-error" role="alert">{{ $message }}</div>@enderror

<form method="POST" action="{{ route('orders.pay.store', $order) }}" class="card pad narrow">
    @csrf
    <fieldset>
        <legend>Método de pago</legend>
        <label class="check"><input type="radio" name="method" value="redsys" @checked(old('method', 'redsys') === 'redsys')> Tarjeta (TPV Virtual Redsys, entorno de pruebas)</label>
        <label class="check"><input type="radio" name="method" value="transfer" @checked(old('method') === 'transfer')> Transferencia (simulada)</label>
        @error('method')<p class="error">{{ $message }}</p>@enderror
    </fieldset>
    <p class="small muted">Con tarjeta irás a la página segura de Redsys; los datos de la tarjeta nunca pasan por esta tienda.</p>

    <button class="btn" type="submit">Continuar con el pago</button>
</form>
@endsection
