<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>Edit produk</title></head>
<body><main>
    <h1>Edit produk</h1><a href="{{ route('products.index') }}">Katalog</a>
    @foreach($errors->all() as $error)<p role="alert">{{ $error }}</p>@endforeach
    <form method="POST" action="{{ route('admin.products.update', $product) }}">
        @csrf @method('PATCH')
        <p><label>Nama <input name="name" value="{{ old('name', $product->name) }}" required></label></p>
        <p><label>Deskripsi <textarea name="description">{{ old('description', $product->description) }}</textarea></label></p>
        <p><label>Harga <input name="price" type="number" min="0" value="{{ old('price', $product->price) }}" required></label></p>
        <p><label>Stok <input name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}" required></label></p>
        <button>Simpan produk</button>
    </form>
</main></body></html>
