<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        // Validamos el formato de los datos
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Indica tu correo electrónico.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'Indica tu contraseña.',
        ]);

        // Comprobamos credenciales: si son correctas, Auth::attempt inicia la sesión
        if (Auth::attempt($data)) {
            $request->session()->regenerate();

            // El administrador no compra: va a su back-office aunque viniera del checkout
            if (Auth::user()->is_admin) {
                $request->session()->forget('url.intended');

                return redirect()->route('admin.orders.index');
            }

            return redirect()->intended(route('home'))->with('status', 'Bienvenido/a, '.Auth::user()->name.'.');
        }

        // Mensaje genérico para no revelar si el correo existe
        return back()
            ->withErrors(['email' => 'El correo o la contraseña no son correctos.'])
            ->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
