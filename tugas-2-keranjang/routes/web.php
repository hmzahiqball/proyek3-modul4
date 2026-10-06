<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('products.index'));
Route::get('/products', [CartController::class, 'index'])->name('products.index');
Route::get('/cart', [CartController::class, 'cart'])->name('cart.index');
Route::post('/cart/products/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/products/{product}/{direction}', [CartController::class, 'change'])->name('cart.change');
Route::delete('/cart/products/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
