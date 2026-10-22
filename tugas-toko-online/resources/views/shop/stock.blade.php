<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>Stok produk</title></head>
<body><main>
    <h1>Stok produk</h1><a href="{{ route('products.index') }}">Katalog</a>
    <table><thead><tr><th>Produk</th><th>Stok</th></tr></thead><tbody>
    @foreach($products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->stock }}</td></tr>@endforeach
    </tbody></table>
</main></body></html>
