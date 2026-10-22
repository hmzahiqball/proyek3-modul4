<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class RoleSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_defaults_new_and_legacy_style_users_to_customer(): void
    {
        $user = User::factory()->create();
        $this->assertSame('customer', $user->role);
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertTrue(Schema::hasIndex('users', ['role']));
        $this->assertNotContains('role', $user->getFillable());
        DB::table('users')->insert(['username' => 'sql-default', 'nama_lengkap' => 'SQL', 'password' => 'not-a-login']);
        $this->assertDatabaseHas('users', ['username' => 'sql-default', 'role' => 'customer']);
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

    public function test_demo_seeder_refuses_role_collision_without_overwriting_account(): void
    {
        $user = User::factory()->create(['username' => 'w07_admin']);
        $_ENV['ROLE_DEMO_PASSWORD'] = 'test-only-role-password';
        try {
            $this->seed(RoleDemoSeeder::class);
            $this->fail('Role collision must not promote an existing account.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('role berbeda', $e->getMessage());
            $this->assertSame('customer', $user->fresh()->role);
        } finally {
            unset($_ENV['ROLE_DEMO_PASSWORD']);
        }
    }

    public function test_demo_seeder_is_disabled_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('local/testing');
        (new RoleDemoSeeder)->run();
    }
}
