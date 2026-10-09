<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

// Tienda
Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalogo', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/producto/{product:slug}', [ShopController::class, 'show'])->name('product.show');

// Carrito
Route::get('/carrito', [CartController::class, 'show'])->name('cart.show');
Route::post('/carrito/anadir/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/carrito/codigo', [CartController::class, 'applyCode'])->name('cart.code');

// Checkout, pago simulado y pedido: solo con sesión iniciada (todo cliente es un usuario)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/pedido/{order}/pagar', [CheckoutController::class, 'payForm'])->name('orders.pay');
    Route::post('/pedido/{order}/pagar', [CheckoutController::class, 'pay'])->name('orders.pay.store');
    Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('orders.show');
});

// Soporte
Route::get('/soporte', [SupportController::class, 'create'])->name('support.create');
Route::post('/soporte', [SupportController::class, 'store'])->name('support.store');

// Cuentas de usuarios
// Registro y login solo para visitantes sin sesión; logout solo para usuarios con sesión
Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegisterController::class, 'create'])->name('register.create');
    Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Back-office
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');

    Route::middleware('admin')->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/pedidos', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/pedidos/{order}/estado', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('/eventos', [Admin\EventController::class, 'index'])->name('events.index');
        Route::get('/eventos.json', [Admin\EventController::class, 'json'])->name('events.json');
        Route::get('/eventos.csv', [Admin\EventController::class, 'csv'])->name('events.csv');
        Route::get('/incidencias', [Admin\TicketController::class, 'index'])->name('tickets.index');
    });
});
