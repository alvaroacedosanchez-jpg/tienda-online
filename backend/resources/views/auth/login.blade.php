@extends('layouts.app')
@section('title', 'Iniciar sesión de usuario')
@section('content')
<h1>Inicio de sesión</h1>
<p class="muted">Prototipo académico: usa datos ficticios, no tu correo ni una contraseña real.</p>
<form method="POST" action="{{ route('login.store') }}" class="card pad narrow">
    @csrf
    <div class="field">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autofocus>
        @error('email')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password">
    </div>
    <button class="btn" type="submit">Entrar</button>
</form>
<p>¿No tienes cuenta?<a href="{{ route('register.create') }}">Crea una</a></p>
@endsection
