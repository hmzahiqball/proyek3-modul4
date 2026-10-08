# Modul 4 - Penyimpanan Data pada Web

Repository ini berisi implementasi tugas Modul 4 Pemrograman Web menggunakan
Laravel 13. Materi yang diterapkan meliputi autentikasi, session, keranjang
belanja, migration, seeder, dan penyimpanan data pada MySQL.

## Struktur tugas

| Folder | Tugas | Fitur utama |
| --- | --- | --- |
| [`tugas1`](./tugas1) | Tugas 1 - Login Laravel | Login, logout, middleware `auth`/`guest`, dan dashboard |
| [`tugas-2-keranjang`](./tugas-2-keranjang) | Tugas 2 - Keranjang Belanja | Session cart, tambah produk, ubah jumlah, hapus item, dan kosongkan keranjang |
| [`tugas-toko-online`](./tugas-toko-online) | Tugas toko online | Login, katalog produk, cart, checkout, order, migration, dan seeder MySQL |

## Persyaratan

- PHP 8.3 atau lebih baru
- Composer
- MySQL/MariaDB
- Ekstensi PHP `pdo_mysql`

Setiap folder aplikasi merupakan project Laravel mandiri. Jalankan perintah
berikut dari folder aplikasi yang ingin digunakan.

## Konfigurasi MySQL

Salin file environment:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Atur nilai berikut pada `.env` sesuai instalasi MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_tugas
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `db_tugas` terlebih dahulu. Jika MySQL berjalan pada port
berbeda, ubah `DB_PORT` sesuai port tersebut.

## Tugas 1: Login

```powershell
cd tugas1
composer install
php artisan migrate --seed
php artisan serve --port=8000
```

Buka `http://127.0.0.1:8000/login`.

Kredensial seeder:

```text
Username: putra
Password: putra123
```

## Tugas 2: Keranjang belanja

```powershell
cd tugas-2-keranjang
composer install
php artisan migrate --seed
php artisan serve --port=8000
```

Buka `http://127.0.0.1:8000/products`. Produk dan isi keranjang disimpan
menggunakan session.

## Toko online dan MySQL

```powershell
cd tugas-toko-online
composer install
php artisan migrate
php artisan db:seed
php artisan serve --port=8000
```

Migration yang tersedia mencakup:

- `users`, `sessions`, cache, dan jobs
- `products`
- `orders` dan `order_items`
- penambahan kolom `products.description` untuk database lama

Seeder membuat satu akun pengguna dan sepuluh produk alat tulis. Seeder dapat
dijalankan berulang kali karena menggunakan `updateOrCreate`.

Kredensial seeder:

```text
Username: putra
Password: putra123
```

## Pengujian

Jalankan test dari folder aplikasi:

```powershell
php artisan test
```

Jangan menjalankan dua aplikasi Laravel pada port yang sama secara bersamaan.
Gunakan port berbeda jika ingin menjalankan lebih dari satu tugas, misalnya
`--port=8001`.
