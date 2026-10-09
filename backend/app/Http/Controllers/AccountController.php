<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Área de cliente ("Mi cuenta"): resumen, pedidos, dirección por defecto, contraseña y baja.
 * Todas las rutas están protegidas por el middleware auth.
 */
class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('account.show', [
            'user' => $user,
            'customer' => $user->customer,
            'inProgress' => $user->orders()->whereIn('status', Order::IN_PROGRESS)->with('invoice')->latest()->get(),
        ]);
    }

    public function orders(Request $request)
    {
        $user = $request->user();

        return view('account.orders', [
            'inProgress' => $user->orders()->whereIn('status', Order::IN_PROGRESS)->with('invoice')->latest()->get(),
            'past' => $user->orders()->whereNotIn('status', Order::IN_PROGRESS)->with('invoice')->latest()->paginate(10),
        ]);
    }

    /** Dirección por defecto: la que rellena el checkout. No cambia los pedidos ya hechos. */
    public function updateAddress(Request $request)
    {
        $data = $request->validateWithBag('address', [
            'phone' => ['nullable', 'regex:/^[0-9 +]{9,15}$/'],
            'address' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'digits:5'],
        ], [
            'address.required' => 'Indica la dirección.',
            'city.required' => 'Indica la ciudad.',
            'postal_code.required' => 'Indica el código postal.',
            'postal_code.digits' => 'El código postal debe tener 5 dígitos.',
            'phone.regex' => 'El teléfono no tiene un formato válido.',
        ]);

        $user = $request->user();

        // Si aún no ha comprado, se crea su ficha de cliente con los datos de la cuenta
        Customer::updateOrCreate(['user_id' => $user->id], $data + [
            'name' => $user->customer->name ?? $user->name,
            'email' => $user->email,
        ]);

        return redirect()->route('account.show')->with('status', 'Dirección por defecto actualizada.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8', 'different:current_password'],
        ], [
            'current_password.required' => 'Indica tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Indica la nueva contraseña.',
            'password.confirmed' => 'Las contraseñas nuevas no coinciden.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ]);

        $request->user()->update(['password' => $data['password']]); // el cast 'hashed' la cifra
        $request->session()->regenerate();

        return redirect()->route('account.show')->with('status', 'Contraseña actualizada.');
    }

    /** Baja de la cuenta (soft delete): se conservan sus pedidos y facturas. */
    public function destroy(Request $request)
    {
        $request->validateWithBag('delete', [
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.required' => 'Indica tu contraseña para confirmar la baja.',
            'current_password.current_password' => 'La contraseña no es correcta.',
        ]);

        $user = $request->user();

        // Regla de negocio: no se puede dar de baja con pedidos sin terminar
        if ($user->orders()->whereIn('status', Order::IN_PROGRESS)->exists()) {
            return redirect()->route('account.show')->withErrors([
                'current_password' => 'Tienes pedidos en curso; podrás darte de baja cuando se completen.',
            ], 'delete');
        }

        $user->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Tu cuenta se ha dado de baja. Tus pedidos y facturas se conservan.');
    }
}
