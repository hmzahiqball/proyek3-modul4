<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        return view('products.index', ['products' => Product::query()->orderBy('id')->get()]);
    }

    public function cart(Request $request): View
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $items = collect($cart)->map(function (int $quantity, string $id) use ($products) {
            $product = $products->get((int) $id);

            return $product ? ['product' => $product, 'quantity' => $quantity, 'subtotal' => $product->price * $quantity] : null;
        })->filter();

        return view('cart.index', ['items' => $items, 'total' => $items->sum('subtotal')]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_if($product->stock < 1, 422, 'Product is out of stock.');
        $cart = $request->session()->get('cart', []);
        $quantity = min(($cart[$product->id] ?? 0) + 1, $product->stock);
        $cart[$product->id] = $quantity;
        $request->session()->put('cart', $cart);

        return back()->with('status', 'Produk ditambahkan ke keranjang.');
    }

    public function change(Request $request, Product $product, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['increase', 'decrease'], true), 404);
        $cart = $request->session()->get('cart', []);
        $quantity = (int) ($cart[$product->id] ?? 0);
        $quantity += $direction === 'increase' ? 1 : -1;

        if ($quantity < 1) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = min($quantity, $product->stock);
        }
        $request->session()->put('cart', $cart);

        return back();
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget('cart');

        return back();
    }
}
