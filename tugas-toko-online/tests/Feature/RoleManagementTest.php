<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoleManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user = User::factory()->create();
        // Trusted fixture setup without update events.
        User::whereKey($user->id)->update(['role' => $role]);

        return $user->fresh();
    }

    public function test_ac01_admin_changes_customer_to_staff_and_logs_once(): void
    {
        $admin = $this->account('admin');
        $target = $this->account('customer');
        Log::spy();
        $this->actingAs($admin)->from('/admin/users')
            ->patch(route('admin.users.role.update', $target), ['role' => 'staff'])
            ->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'staff']);
        Log::shouldHaveReceived('info')->once()->with('user.role.changed', [
            'target_user_id' => $target->id, 'old_role' => 'customer',
            'new_role' => 'staff', 'actor_id' => $admin->id,
        ]);
    }

    public function test_ac02_customer_is_forbidden_and_database_and_logs_unchanged(): void
    {
        $target = $this->account('customer');
        Log::spy();
        $this->actingAs($target)->patchJson(route('admin.users.role.update', $target), ['role' => 'staff'])
            ->assertForbidden();
        $this->assertSame('customer', $target->fresh()->role);
        Log::shouldNotHaveReceived('info');
    }

    public function test_ac03_admin_cannot_change_self_or_another_admin(): void
    {
        $admin = $this->account('admin');
        $other = $this->account('admin');
        Log::spy();
        foreach ([$admin, $other] as $target) {
            $this->actingAs($admin)->patchJson(route('admin.users.role.update', $target), ['role' => 'customer'])
                ->assertUnprocessable()->assertJsonValidationErrors('role');
            $this->assertSame('admin', $target->fresh()->role);
        }
        Log::shouldNotHaveReceived('info');
    }

    public function test_ac04_invalid_roles_are_rejected_without_changes(): void
    {
        $admin = $this->account('admin');
        $target = $this->account('customer');
        Log::spy();
        foreach ([[], ['role' => 'admin'], ['role' => 'unknown'], ['role' => ['staff']], ['role' => '']] as $payload) {
            $this->actingAs($admin)->patchJson(route('admin.users.role.update', $target), $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('role');
            $this->assertSame('customer', $target->fresh()->role);
        }
        Log::shouldNotHaveReceived('info');
    }

    public function test_ac05_same_role_does_not_save_or_log(): void
    {
        $admin = $this->account('admin');
        $target = $this->account('staff');
        $before = $target->getRawOriginal();
        Log::spy();
        $this->actingAs($admin)->from('/admin/users')
            ->patch(route('admin.users.role.update', $target), ['role' => 'staff'])
            ->assertRedirect('/admin/users');
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $mock = Mockery::mock(User::class)->makePartial();
        $mock->setRawAttributes($target->getAttributes());
        $mock->shouldNotReceive('save');
        app(RoleManagementService::class)->changeRole($admin, $mock, 'staff');
        Log::shouldNotHaveReceived('info');
    }

    public function test_ability_matrix_and_guest_role_endpoint(): void
    {
        $expected = [
            'customer' => [false, false, false, true],
            'staff' => [false, false, true, false],
            'admin' => [true, true, true, false],
        ];
        foreach ($expected as $role => $permissions) {
            $user = $this->account($role);
            foreach (['manage-roles', 'manage-products', 'adjust-stock', 'use-cart'] as $i => $ability) {
                $this->assertSame($permissions[$i], Gate::forUser($user)->allows($ability));
            }
        }
        $target = $this->account('customer');
        $this->patch(route('admin.users.role.update', $target), ['role' => 'staff'])->assertRedirect('/login');
    }

    public function test_observer_ignores_non_role_update_and_bulk_update_and_supports_null_actor(): void
    {
        $target = $this->account('customer');
        Log::spy();
        $target->nama_lengkap = 'Nama baru';
        $target->save();
        User::whereKey($target->id)->update(['role' => 'staff']);
        Log::shouldNotHaveReceived('info');
        $target->refresh();
        $target->role = 'customer';
        $target->save();
        Log::shouldHaveReceived('info')->once()->with('user.role.changed', [
            'target_user_id' => $target->id, 'old_role' => 'staff',
            'new_role' => 'customer', 'actor_id' => null,
        ]);
    }

    public function test_admin_form_only_offers_customer_and_staff_and_requires_csrf(): void
    {
        $admin = $this->account('admin');
        $this->account('customer');
        $this->actingAs($admin)->get('/admin/users')->assertOk()
            ->assertSee('name="_token"', false)->assertSee('value="PATCH"', false)
            ->assertSee('value="customer"', false)->assertSee('value="staff"', false)
            ->assertDontSee('value="admin"', false);
    }
}
