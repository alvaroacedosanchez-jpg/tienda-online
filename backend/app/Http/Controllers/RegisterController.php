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
            'password' => ['required', 'confirmed', 'min:8']
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
