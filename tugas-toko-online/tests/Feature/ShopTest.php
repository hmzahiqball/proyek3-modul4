<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_products_but_must_login_to_buy(): void
    {
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 2]);

        $this->get('/')->assertOk()->assertSee('Buku');
        $this->post(route('cart.store'), ['cart' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertRedirect(route('login'));
    }

    public function test_cart_payload_is_calculated_from_database_and_get_cart_is_empty(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 3]);
        $payload = [['product_id' => $product->id, 'quantity' => 2]];

        $this->actingAs($user)
            ->post(route('cart.store'), ['cart' => $payload])
            ->assertOk()
            ->assertSee('Rp 10.000')
            ->assertSee('Buku');

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Keranjang kosong.');
    }

    public function test_checkout_creates_order_details_and_reduces_stock_from_payload(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 3]);

        $this->actingAs($user)
            ->post(route('checkout'), [
                'cart' => [['product_id' => $product->id, 'quantity' => 2]],
                'shipping_address' => 'Jl. Contoh 1',
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 10000]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'unit_price' => 5000, 'quantity' => 2]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
    }

    public function test_checkout_rejects_quantity_above_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 1]);

        $this->actingAs($user)
            ->post(route('checkout'), [
                'cart' => [['product_id' => $product->id, 'quantity' => 2]],
                'shipping_address' => 'Jl. Contoh 1',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_client_cannot_submit_a_forged_price(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 2]);

        $this->actingAs($user)
            ->post(route('checkout'), [
                'cart' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 1]],
                'shipping_address' => 'Jl. Contoh 1',
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 5000]);
    }
}
