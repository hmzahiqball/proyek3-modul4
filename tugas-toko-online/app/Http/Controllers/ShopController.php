<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

    public function showCart(Request $request): View
    {
        return view('shop.cart', $this->summarizeCart($this->validatedCart($request)));
    }

    public function addToCart(Request $request): JsonResponse
    {
        $cart = $this->validatedCart($request);
        $productId = (int) $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ])['product_id'];
        $product = Product::findOrFail($productId);
        $quantity = ($cart[$productId] ?? 0) + 1;
        abort_if($quantity > $product->stock, 422, 'Stok tidak mencukupi.');
        $cart[$productId] = $quantity;
        $summary = $this->summarizeCart($cart);

        return response()->json([
            'cart' => $cart,
            'items' => $summary['items']->map(fn (array $item): array => [
                'product_id' => $item['product']->id,
                'product' => $item['product']->name,
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal'],
            ])->values(),
            'total' => $summary['total'],
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate(['shipping_address' => ['required', 'string', 'max:1000']]);
        $cart = $this->validatedCart($request);
        abort_if($cart === [], 422, 'Keranjang kosong.');

        $order = DB::transaction(function () use ($cart, $data, $request): Order {
            $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            $lines = [];
            foreach ($cart as $productId => $quantity) {
                $product = $products->get((int) $productId);
                abort_if(! $product || $quantity > $product->stock, 422, 'Stok tidak mencukupi.');
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

        return redirect()->route('orders.index')->with('status', "Pesanan #{$order->id} berhasil dibuat.");
    }

    public function orders(Request $request): View
    {
        return view('shop.orders', ['orders' => Order::with('items.product')->where('user_id', $request->user()->id)->latest()->get()]);
    }

    /**
     * @return array{items: \Illuminate\Support\Collection, total: int, cart: array<int, int>}
     */
    private function summarizeCart(array $cart): array
    {
        $products = Product::query()
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $items = collect($cart)->map(function (int $quantity, int|string $id) use ($products): array {
            $product = $products->get((int) $id);
            abort_if(! $product, 422, 'Produk tidak ditemukan.');
            abort_if($quantity > $product->stock, 422, "Stok {$product->name} tidak mencukupi.");

            return [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $product->price * $quantity,
            ];
        });

        return ['items' => $items, 'total' => $items->sum('subtotal'), 'cart' => $cart];
    }

    /**
     * @return array<int, int>
     */
    private function validatedCart(Request $request): array
    {
        $payload = $request->input('cart');
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $validated = Validator::make(['cart' => $payload], [
            'cart' => ['nullable', 'array'],
            'cart.*' => ['required', 'integer', 'min:1'],
        ])->validate();

        return collect($validated['cart'] ?? [])
            ->mapWithKeys(fn (int|string $quantity, int|string $productId): array => [(int) $productId => (int) $quantity])
            ->all();
    }
}
