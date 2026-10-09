<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        // Validamos los datos
        $data = $request->validate([
            'name' => ['required', 'string' ,'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'name.required' => 'Indica tu nombre.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'email.required' => 'Indica tu correo electrónico.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede superar los 150 caracteres.',
            'email.unique' => 'Ya existe una cuenta con este correo electrónico.',
            'password.required' => 'Indica una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        // Creamos el usuario
        $user = User::create($data);

        // Iniciamos sesion
        Auth::login($user);
        $request->session()->regenerate();
        
        // Redirigimos
        return redirect()->route('home')->with('status', 'Bienvenido/a, '. $user->name .'. Tu cuenta ha sido creada');

    }
}
