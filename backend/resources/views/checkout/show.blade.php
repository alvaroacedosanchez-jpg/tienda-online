@extends('layouts.app')
@section('title', 'Checkout')
@section('content')
<h1>Checkout</h1>
<p class="alert alert-warn">Usa datos ficticios. No escribas tu nombre, correo ni dirección reales.</p>

<div class="two-col">
    <form method="POST" action="{{ route('checkout.store') }}" class="card pad" novalidate>
        @csrf
        <h2>Datos de envío</h2>
        <p class="small muted">Te enviaremos la confirmación a {{ $user->email }}. Esta dirección se guarda solo para este pedido.</p>

        @foreach ([
            'name' => ['Nombre de quien recibe', 'text', 'Laura Prueba'],
            'phone' => ['Teléfono (opcional)', 'tel', '600000000'],
            'address' => ['Dirección', 'text', 'Calle Ficticia 1'],
            'city' => ['Ciudad', 'text', 'Madrid'],
            'postal_code' => ['Código postal', 'text', '28001'],
        ] as $field => [$label, $type, $placeholder])
            <div class="field">
                <label for="{{ $field }}">{{ $label }}</label>
                {{-- Valor: lo último escrito (si hubo error) → la ficha del cliente → el nombre de la cuenta --}}
                <input type="{{ $type }}" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $customer?->$field ?? ($field === 'name' ? $user->name : '')) }}" placeholder="{{ $placeholder }}">
                @error($field)<p class="error">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <div class="field">
            <label class="check">
                <input type="checkbox" name="accept_prototype" value="1" @checked(old('accept_prototype'))>
                Entiendo que es un prototipo académico sin compra real.
            </label>
            @error('accept_prototype')<p class="error">{{ $message }}</p>@enderror
        </div>

        <button class="btn" type="submit">Crear pedido y pasar al pago</button>
    </form>

    <div>
        <div class="card pad">
            <h2>Tu pedido</h2>
            <ul class="plain">
                @foreach ($items as $item)
                    <li>{{ $item['quantity'] }} × {{ $item['product']->name }} <span class="right">{{ number_format($item['line_total'], 2, ',', '.') }} €</span></li>
                @endforeach
            </ul>
        </div>
        @include('partials.summary', ['summary' => $summary])
    </div>
</div>
@endsection
