<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RedsysController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

// Tienda
Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalogo', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/producto/{product:slug}', [ShopController::class, 'show'])->name('product.show');

// Carrito: invitados y clientes (el administrador no compra)
Route::middleware('customer')->group(function () {
    Route::get('/carrito', [CartController::class, 'show'])->name('cart.show');
    Route::post('/carrito/anadir/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/carrito/{itemKey}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/carrito/{itemKey}', [CartController::class, 'remove'])->name('cart.remove');
});

// Checkout, pago simulado, pedido y área de cliente: solo clientes con sesión iniciada
Route::middleware(['auth', 'customer'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/codigo', [CheckoutController::class, 'applyCode'])->name('checkout.code');
    Route::get('/pedido/{order}/pagar', [CheckoutController::class, 'payForm'])->name('orders.pay');
    Route::post('/pedido/{order}/pagar', [CheckoutController::class, 'pay'])->name('orders.pay.store');
    Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Vuelta del cliente desde el TPV de Redsys (urlOk / urlKo)
    Route::get('/pagos/redsys/{payment}/ok', [RedsysController::class, 'ok'])->name('redsys.ok');
    Route::get('/pagos/redsys/{payment}/ko', [RedsysController::class, 'ko'])->name('redsys.ko');

    // Área de cliente: pedidos, facturas y datos de la cuenta
    Route::prefix('mi-cuenta')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'show'])->name('show');
        Route::get('/pedidos', [AccountController::class, 'orders'])->name('orders');
        Route::get('/facturas', [InvoiceController::class, 'index'])->name('invoices');
        Route::get('/facturas/{invoice}/pdf', [InvoiceController::class, 'download'])->name('invoices.pdf');
        Route::put('/direccion', [AccountController::class, 'updateAddress'])->name('address.update');
        Route::put('/contrasena', [AccountController::class, 'updatePassword'])->name('password.update');
        Route::delete('/', [AccountController::class, 'destroy'])->name('destroy');
    });
});

// Notificación del TPV de Redsys (la llama su servidor: sin sesión ni CSRF, se valida por firma)
Route::post('/pagos/redsys/notificacion', [RedsysController::class, 'notify'])->name('redsys.notify');

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
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/clientes', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/stock/recepcion', [AdminStockController::class, 'receiveForm'])->name('stock.receive');
        Route::post('/stock/recepcion', [AdminStockController::class, 'processReceive'])->name('stock.receive.process');
        Route::get('/pedidos/{order}/etiqueta', [Admin\OrderController::class, 'shippingLabel'])->name('orders.label');
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
