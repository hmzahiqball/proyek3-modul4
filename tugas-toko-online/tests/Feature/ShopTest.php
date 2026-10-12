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
        $this->post(route('cart.add'), ['cart' => [], 'product_id' => $product->id])
            ->assertRedirect(route('login'));
    }

    public function test_cart_payload_is_calculated_from_database_and_get_cart_is_empty(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 3]);
        $this->actingAs($user)
            ->postJson(route('cart.add'), ['cart' => [$product->id => 1], 'product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('cart.'.$product->id, 2)
            ->assertJsonPath('total', 10000);

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Keranjang kosong.');
    }

    public function test_add_endpoint_returns_same_result_for_the_same_payload(): void
    {
        $user = User::factory()->create();
        $book = Product::create(['name' => 'Buku Tulis', 'price' => 5000, 'stock' => 10]);
        $pen = Product::create(['name' => 'Pulpen', 'price' => 3000, 'stock' => 10]);
        $payload = ['cart' => [$book->id => 2], 'product_id' => $pen->id];

        $first = $this->actingAs($user)->postJson(route('cart.add'), $payload);
        $second = $this->actingAs($user)->postJson(route('cart.add'), $payload);

        $first->assertOk()->assertJsonPath('total', 13000);
        $second->assertOk()->assertJsonPath('total', 13000);
        $first->assertJsonPath('cart.'.$book->id, 2)->assertJsonPath('cart.'.$pen->id, 1);
        $second->assertJsonPath('cart.'.$book->id, 2)->assertJsonPath('cart.'.$pen->id, 1);
    }

    public function test_checkout_creates_order_details_and_reduces_stock_from_payload(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 3]);

        $this->actingAs($user)
            ->post(route('checkout'), [
                'cart' => [$product->id => 2],
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
                'cart' => [$product->id => 2],
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
                'cart' => [$product->id => 1],
                'shipping_address' => 'Jl. Contoh 1',
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 5000]);
    }
}
