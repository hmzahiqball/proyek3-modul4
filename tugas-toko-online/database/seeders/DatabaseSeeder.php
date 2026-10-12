<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'putra'],
            ['password' => 'putra123', 'nama_lengkap' => 'Putra Suyapratama'],
        );

        $products = [
            ['name' => 'Buku Tulis', 'price' => 5000],
            ['name' => 'Pulpen', 'price' => 3000],
            ['name' => 'Penggaris', 'price' => 4000],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['name' => $productData['name']],
                [
                    'description' => "Produk alat tulis {$productData['name']}.",
                    'price' => $productData['price'],
                    'stock' => 10,
                    'image' => 'product.svg',
                ],
            );
        }
    }
}
