<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StockController as AdminStockController;

// Tienda
Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalogo', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/producto/{product:slug}', [ShopController::class, 'show'])->name('product.show');

// Carrito
Route::get('/carrito', [CartController::class, 'show'])->name('cart.show');
Route::post('/carrito/anadir/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/{itemKey}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{itemKey}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/carrito/codigo', [CartController::class, 'applyCode'])->name('cart.code');

// Checkout, pago simulado y pedido
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/pedido/{order}/pagar', [CheckoutController::class, 'payForm'])->name('orders.pay');
Route::post('/pedido/{order}/pagar', [CheckoutController::class, 'pay'])->name('orders.pay.store');
Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('orders.show');

// Soporte
Route::get('/soporte', [SupportController::class, 'create'])->name('support.create');
Route::post('/soporte', [SupportController::class, 'store'])->name('support.store');

// Back-office
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');

    Route::middleware('admin')->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/pedidos', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/pedidos/{order}/estado', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('/stock', [AdminStockController::class, 'index'])->name('stock.index');
        Route::get('/eventos', [Admin\EventController::class, 'index'])->name('events.index');
        Route::get('/eventos.json', [Admin\EventController::class, 'json'])->name('events.json');
        Route::get('/eventos.csv', [Admin\EventController::class, 'csv'])->name('events.csv');
        Route::get('/incidencias', [Admin\TicketController::class, 'index'])->name('tickets.index');
    });
});