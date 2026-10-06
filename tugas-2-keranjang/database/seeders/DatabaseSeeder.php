<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Buku Tulis', 'price' => 5000, 'stock' => 20],
            ['name' => 'Pulpen', 'price' => 3000, 'stock' => 20],
            ['name' => 'Penggaris', 'price' => 4000, 'stock' => 10],
            ['name' => 'Pensil 2B', 'price' => 2500, 'stock' => 15],
            ['name' => 'Penghapus', 'price' => 1500, 'stock' => 25],
        ] as $product) {
            Product::create($product);
        }
    }
}
