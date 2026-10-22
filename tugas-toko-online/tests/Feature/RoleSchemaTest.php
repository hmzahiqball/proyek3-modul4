<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_defaults_new_and_legacy_style_users_to_customer(): void
    {
        $user = User::factory()->create();
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertTrue(Schema::hasIndex('users', ['role']));
        $this->assertNotContains('role', $user->getFillable());
    }

    public function test_demo_seeder_is_local_and_repeatable_without_overwriting_existing_users(): void
    {
        $_ENV['ROLE_DEMO_PASSWORD'] = 'test-only-role-password';
        try {
            $this->seed(RoleDemoSeeder::class);
            $hash = User::where('username', 'w07_admin')->value('password');
            $this->seed(RoleDemoSeeder::class);
            $this->assertDatabaseCount('users', 3);
            foreach (['admin', 'customer', 'staff'] as $role) {
                $this->assertDatabaseHas('users', ['username' => 'w07_'.$role, 'role' => $role]);
            }
            $this->assertSame($hash, User::where('username', 'w07_admin')->value('password'));
        } finally {
            unset($_ENV['ROLE_DEMO_PASSWORD']);
        }
    }
}
