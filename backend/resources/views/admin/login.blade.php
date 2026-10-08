@extends('layouts.app')
@section('title', 'Acceso back-office')
@section('content')
<h1>Acceso al back-office</h1>
<p class="muted">Cuenta de prueba indicada en el README.</p>
<form method="POST" action="{{ route('admin.login.store') }}" class="card pad narrow">
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
@endsection
