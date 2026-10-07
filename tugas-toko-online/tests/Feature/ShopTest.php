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
        $this->post(route('cart.add', $product))->assertRedirect(route('login'));
    }

    public function test_checkout_creates_order_details_reduces_stock_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 3]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('checkout'), ['shipping_address' => 'Jl. Contoh 1'])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 10000]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'unit_price' => 5000, 'quantity' => 2]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $this->assertSame([], session('cart', []));
    }

    public function test_checkout_rejects_quantity_above_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 1]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('checkout'), ['shipping_address' => 'Jl. Contoh 1'])
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }
}
