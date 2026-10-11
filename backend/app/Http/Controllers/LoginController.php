<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\EventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, EventLogger $events, CartService $cart)
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

        // Guardamos el id de sesión de invitado antes de regenerarlo, para adoptar sus eventos
        $guestSessionId = $request->session()->getId();

        // Comprobamos credenciales: si son correctas, Auth::attempt inicia la sesión
        if (Auth::attempt($data)) {
            $request->session()->regenerate();

            // El administrador no compra: va a su back-office aunque viniera del checkout
            if (Auth::user()->is_admin) {
                $request->session()->forget('url.intended');

                return redirect()->route('admin.dashboard')->with('status', 'Bienvenido/a, '.Auth::user()->name.'.');
            }

            // Lo que hizo como invitado en esta visita (productos vistos, carrito...) pasa a ser suyo
            $events->adoptGuestEvents($guestSessionId, Auth::user());
            // El carrito que llenó como invitado se suma al que tenía guardado
            $cart->mergeGuestCart();

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
