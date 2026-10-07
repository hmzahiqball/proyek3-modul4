<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function products(): View
    {
        return view('shop.products', ['products' => Product::query()->orderBy('id')->get()]);
    }

    public function cart(Request $request): View
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $items = collect($cart)->map(function (int $quantity, string $id) use ($products) {
            $product = $products->get((int) $id);

            return $product ? ['product' => $product, 'quantity' => $quantity, 'subtotal' => $product->price * $quantity] : null;
        })->filter();

        return view('shop.cart', ['items' => $items, 'total' => $items->sum('subtotal')]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_if($product->stock < 1, 422);
        $cart = $request->session()->get('cart', []);
        $cart[$product->id] = min(($cart[$product->id] ?? 0) + 1, $product->stock);
        $request->session()->put('cart', $cart);

        return back();
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

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate(['shipping_address' => ['required', 'string', 'max:1000']]);
        $cart = $request->session()->get('cart', []);
        abort_if($cart === [], 422, 'Keranjang kosong.');

        $order = DB::transaction(function () use ($cart, $data, $request): Order {
            $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            $lines = [];
            foreach ($cart as $id => $quantity) {
                $product = $products->get((int) $id);
                abort_if(! $product || $quantity < 1 || $quantity > $product->stock, 422, 'Stok tidak mencukupi.');
                $total += $product->price * $quantity;
                $lines[] = ['product' => $product, 'quantity' => $quantity];
            }
            $order = Order::create(['user_id' => $request->user()->id, 'total' => $total, 'shipping_address' => $data['shipping_address']]);
            foreach ($lines as $line) {
                $line['product']->decrement('stock', $line['quantity']);
                $order->items()->create(['product_id' => $line['product']->id, 'unit_price' => $line['product']->price, 'quantity' => $line['quantity']]);
            }

            return $order;
        });
        $request->session()->forget('cart');

        return redirect()->route('orders.index')->with('status', "Pesanan #{$order->id} berhasil dibuat.");
    }

    public function orders(Request $request): View
    {
        return view('shop.orders', ['orders' => Order::with('items.product')->where('user_id', $request->user()->id)->latest()->get()]);
    }
}
