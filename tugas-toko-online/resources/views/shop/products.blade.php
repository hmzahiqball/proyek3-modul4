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
        .card { background: #fff; padding: 1rem; border-radius: .6rem; box-shadow: 0 2px 8px #0001; }
        .card img { display: block; width: 100%; border-radius: .4rem; }
        button { padding: .6rem .8rem; background: #2563eb; color: #fff; border: 0; border-radius: .3rem; cursor: pointer; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
<main>
    <div class="top">
        <h1>Toko Online</h1>
        <nav>
            @auth
                <a href="{{ route('cart.index') }}">Keranjang ({{ array_sum(session('cart', [])) }})</a>
                <a href="{{ route('orders.index') }}">Riwayat pesanan</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button>Logout</button></form>
            @else
                <a href="{{ route('login') }}">Login</a>
            @endauth
        </nav>
    </div>
    <h2>Daftar Barang</h2>
    <div class="grid">
        @foreach($products as $product)
            <article class="card">
                <img src="{{ asset('images/' . ($product->image ?: 'product.svg')) }}" alt="{{ $product->name }}">
                <h3>{{ $product->name }}</h3>
                <p>{{ $product->description }}</p>
                <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                <p class="muted">Stok: {{ $product->stock }}</p>
                @auth
                    @if($product->stock > 0)
                        <form method="POST" action="{{ route('cart.add', $product) }}">@csrf<button>Tambah ke keranjang</button></form>
                    @else
                        <span class="muted">Stok habis</span>
                    @endif
                @else
                    <a href="{{ route('login') }}">Login untuk membeli</a>
                @endauth
            </article>
        @endforeach
    </div>
</main>
</body>
</html>
