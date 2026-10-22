<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Toko Online</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; color: #1f2937; }
        main { max-width: 70rem; margin: auto; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        nav { display: flex; align-items: center; gap: .8rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1rem; }
        .card, #cart-summary { background: #fff; padding: 1rem; border-radius: .6rem; box-shadow: 0 2px 8px #0001; }
        .card img { display: block; width: 100%; border-radius: .4rem; }
        button { padding: .6rem .8rem; background: #2563eb; color: #fff; border: 0; border-radius: .3rem; cursor: pointer; }
        button:disabled { background: #9ca3af; cursor: not-allowed; }
        .muted { color: #6b7280; }
        #cart-summary { margin: 1.5rem 0; }
    </style>
</head>
<body>
<main>
    <div class="top">
        <h1>Toko Online</h1>
        <nav>
            @auth
                @can('use-cart')
                <button id="view-cart" type="button">Keranjang (<span id="cart-count">0</span>)</button>
                <a href="{{ route('orders.index') }}">Riwayat pesanan</a>
                @endcan
                @can('adjust-stock')<a href="{{ route('stock.index') }}">Stok</a>@endcan
                @can('manage-roles')<a href="{{ route('admin.users.index') }}">Kelola role</a>@endcan
                <form method="POST" action="{{ route('logout') }}">@csrf<button>Logout</button></form>
            @else
                <a href="{{ route('login') }}">Login</a>
            @endauth
        </nav>
    </div>
    @can('use-cart')
        <section id="cart-summary" aria-live="polite">
            <strong>Keranjang sementara:</strong> state hanya berada di memori halaman ini.
            <span id="cart-message">Belum ada item.</span>
        </section>
    @endcan
    <h2>Daftar Barang</h2>
    <div class="grid">
        @foreach($products as $product)
            <article class="card">
                <img src="{{ asset('images/' . ($product->image ?: 'product.svg')) }}" alt="{{ $product->name }}">
                <h3>{{ $product->name }}</h3>
                <p>{{ $product->description }}</p>
                <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                @can('adjust-stock')<p class="muted">Stok: {{ $product->stock }}</p>@endcan
                @can('manage-products')<a href="{{ route('admin.products.edit', $product) }}">Edit produk</a>@endcan
                @can('use-cart')
                    <button type="button" class="add-to-cart" data-product-id="{{ $product->id }}" {{ $product->stock < 1 ? 'disabled' : '' }}>
                        Tambah ke keranjang
                    </button>
                @else
                    @guest<a href="{{ route('login') }}">Login untuk membeli</a>@endguest
                @endcan
            </article>
        @endforeach
    </div>
</main>
@can('use-cart')
<script>
    window.cartState = {};
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

    function cartCount() {
        return Object.values(window.cartState).reduce((sum, quantity) => sum + quantity, 0);
    }

    async function addToCart(productId) {
        const response = await fetch(@json(route('cart.add')), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify({cart: window.cartState, product_id: productId})
        });
        if (!response.ok) {
            throw new Error('Server menolak payload cart.');
        }
        return response.json();
    }

    function updateCounter() {
        document.querySelector('#cart-count').textContent = cartCount();
        document.querySelector('#cart-message').textContent = Object.keys(window.cartState).length
            ? `${cartCount()} item siap ditinjau melalui tombol Keranjang.`
            : 'Belum ada item.';
    }

    document.querySelectorAll('.add-to-cart').forEach((button) => {
        button.addEventListener('click', async () => {
            const productId = Number(button.dataset.productId);
            const result = await addToCart(productId);
            window.cartState = result.cart;
            updateCounter();
            document.querySelector('#cart-message').textContent = `${cartCount()} item, total Rp ${result.total.toLocaleString('id-ID')}.`;
        });
    });

    document.querySelector('#view-cart').addEventListener('click', async () => {
        const html = await fetch(@json(route('cart.show')), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'text/html', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify({cart: window.cartState})
        }).then((response) => response.text());
        history.pushState({}, '', @json(route('cart.index')));
        document.open();
        document.write(html);
        document.close();
    });
</script>
@endcan
</body>
</html>
