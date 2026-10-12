<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Keranjang</title>
</head>
<body style="font-family:Arial;background:#f3f4f6;padding:2rem">
<main style="max-width:55rem;margin:auto">
    <a href="{{ route('products.index') }}">← Produk</a>
    <h1>Keranjang Belanja</h1>
    <p>State cart dikirim dari browser melalui Request Payload dan tidak disimpan pada session/cookie.</p>
    @forelse($items as $item)
        <article style="background:#fff;padding:1rem;margin:.75rem 0;border-radius:.5rem;display:flex;justify-content:space-between;gap:1rem">
            <div>
                <strong>{{ $item['product']->name }}</strong><br>
                Rp {{ number_format($item['product']->price, 0, ',', '.') }} × {{ $item['quantity'] }}
                = <strong>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
            </div>
            <div>
                <button type="button" data-action="decrease" data-product-id="{{ $item['product']->id }}">-</button>
                <span>{{ $item['quantity'] }}</span>
                <button type="button" data-action="increase" data-product-id="{{ $item['product']->id }}">+</button>
                <button type="button" data-action="remove" data-product-id="{{ $item['product']->id }}">Hapus</button>
            </div>
        </article>
    @empty
        <p>Keranjang kosong. Refresh halaman ini untuk melihat bahwa state tidak dipersistenkan.</p>
    @endforelse
    @if($items->isNotEmpty())
        <h2>Total: Rp {{ number_format($total, 0, ',', '.') }}</h2>
        <button type="button" data-action="clear">Kosongkan keranjang</button>
        <hr>
        <form method="POST" action="{{ route('checkout') }}" id="checkout-form">
            @csrf
            <input type="hidden" name="cart" value="{{ json_encode($cart) }}">
            <label>Alamat pengiriman<br><textarea name="shipping_address" required rows="3" cols="50">{{ old('shipping_address') }}</textarea></label><br>
            <button>Checkout</button>
        </form>
    @endif
</main>
<script>
    window.cartState = @json($cart);
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    async function submitCart() {
        const response = await fetch(@json(route('cart.show')), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'text/html', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify({cart: window.cartState})
        });
        if (!response.ok) {
            throw new Error('Server menolak payload cart.');
        }
        document.open();
        document.write(await response.text());
        document.close();
    }

    document.querySelectorAll('[data-action]').forEach((button) => {
        button.addEventListener('click', async () => {
            const action = button.dataset.action;
            if (action === 'clear') {
                window.cartState = {};
            } else {
                const productId = Number(button.dataset.productId);
                const quantity = window.cartState[productId] || 0;
                if (action === 'increase') window.cartState[productId] = quantity + 1;
                if (action === 'decrease') window.cartState[productId] = quantity - 1;
                if (action === 'remove' || window.cartState[productId] < 1) {
                    delete window.cartState[productId];
                }
            }
            await submitCart();
        });
    });

    document.querySelector('#checkout-form')?.addEventListener('submit', (event) => {
        event.currentTarget.elements.cart.value = JSON.stringify(window.cartState);
    });
</script>
</body>
</html>
