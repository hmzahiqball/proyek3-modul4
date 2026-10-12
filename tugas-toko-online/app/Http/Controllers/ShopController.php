<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function products(): View
    {
        return view('shop.products', ['products' => Product::query()->orderBy('id')->get()]);
    }

    public function cart(): View
    {
        return view('shop.cart', $this->summarizeCart([]));
    }

    public function storeCart(Request $request): View
    {
        $cart = $this->validatedCart($request);

        return view('shop.cart', $this->summarizeCart($cart));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate(['shipping_address' => ['required', 'string', 'max:1000']]);
        $cart = $this->validatedCart($request);
        abort_if($cart === [], 422, 'Keranjang kosong.');

        $order = DB::transaction(function () use ($cart, $data, $request): Order {
            $products = Product::whereIn('id', array_column($cart, 'product_id'))->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            $lines = [];
            foreach ($cart as $line) {
                $product = $products->get($line['product_id']);
                abort_if(! $product || $line['quantity'] > $product->stock, 422, 'Stok tidak mencukupi.');
                $total += $product->price * $line['quantity'];
                $lines[] = ['product' => $product, 'quantity' => $line['quantity']];
            }
            $order = Order::create(['user_id' => $request->user()->id, 'total' => $total, 'shipping_address' => $data['shipping_address']]);
            foreach ($lines as $line) {
                $line['product']->decrement('stock', $line['quantity']);
                $order->items()->create(['product_id' => $line['product']->id, 'unit_price' => $line['product']->price, 'quantity' => $line['quantity']]);
            }

            return $order;
        });

        return redirect()->route('orders.index')->with('status', "Pesanan #{$order->id} berhasil dibuat.");
    }

    public function orders(Request $request): View
    {
        return view('shop.orders', ['orders' => Order::with('items.product')->where('user_id', $request->user()->id)->latest()->get()]);
    }

    /**
     * @return array{items: \Illuminate\Support\Collection, total: int, cart: array<int, array{product_id: int, quantity: int}>}
     */
    private function summarizeCart(array $cart): array
    {
        $products = Product::query()
            ->whereIn('id', array_column($cart, 'product_id'))
            ->get()
            ->keyBy('id');

        $items = collect($cart)->map(function (array $line) use ($products): array {
            $product = $products->get($line['product_id']);
            abort_if(! $product, 422, 'Produk tidak ditemukan.');
            abort_if($line['quantity'] > $product->stock, 422, "Stok {$product->name} tidak mencukupi.");

            return [
                'product' => $product,
                'quantity' => $line['quantity'],
                'subtotal' => $product->price * $line['quantity'],
            ];
        });

        return ['items' => $items, 'total' => $items->sum('subtotal'), 'cart' => $cart];
    }

    /**
     * @return array<int, array{product_id: int, quantity: int}>
     */
    private function validatedCart(Request $request): array
    {
        $payload = $request->input('cart');
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $validated = Validator::make(['cart' => $payload], [
            'cart' => ['required', 'array'],
            'cart.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'cart.*.quantity' => ['required', 'integer', 'min:1'],
        ])->validate();

        return array_map(
            fn (array $line): array => [
                'product_id' => (int) $line['product_id'],
                'quantity' => (int) $line['quantity'],
            ],
            $validated['cart'],
        );
    }
}
