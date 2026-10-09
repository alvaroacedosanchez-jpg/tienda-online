@extends('layouts.app')
@section('title', 'Registro de usuario')
@section('content')
<h1>Registro de usuario</h1>
<p class="muted">Prototipo académico: usa datos ficticios, no tu correo ni una contraseña real.</p>
<form method="POST" action="{{ route('register.store') }}" class="card pad narrow">
    @csrf
    <div class="field">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" autofocus>
        @error('name')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}">
        @error('email')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label for="password">Contraseña</label>
    </div>
        <input type="password" id="password" name="password">
    <div class="field">
        @error('password')<p class="error">{{ $message }}</p>@enderror
        <label for="password_confirmation">Repita contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation">
    </div>
    <button class="btn" type="submit">Crear cuenta</button>
</form>
@endsection
