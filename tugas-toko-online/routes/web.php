<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'products'])->name('products.index');
Route::get('/dashboard', fn () => redirect()->route('products.index'))->middleware('auth')->name('dashboard');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::middleware('auth')->group(function (): void {
    Route::get('/cart', [ShopController::class, 'cart'])->middleware('can:use-cart')->name('cart.index');
    Route::post('/cart', [ShopController::class, 'showCart'])->middleware('can:use-cart')->name('cart.show');
    Route::post('/cart/add', [ShopController::class, 'addToCart'])->middleware('can:use-cart')->name('cart.add');
    Route::post('/checkout', [ShopController::class, 'checkout'])->middleware('can:use-cart')->name('checkout');
    Route::get('/orders', [ShopController::class, 'orders'])->name('orders.index');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/admin/users', [RoleController::class, 'index'])
    ->middleware(['auth', 'can:manage-roles'])->name('admin.users.index');
Route::patch('/admin/users/{user}/role', [RoleController::class, 'update'])
    ->middleware(['auth', 'can:manage-roles'])->name('admin.users.role.update');

Route::get('/stock', [ProductController::class, 'stock'])
    ->middleware(['auth', 'can:adjust-stock'])->name('stock.index');
Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])
    ->middleware(['auth', 'can:manage-products'])->name('admin.products.edit');
Route::patch('/admin/products/{product}', [ProductController::class, 'update'])
    ->middleware(['auth', 'can:manage-products'])->name('admin.products.update');
