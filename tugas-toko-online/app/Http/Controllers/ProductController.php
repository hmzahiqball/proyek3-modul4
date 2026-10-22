<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function stock(): View
    {
        return view('shop.stock', ['products' => Product::orderBy('id')->get()]);
    }

    public function edit(Product $product): View
    {
        return view('admin.product', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
        ]));

        return redirect()->route('products.index')->with('status', 'Produk diperbarui.');
    }
}
