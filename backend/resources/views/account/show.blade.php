@extends('layouts.app')
@section('title', 'Mi cuenta')
@section('content')
<h1>Mi cuenta</h1>
@include('account.partials.nav')

<div class="card pad">
    <h2>Pedidos en curso</h2>
    @include('account.partials.orders-table', ['orders' => $inProgress, 'empty' => 'No tienes pedidos en curso.'])
    <p class="small"><a href="{{ route('account.orders') }}">Ver todos mis pedidos</a></p>
</div>

<div class="two-col">
    <div class="card pad">
        <h2>Mis datos</h2>
        <p>{{ $user->name }}<br><span class="muted">{{ $user->email }}</span></p>

        <h3>Dirección por defecto</h3>
        <p class="small muted">Se usa para rellenar el checkout. Cambiarla no modifica los pedidos ya hechos.</p>
        <form method="POST" action="{{ route('account.address.update') }}" novalidate>
            @csrf
            @method('PUT')
            @foreach ([
                'phone' => ['Teléfono (opcional)', 'tel'],
                'address' => ['Dirección', 'text'],
                'city' => ['Ciudad', 'text'],
                'postal_code' => ['Código postal', 'text'],
            ] as $field => [$label, $type])
                <div class="field">
                    <label for="address-{{ $field }}">{{ $label }}</label>
                    <input type="{{ $type }}" id="address-{{ $field }}" name="{{ $field }}" value="{{ old($field, $customer?->$field) }}">
                    @error($field, 'address')<p class="error">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <button class="btn" type="submit">Guardar dirección</button>
        </form>
    </div>

    <div>
        <div class="card pad">
            <h2>Cambiar contraseña</h2>
            <form method="POST" action="{{ route('account.password.update') }}">
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="current_password">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                    @error('current_password', 'password')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" autocomplete="new-password">
                    @error('password', 'password')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Repite la nueva contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                </div>
                <button class="btn" type="submit">Cambiar contraseña</button>
            </form>
        </div>

        <div class="card pad mt">
            <h2>Darme de baja</h2>
            <p class="small muted">Tu cuenta quedará desactivada y no podrás iniciar sesión. Tus pedidos y facturas se conservan. No es posible si tienes pedidos en curso.</p>
            <form method="POST" action="{{ route('account.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="field">
                    <label for="delete_password">Confirma con tu contraseña</label>
                    <input type="password" id="delete_password" name="current_password" autocomplete="current-password">
                    @error('current_password', 'delete')<p class="error">{{ $message }}</p>@enderror
                </div>
                <button class="btn btn-light" type="submit">Darme de baja</button>
            </form>
        </div>
    </div>
</div>
@endsection
