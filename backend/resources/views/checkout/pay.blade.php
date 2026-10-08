@extends('layouts.app')
@section('title', 'Pago simulado')
@section('content')
<h1>Pago simulado</h1>
<p>Pedido <strong>{{ $order->reference }}</strong> · Total <strong>{{ number_format($order->total, 2, ',', '.') }} €</strong></p>

<div class="alert alert-warn">
    Entorno de pruebas. No introduzcas una tarjeta real.
    Tarjeta aprobada: <code>4242 4242 4242 4242</code> · Tarjeta rechazada: <code>4000 0000 0000 0002</code> · Caducidad futura (ej. 12/30) y CVV cualquiera de 3 dígitos.
</div>
@error('payment')<div class="alert alert-error" role="alert">{{ $message }}</div>@enderror

<form method="POST" action="{{ route('orders.pay.store', $order) }}" class="card pad narrow" novalidate>
    @csrf
    <fieldset>
        <legend>Método de pago</legend>
        <label class="check"><input type="radio" name="method" value="card" @checked(old('method', 'card') === 'card')> Tarjeta (simulada)</label>
        <label class="check"><input type="radio" name="method" value="transfer" @checked(old('method') === 'transfer')> Transferencia (simulada)</label>
        @error('method')<p class="error">{{ $message }}</p>@enderror
    </fieldset>

    <div class="field">
        <label for="card_number">Número de tarjeta</label>
        <input type="text" id="card_number" name="card_number" inputmode="numeric" autocomplete="off" placeholder="4242 4242 4242 4242" value="{{ old('card_number') }}">
        @error('card_number')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label for="card_expiry">Caducidad (MM/AA)</label>
        <input type="text" id="card_expiry" name="card_expiry" autocomplete="off" placeholder="12/30" value="{{ old('card_expiry') }}">
        @error('card_expiry')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label for="card_cvv">CVV</label>
        <input type="text" id="card_cvv" name="card_cvv" inputmode="numeric" autocomplete="off" placeholder="123">
        @error('card_cvv')<p class="error">{{ $message }}</p>@enderror
    </div>

    <button class="btn" type="submit">Pagar (simulado)</button>
</form>
@endsection
