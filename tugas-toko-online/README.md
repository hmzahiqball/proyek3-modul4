## Toko Online

Aplikasi toko online Laravel 13 untuk tugas pemrograman web. Pengunjung dapat
melihat produk, sedangkan login diperlukan untuk mengelola keranjang dan
melakukan checkout.

### Fitur

- Login dengan password ter-hash dan middleware `auth`/`guest`.
- Daftar tiga produk dengan gambar, harga, stok, dan deskripsi.
- Keranjang stateless: payload cart dikirim dari browser, sedangkan harga, stok,
  subtotal, dan total selalu dihitung ulang oleh server.
- Checkout dengan validasi stok, transaksi database, dan pengarsipan harga item.
- Riwayat pesanan milik pengguna.

### Menjalankan aplikasi

1. Salin `.env.example` menjadi `.env` dan atur koneksi MySQL.
2. Jalankan `composer install`.
3. Buat application key dengan `php artisan key:generate`.
4. Jalankan migrasi dan seeder:

   ```bash
   php artisan migrate --seed
   ```

5. Jalankan server:

   ```bash
   php artisan serve
   ```

`GET /cart` tidak menerima payload sehingga menampilkan keranjang kosong setelah
refresh. Interaksi cart menggunakan `POST /cart` dengan payload JSON seperti
`{"cart":[{"product_id":1,"quantity":2}]}`. State tersebut hanya berada di
memori JavaScript halaman dan tidak disimpan pada session, cookie, atau database.

Data login hasil seeder:

```text
Username: putra
Password: putra123
```

### Pengujian

```bash
php artisan test
```
