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
    Route::post('/cart/products/{product}', [ShopController::class, 'add'])->name('cart.add');
    Route::post('/cart/products/{product}/{direction}', [ShopController::class, 'change'])->name('cart.change');
    Route::delete('/cart/products/{product}', [ShopController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [ShopController::class, 'clear'])->name('cart.clear');
    Route::post('/checkout', [ShopController::class, 'checkout'])->name('checkout');
    Route::get('/orders', [ShopController::class, 'orders'])->name('orders.index');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
