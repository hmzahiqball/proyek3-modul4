<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class RoleDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Akun demonstrasi hanya untuk environment local/testing.');
        }

        $password = env('ROLE_DEMO_PASSWORD');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Tetapkan ROLE_DEMO_PASSWORD lokal minimal 12 karakter.');
        }

        foreach (['admin', 'customer', 'staff'] as $role) {
            $username = 'w07_'.$role;
            $existing = User::where('username', $username)->first();
            if ($existing) {
                if ($existing->role !== $role) {
                    throw new RuntimeException("Akun {$username} sudah ada dengan role berbeda; tidak ditimpa.");
                }

                continue;
            }

            $user = new User;
            $user->username = $username;
            $user->nama_lengkap = 'W07 '.ucfirst($role);
            $user->password = $password;
            $user->role = $role;
            $user->save();
        }
    }
}
