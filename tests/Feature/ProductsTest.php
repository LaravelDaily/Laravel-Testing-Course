<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    public function test_homepage_contains_empty_table()
    {
        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertSee(__('No products found'));
    }

    public function test_homepage_contains_non_empty_table()
    {
        Product::create([
            'name' => 'Product 1',
            'price' => 123
        ]);
        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertDontSee(__('No products found'));
    }
}
