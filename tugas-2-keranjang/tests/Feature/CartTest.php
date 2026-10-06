<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class CartTest extends TestCase
{
    public function test_product_can_be_added_and_quantity_changed_in_session(): void
    {
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 3]);
        $this->post(route('cart.add', $product))->assertRedirect();
        $this->post(route('cart.add', $product))->assertRedirect();
        $this->assertSame(2, session('cart')[$product->id]);
        $this->post(route('cart.change', [$product, 'increase']))->assertRedirect();
        $this->assertSame(3, session('cart')[$product->id]);
        $this->post(route('cart.change', [$product, 'increase']))->assertRedirect();
        $this->assertSame(3, session('cart')[$product->id]);
    }

    public function test_decreasing_to_zero_removes_item_and_clear_empties_cart(): void
    {
        $product = Product::create(['name' => 'Buku', 'price' => 5000, 'stock' => 3]);
        $this->withSession(['cart' => [$product->id => 1]])
            ->post(route('cart.change', [$product, 'decrease']))->assertRedirect();
        $this->assertSame([], session('cart', []));
        $this->withSession(['cart' => [$product->id => 1]])
            ->delete(route('cart.clear'))->assertRedirect();
        $this->assertSame([], session('cart', []));
    }
}
