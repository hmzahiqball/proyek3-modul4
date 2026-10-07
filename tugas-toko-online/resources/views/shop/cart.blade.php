<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Keranjang</title></head>
<body style="font-family:Arial;background:#f3f4f6;padding:2rem">
<main style="max-width:55rem;margin:auto">
    <a href="{{ route('products.index') }}">← Produk</a>
    <h1>Keranjang Belanja</h1>
    @forelse($items as $item)
        <article style="background:#fff;padding:1rem;margin:.75rem 0;border-radius:.5rem;display:flex;justify-content:space-between;gap:1rem">
            <div>
                <strong>{{ $item['product']->name }}</strong><br>
                Rp {{ number_format($item['product']->price, 0, ',', '.') }} × {{ $item['quantity'] }}
                = <strong>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
            </div>
            <div>
                <form style="display:inline" method="POST" action="{{ route('cart.change', [$item['product'], 'decrease']) }}">@csrf<button>-</button></form>
                <span>{{ $item['quantity'] }}</span>
                <form style="display:inline" method="POST" action="{{ route('cart.change', [$item['product'], 'increase']) }}">@csrf<button>+</button></form>
                <form style="display:inline" method="POST" action="{{ route('cart.remove', $item['product']) }}">@csrf @method('DELETE')<button>Hapus</button></form>
            </div>
        </article>
    @empty
        <p>Keranjang kosong.</p>
    @endforelse
    @if($items->isNotEmpty())
        <h2>Total: Rp {{ number_format($total, 0, ',', '.') }}</h2>
        <form method="POST" action="{{ route('cart.clear') }}">@csrf @method('DELETE')<button>Kosongkan keranjang</button></form>
        <hr>
        <form method="POST" action="{{ route('checkout') }}">
            @csrf
            <label>Alamat pengiriman<br><textarea name="shipping_address" required rows="3" cols="50">{{ old('shipping_address') }}</textarea></label><br>
            <button>Checkout</button>
        </form>
    @endif
</main>
</body>
</html>
