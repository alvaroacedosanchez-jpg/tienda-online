@extends('layouts.app')
@section('title', 'Soporte')
@section('content')
<h1>Soporte e incidencias</h1>
<p class="muted">Formulario de contacto postventa (simulado). Usa datos ficticios.</p>

<form method="POST" action="{{ route('support.store') }}" class="card pad narrow" novalidate>
    @csrf
    @foreach ([
        'name' => ['Nombre', 'text'],
        'email' => ['Correo electrónico', 'email'],
        'order_reference' => ['Referencia del pedido (opcional)', 'text'],
        'subject' => ['Asunto', 'text'],
    ] as $field => [$label, $type])
        <div class="field">
            <label for="{{ $field }}">{{ $label }}</label>
            <input type="{{ $type }}" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $field === 'order_reference' ? $reference : '') }}">
            @error($field)<p class="error">{{ $message }}</p>@enderror
        </div>
    @endforeach
    <div class="field">
        <label for="message">Mensaje</label>
        <textarea id="message" name="message" rows="5">{{ old('message') }}</textarea>
        @error('message')<p class="error">{{ $message }}</p>@enderror
    </div>
    <button class="btn" type="submit">Enviar solicitud</button>
</form>
@endsection
