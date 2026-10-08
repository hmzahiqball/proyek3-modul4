<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'username' => 'putra',
            'password' => 'putra123',
            'nama_lengkap' => 'Putra Suyapratama',
        ]);

        User::create([
            'username' => 'muti',
            'password' => 'pearly123',
            'nama_lengkap' => 'Pearly Lovies',
        ]);
    }
}
