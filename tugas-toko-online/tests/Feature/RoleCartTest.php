<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\RoleManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RoleCartTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user = User::factory()->create();
        User::whereKey($user->id)->update(['role' => $role]);

        return $user->fresh();
    }

    public function test_ac06_staff_and_admin_are_forbidden_before_cart_operation(): void
    {
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 10]);
        Log::spy();
        foreach (['staff', 'admin'] as $role) {
            $user = $this->account($role);
            // Invalid input still returns 403, so cart validation/controller was not reached.
            $this->actingAs($user)->postJson('/cart/add', ['cart' => 'invalid'])->assertForbidden();
            $this->actingAs($user)->postJson('/cart/add', ['cart' => [], 'product_id' => $product->id])->assertForbidden();
            $this->get('/cart')->assertForbidden();
            $this->post('/cart', ['cart' => []])->assertForbidden();
            $this->post('/checkout', ['cart' => []])->assertForbidden();
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertFalse(session()->has('cart'));
        Log::shouldNotHaveReceived('info');
    }

    public function test_customer_role_change_revokes_cart_on_next_request_in_same_session(): void
    {
        $customer = $this->account('customer');
        $admin = $this->account('admin');
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 10]);
        $payload = ['cart' => [$product->id => 1], 'product_id' => $product->id];
        $this->actingAs($customer)->postJson('/cart/add', $payload)->assertOk()->assertJsonPath('total', 10000);
        app(RoleManagementService::class)->changeRole($admin, $customer, 'staff');
        // A new HTTP request resolves the user from the database; explicitly rehydrate in the feature harness.
        $this->actingAs($customer->fresh())->postJson('/cart/add', $payload)->assertForbidden();
        $this->assertFalse(session()->has('cart'));
    }

    public function test_full_access_matrix_and_stock_visibility(): void
    {
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 10]);
        $this->get('/')->assertOk()->assertDontSee('Stok:');
        $this->get('/stock')->assertRedirect('/login');
        $this->get('/admin/products/'.$product->id.'/edit')->assertRedirect('/login');
        foreach (['customer', 'staff', 'admin'] as $role) {
            $user = $this->account($role);
            $catalog = $this->actingAs($user)->get('/')->assertOk();
            if ($role === 'customer') {
                $catalog->assertDontSee('Stok:')->assertDontSee('data-stock=', false);
            } else {
                $catalog->assertSee('Stok:');
            }
            $this->get('/stock')->assertStatus($role === 'customer' ? 403 : 200);
            $this->get('/admin/products/'.$product->id.'/edit')->assertStatus($role === 'admin' ? 200 : 403);
            $this->get('/admin/users')->assertStatus($role === 'admin' ? 200 : 403);
            $this->patch('/admin/products/'.$product->id, [
                'name' => 'Buku baru', 'description' => 'Alat tulis', 'price' => 6000, 'stock' => 9,
            ])->assertStatus($role === 'admin' ? 302 : 403);
        }
        $this->assertSame('Buku baru', $product->fresh()->name);
    }
}
