<?php

use App\Models\Product;

beforeEach(function () {
    $this->user = createUser();
    $this->admin = createUser(isAdmin: true);
});

test('homepage contains empty table', function () {
    $this->actingAs($this->user)
        ->get('/products')
        ->assertStatus(200)
        ->assertSee(__('No products found'));
});

test('homepage contains non empty table', function () {
    $product = Product::create([
        'name' => 'Product 1',
        'price' => 123
    ]);

    $this->actingAs($this->user)
        ->get('/products')
        ->assertStatus(200)
        ->assertDontSee(__('No products found'))
        ->assertSee('Product 1')
        ->assertViewHas('products', function ($collection) use ($product) {
            return $collection->contains($product);
        });
});

test('create product successful', function() {
    $product = [
        'name' => 'Product 123',
        'price' => 1234
    ];

    $this->actingAs($this->admin)
        ->post('/products', $product)
        ->assertRedirect('products');

    $this->assertDatabaseHas('products', $product);

    $lastProduct = Product::latest()->first();
    expect($lastProduct->name)->toBe($product['name']);
    expect($lastProduct->price)->toBe($product['price']);
});
