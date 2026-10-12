<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'products'])->name('products.index');
Route::get('/dashboard', fn () => redirect()->route('products.index'))->middleware('auth')->name('dashboard');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::middleware('auth')->group(function (): void {
    Route::get('/cart', [ShopController::class, 'cart'])->name('cart.index');
    Route::post('/cart', [ShopController::class, 'showCart'])->name('cart.show');
    Route::post('/cart/add', [ShopController::class, 'addToCart'])->name('cart.add');
    Route::post('/checkout', [ShopController::class, 'checkout'])->name('checkout');
    Route::get('/orders', [ShopController::class, 'orders'])->name('orders.index');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
