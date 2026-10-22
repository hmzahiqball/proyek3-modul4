# W07 Modul 5 — Role Management

Status implementasi dan pengujian: LOLOS PENGUJIAN. Finalisasi push/tag dicatat terpisah pada laporan rilis setelah verifikasi remote.

## Spesifikasi dan baseline

Acuan yang telah dibaca: Modul Praktikum Versi 2, W07 - Modul 5 - Role Management.pdf (9 halaman), serta W07 - Role Management Introduction.pdf (13 halaman). Matriks dan BR ada pada modul halaman 2–3, kontrak komponen halaman 4–6, eksperimen halaman 7, dan DoD halaman 8. Tidak ditemukan worksheet W07 dalam repository; dokumen ini adalah dokumentasi implementasi/bukti, bukan format worksheet kelas yang dikarang.

Repository tetap https://github.com/hmzahiqball/proyek3-modul4. Baseline `main` @ `48353536332aa885d278dcb19258aabd29fa05cb` terverifikasi terhadap remote dan tag `tugas-toko-online-stateless`; riwayat memuat implementasi cart W06. Aplikasi aktif adalah subfolder `tugas-toko-online`, Laravel 13.34.0, PHP 8.5.9. Aplikasi lain tidak digabung/diubah. Branch baru `week7-modul5-role-management` dibuat dari baseline tersebut setelah pemulihan metadata rebase oleh pengguna. Ketiga perubahan dokumen pengguna dipertahankan dan dikecualikan dari seluruh staging W07.

## Access matrix

| Aktivitas | Guest | Customer | Staff | Admin |
| --- | --- | --- | --- | --- |
| Katalog `/` | Ya | Ya | Ya | Ya |
| Cart `/cart`, `POST /cart`, `POST /cart/add`, checkout | Tidak | Ya | Tidak | Tidak |
| Stok `/stock` dan jumlah stok di katalog | Tidak | Tidak | Ya | Ya |
| Edit produk `/admin/products/{product}/edit`, PATCH produk | Tidak | Tidak | Tidak | Ya |
| Role `/admin/users`, PATCH role | Tidak | Tidak | Tidak | Ya |

Gate `adjust-stock` mengikuti nama ability modul; akses yang dipakai staff adalah melihat stok. Pengeditan produk termasuk stok tetap melalui admin. Hanya penambahan edit produk sederhana dilakukan untuk membuat baris manage-products dapat diuji, tanpa merombak fitur toko. Angka stok disembunyikan dari HTML guest/customer, termasuk atribut data-stock. Validasi ketersediaan stok untuk pembelian tetap dilakukan server.

## Checklist requirement

Seluruh referensi file relatif terhadap aplikasi ini. Bukti JSON menyediakan request/response, snapshot DB sebelum/sesudah, prediksi, jumlah log sebelum/sesudah dan baris log. `test-suite.txt` merekam test aktual, bukan sekadar daftar skenario. Spasi akhir/blank line terminal dinormalisasi untuk lolos git diff --check; hasil, status dan isi log tidak diubah.

| Requirement | Status | Komponen | Pengujian dan bukti |
| --- | --- | --- | --- |
| Role matrix | LOLOS PENGUJIAN | AppServiceProvider, routes/web.php, Blade katalog, ProductController | RoleManagementTest ability matrix; RoleCartTest full matrix; routes.txt |
| BR-01 | LOLOS PENGUJIAN | migration add_role, User::$attributes | RoleSchemaTest insert SQL tanpa role dan instance baru; local-mysql.json menunjukkan varchar(20), default customer, index |
| Migration/default role | LOLOS PENGUJIAN | database/migrations/2026_10_22_000001_add_role_to_users_table.php | Migration additive dijalankan MySQL; akun lama menjadi customer, tanpa penghapusan data; semua migration Ran |
| Seeder demonstrasi | LOLOS PENGUJIAN | database/seeders/RoleDemoSeeder.php | Tiga akun lokal; repeatable, tidak menimpa password/akun lama; collision ditolak; guard production; local-mysql.json |
| BR-02/Gate/middleware | LOLOS PENGUJIAN | AppServiceProvider, route PATCH role | AC-02: 403; DB/log tetap; kemampuan lain diuji matrix |
| BR-03/ChangeRoleRequest | LOLOS PENGUJIAN | app/Http/Requests/ChangeRoleRequest.php | role required/string/customer atau staff; AC-04 dan input kosong/array/admin/unknown |
| RoleController | LOLOS PENGUJIAN | app/Http/Controllers/RoleController.php | Request tervalidasi -> service -> redirect; nama route admin.users.role.update unik; route:list |
| BR-04/RoleManagementService | LOLOS PENGUJIAN | app/Services/RoleManagementService.php | AC-03 HTTP 422 untuk diri sendiri, feature test juga admin lain; DB/log tetap |
| BR-05 | LOLOS PENGUJIAN | service early return | AC-05 HTTP 302 tanpa DB/log; mock User melarang save() secara eksplisit |
| BR-06/UserObserver | LOLOS PENGUJIAN | app/Observers/UserObserver.php dan satu registrasi provider | AC-01 satu user.role.changed dengan target/old/new/actor; non-role update diabaikan, CLI actor null |
| BR-07/integrasi W06 | LOLOS PENGUJIAN | middleware can:use-cart, Blade cart existing | AC-06; real HTTP SAME-SESSION-REVOKED; payload cart tetap request; session_cart_stored false |
| AC-01 | LOLOS PENGUJIAN | endpoint/service/Observer | 302; customer->staff; tepat satu log; http-db-log.json |
| AC-02 | LOLOS PENGUJIAN | middleware can:manage-roles | 403 customer; DB/log tetap; http-db-log.json |
| AC-03 | LOLOS PENGUJIAN | service | 422 admin sendiri; DB/log tetap; http-db-log.json |
| AC-04 | LOLOS PENGUJIAN | Form Request | 422 role admin invalid; DB/log tetap; http-db-log.json |
| AC-05 | LOLOS PENGUJIAN | service | 302 role sama; DB/log tetap; mock membuktikan tanpa save |
| AC-06 | LOLOS PENGUJIAN | middleware cart | 403 staff sebelum validasi payload invalid; produk/order/role tetap; http-db-log.json |
| Observer vs bulk update | LOLOS PENGUJIAN | dummy SQLite, Eloquent | save satu event; bulk dua user nol event; snapshot setiap user, role dipulihkan |
| HTTP/DB/log | LOLOS PENGUJIAN | scripts/w07_verify_http.py | HTTP loopback nyata dengan database session dan CSRF aktif; JSON + observer-events.txt |
| Regresi W05-W06 | LOLOS PENGUJIAN | AuthenticationTest, ShopTest, runner | login/session/dashboard/logout, payload stateless, kalkulasi harga tepercaya, checkout/stock; test-suite.txt |
| Bukti/refleksi | LOLOS PENGUJIAN | dokumen ini dan evidence/ | Prediksi dibuat sebelum action; response/DB/log aktual; refleksi di bawah; daftar screenshot manual |
| Commit/push checkpoint | LOLOS PENGUJIAN | Git branch W07 | e19dc7b, e74abb1, ce234a3; hash remote ce234a3 terverifikasi sebelum dokumentasi akhir |
| Commit/push akhir dan tag | SEDANG DIKERJAKAN | Git | Tag rencana w07-modul5-role-management-v1.0.0; status final hanya ditulis setelah commit/push dan remote terverifikasi |

## Hasil HTTP aktual

Runner memakai aplikasi Laravel yang sama, database SQLite dummy baru per run, login browser-like berbasis cookie, session database, dan CSRF aktif. Tidak mengganti .env atau data MySQL. Input login disamarkan; token/cookie/hash/password tidak diekspor. URL port dinamis lengkap tersedia di JSON. Timestamp executed_at memakai WIB (+07:00); baris log mentah mengikuti konfigurasi timezone UTC aplikasi baseline. HTML response hanya diringkas agar token session tidak ikut bukti.

| ID | Prediksi sebelum request | Method | Path | HTTP | Database | Tambahan log |
| --- | --- | --- | --- | --- | --- | --- |
| GUEST | 302 ke login; DB/log tetap | POST | `cart/add` | 302 | Tetap | 0 |
| CSRF | 419 tanpa token; DB/log tetap | PATCH | `admin/users/2/role` | 419 | Tetap | 0 |
| CART-W06 | 200 total 10000 dari payload cart; DB/log tetap | POST | `cart/add` | 200 | Tetap | 0 |
| AC-01 | 302; customer -> staff; satu log | PATCH | `admin/users/2/role` | 302 | Berubah | 1 |
| SAME-SESSION-REVOKED | 403 pada sesi customer yang sudah login setelah berubah staff | POST | `cart/add` | 403 | Tetap | 0 |
| AC-03 | 422; admin sendiri ditolak service; DB/log tetap | PATCH | `admin/users/1/role` | 422 | Tetap | 0 |
| AC-04 | 422; role admin tidak valid; DB/log tetap | PATCH | `admin/users/2/role` | 422 | Tetap | 0 |
| AC-05 | 302; role staff sama; DB/log tetap | PATCH | `admin/users/2/role` | 302 | Tetap | 0 |
| AC-06 | 403 staff; DB/log tetap; payload invalid membuktikan validasi cart tidak dicapai | POST | `cart/add` | 403 | Tetap | 0 |
| RESTORE-CUSTOMER | 302; staff -> customer; satu log | PATCH | `admin/users/2/role` | 302 | Berubah | 1 |
| AC-02 | 403 customer; DB/log tetap | PATCH | `admin/users/2/role` | 403 | Tetap | 0 |
| CART-STATELESS | 200 tanpa cart lama pada payload kosong | POST | `cart/add` | 200 | Tetap | 0 |
| LOGOUT-W05 | 302 login; DB/log tetap | POST | `logout` | 302 | Tetap | 0 |
| DASHBOARD-AFTER-LOGOUT | 302 login; sesi tidak terautentikasi | GET | `dashboard` | 302 | Tetap | 0 |

AC-01 diterima Gate admin, lolos validasi, lolos aturan target service, lalu save menerbitkan updated. Observer melihat wasChanged(role) dan menulis satu log. AC-02 dan AC-06 berhenti di middleware can sehingga controller tidak dijalankan. AC-03 berhenti di service; AC-04 di Form Request. AC-05 berhenti pada early return service; status sukses tidak berarti ada penulisan database.

Status 302 adalah response asli kontrak redirect controller. Untuk payload JSON yang gagal validasi/service Laravel memberi 422; form HTML dapat redirect dengan session errors. Guest dengan CSRF valid mendapat 302 login untuk Accept HTML; request tanpa CSRF lebih dahulu mendapat 419, termasuk bila belum login. Pengujian tidak menonaktifkan CSRF sebagai jalan pintas.

## Eksperimen Observer dan refleksi

`$target->save()` memuat instance User dan menjalankan event lifecycle model; Observer updated memeriksa wasChanged('role'). Bukti CLI menunjukkan customer->staff menghasilkan satu event dengan actor_id null, karena tidak ada user HTTP yang login pada CLI.

Bulk `User::whereIn('id', [customerId, staffId])->update(...)` mengubah dua baris dengan satu query SQL tanpa memuat dan menyimpan setiap instance User. Tidak ada event updated per model, sehingga tidak ada callback Observer untuk kedua user meskipun kedua role berubah. Bukti before/after per ID dan nol log ada pada observer_experiment di JSON. Role customer dan staff dummy dipulihkan setelah eksperimen. Jangan memakai jalur bulk ini pada endpoint role; jalur aplikasi tetap service + instance save.

Observer berbeda dari event/listener umum: Observer mengelompokkan callback lifecycle model Eloquent tertentu, sedangkan event/listener umum menanggapi event yang didispatch aplikasi dan tidak harus berkaitan dengan model. Observer praktikum ini adalah bukti pembelajaran; bukan pengganti authorization atau audit produksi yang transaksional.

Pembagian tanggung jawab:
- Route/middleware menentukan apakah pemohon boleh masuk; Gate memetakan role ke ability saat request berjalan.
- Form Request memastikan input valid, tanpa menawarkan admin lewat form publik.
- Controller menghubungkan input tervalidasi, actor/target dan service, lalu memberikan response.
- Service menjaga larangan akun sendiri/admin dan no-op; kontraknya mengasumsikan pemanggil telah mengotorisasi dan memvalidasi input seperti modul. Jangan mengeksposnya melalui route baru tanpa pemeriksaan tersebut.
- Model melakukan persistensi; default model dan default database customer dibuat konsisten. Role tidak ditambahkan pada fillable publik.
- Observer mencatat perubahan yang benar terjadi, dengan konteks terbatas; kegagalan logging tidak membuat permintaan terlarang menjadi sah.

Session menyimpan identitas login, sedangkan cart berasal dari JSON request. Reuse session customer sesudah admin mengubah role tidak mempertahankan kemampuan lama: request berikut membaca role terbaru dari DB dan Gate menolak 403. Response cart tidak menulis cart ke session. Perhitungan harga tetap berasal dari Product di database.

## Eksekusi ulang

Dari root aplikasi:

```powershell
php artisan migrate
# Tetapkan password lokal sendiri sebelum seed pertama. Jangan commit environment/password.
$env:ROLE_DEMO_PASSWORD = Read-Host 'Password akun demo lokal, minimal 12 karakter' -MaskInput
php artisan db:seed --class=RoleDemoSeeder
Remove-Item Env:ROLE_DEMO_PASSWORD
php artisan test --no-ansi
php artisan route:list -vv --except-vendor
python scripts/w07_verify_http.py --php C:/xampp/php/php.exe --work-dir C:/path/to/scratch
php artisan serve --port=8000
```

Runner membutuhkan Python 3 (stdlib) dan PHP dengan SQLite; work-dir pada drive yang sama dengan aplikasi karena konfigurasi baseline memakai database_path. Setiap run membuat DB dummy baru dan menyimpan scratch untuk inspeksi; tidak menjalankan migrate:fresh pada DB pengguna. Runner menolak config cache aktif dan memverifikasi driver/path dummy sebelum migration; bersihkan config cache terlebih dahulu bila ada. Pada mesin ini tidak ada config cache aktif. Bukti hasil ulang akan mengganti berkas evidence dengan hasil aktual yang baru.

Seeder hanya tersedia pada local/testing dan tidak dipanggil otomatis DatabaseSeeder umum. Username: w07_admin, w07_customer, w07_staff. Password lokal yang disiapkan disimpan di keluaran Codex di luar Git; password akun putra tidak diubah. Seeder tidak mereset password akun yang sudah ada. Untuk mengubah password demo secara tepercaya gunakan Tinker pada environment local, pilih username demo yang tepat, assign password baru menggunakan prompt tersembunyi Laravel\Prompts\password, lalu save(). Jangan mengubah role admin lewat registrasi publik.

## Bukti visual untuk worksheet kelas

Screenshot belum dihasilkan; bukti eksekusi aktual tersedia sebagai teks/JSON. Jika worksheet kelas mewajibkan gambar, ambil screenshot berikut dan jangan tampilkan token/cookie/hash/password:
1. php artisan route:list -vv untuk middleware dan nama endpoint role/cart.
2. Tabel users hanya id, username, role, sebelum/sesudah PATCH admin customer->staff.
3. DevTools Network request PATCH: URL/method, payload role dan response/status; sembunyikan headers sensitif.
4. Request customer ke role, admin ubah diri/invalid/sama, staff cart: status dan response masing-masing.
5. Baris user.role.changed yang telah difilter beserta hitungan sebelum/sesudah; role sama tidak menambah baris.
6. Session customer yang sudah login: cart sukses, admin mengubah role, cart berikut 403 tanpa logout/login ulang.
7. Terminal eksperimen save dan bulk dua user, snapshot role setiap ID, serta log per operasi.
8. Hasil test suite dan branch/tag remote akhir.

Gunakan akun demo/customer terpisah ketika mengulangi eksperimen dan pulihkan role target melalui alur admin. Jangan memakai bulk untuk perubahan yang perlu dicatat aplikasi.

## Riwayat masalah dan bantuan AI

AI dipakai untuk audit, implementasi, test dan pencatatan bukti. Temuan penting: default database tidak langsung mengisi atribut instance Eloquent yang baru dibuat; lima regresi cart awal memberi 403. Perbaikan default User customer memulihkan seluruh regresi dan diuji pada instance serta insert SQL. Runner pertama perlu menyesuaikan path SQLite dengan konfigurasi baseline yang memakai database_path; source konfigurasi aplikasi tidak diubah. Guest POST tanpa token awalnya mendapat 419; runner lalu mengambil token guest yang valid agar dapat membuktikan redirect authentication secara terpisah. Tambahan test guard production memanggil seeder langsung agar guard dapat diuji tanpa prompt Artisan. Semua hasil akhir berasal dari eksekusi setelah perbaikan; tidak menyatakan percobaan gagal sebagai kelulusan.

## Milestone dan Git

| Milestone | Requirement/implementasi | Pengujian | Status | Commit/push |
| --- | --- | --- | --- | --- |
| Baseline audit | PDF, struktur, main/tag/remote, tiga perubahan dokumen | status/branch/log/tag/diff/ls-remote; baseline 14 test/48 assertions | LOLOS PENGUJIAN | baseline 4835353; tidak mengubah main |
| Role schema/seeder | migration additive, trusted demo seeder | 2 test/8 assertions pada checkpoint; MySQL kemudian terverifikasi | LOLOS PENGUJIAN | e19dc7b; remote terverifikasi saat milestone berikut |
| Role flow/Observer | Gate, request, controller, service, Observer, form admin | 8 test/64 assertions | LOLOS PENGUJIAN | e74abb1; remote terverifikasi |
| Integrasi matrix/cart | can cart, stok staff/admin, edit produk admin, default model | 27 test/165 assertions; Pint pada file dirty | LOLOS PENGUJIAN | ce234a3; remote terverifikasi |
| Bukti/regresi/finalisasi | runner HTTP, eksperimen bulk dua user, tambahan schema guard, format seluruh file W07, dokumentasi | 29 test/170 assertions; real HTTP; Pint seluruh file W07 | SEDANG DIKERJAKAN | commit/push/tag akhir diperiksa setelah dokumentasi tersimpan |

Remote: https://github.com/hmzahiqball/proyek3-modul4.git. Branch W07: week7-modul5-role-management. Tag akhir rencana: w07-modul5-role-management-v1.0.0. Status rilis akhir tersedia pada laporan final setelah verifikasi ls-remote; tidak ada tag W05-W06 yang dipindahkan/dihapus.
