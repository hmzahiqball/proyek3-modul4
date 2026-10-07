<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['username' => 'putra', 'password' => 'putra123', 'nama_lengkap' => 'Putra Suyapratama']);
        foreach (['Buku Tulis', 'Pulpen Gel', 'Pensil 2B', 'Penghapus', 'Penggaris', 'Spidol', 'Stabilo', 'Kertas A4', 'Map Plastik', 'Notebook'] as $index => $name) {
            Product::create(['name' => $name, 'description' => "Produk alat tulis {$name}.", 'price' => 2500 + ($index * 1000), 'stock' => 5 + $index]);
        }
    }
}
