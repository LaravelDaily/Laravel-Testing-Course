<?php

namespace Tests\Feature;

use App\Jobs\ProductPublishJob;
use App\Models\Product;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_contains_empty_table()
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertSee(__('No products found'));
    }

    public function test_homepage_contains_non_empty_table()
    {
        $product = Product::create([
            'name' => 'Product 1',
            'price' => 123
        ]);
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertDontSee(__('No products found'));
        $response->assertSee('Product 1');
        $response->assertViewHas('products', function ($collection) use ($product) {
            return $collection->contains($product);
        });
    }

    public function test_homepage_contains_table_product()
    {
        $product = Product::create([
            'name' => 'table',
            'price' => 123
        ]);
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertSee($product->name);
    }

    public function test_homepage_contains_products_in_order()
    {
        [$product1, $product2] = Product::factory(2)->create();
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertSeeInOrder([$product1->name, $product2->name]);
    }

    public function test_paginated_products_table_doesnt_contain_11th_record()
    {
        $products = Product::factory(11)->create();
        $lastProduct = $products->last();

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertViewHas('products', function ($collection) use ($lastProduct) {
            return !$collection->contains($lastProduct);
        });
    }

    public function test_admin_can_see_products_create_button()
    {
        $response = $this->actingAs($this->admin)->get('/products');

        $response->assertOk();
        $response->assertSee('Add new product');
    }

    public function test_non_admin_cannot_see_products_create_button()
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertDontSee('Add new product');
    }

    public function test_admin_can_access_product_create_page()
    {
        $response = $this->actingAs($this->admin)->get('/products/create');

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_product_create_page()
    {
        $response = $this->actingAs($this->user)->get('/products/create');

        $response->assertForbidden();
    }

    public function test_create_product_successful()
    {
        $product = [
            'name' => 'Product 123',
            'price' => 1234
        ];
        $response = $this->followingRedirects()->actingAs($this->admin)->post('/products', $product);

        $response->assertStatus(200);
        $response->assertSeeText($product['name']);

        $this->assertDatabaseHas('products', [
            'name' => 'Product 123',
            'price' => 123400
        ]);

        $lastProduct = Product::latest()->first();
        $this->assertEquals($product['name'], $lastProduct->name);
        $this->assertEquals($product['price'] * 100, $lastProduct->price);
    }

    public function test_product_edit_contains_correct_values()
    {
        $product = Product::factory()->create();
        $this->assertDatabaseHas('products', [
            'name' => $product->name,
            'price' => $product->price
        ]);
        $this->assertModelExists($product);

        $response = $this->actingAs($this->admin)->get('products/' . $product->id . '/edit');

        $response->assertOk();
        $response->assertSee('value="' . $product->name . '"', false);
        $response->assertSee('value="' . $product->price . '"', false);
        $response->assertViewHas('product', $product);
    }

    public function test_product_update_validation_error_redirects_back_to_form()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->put('products/' . $product->id, [
            'name' => '',
            'price' => ''
        ]);

        $response->assertStatus(302);
        $response->assertInvalid(['name', 'price']);
    }

    public function test_product_delete_successful()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->delete('products/' . $product->id);

        $response->assertStatus(302);
        $response->assertRedirect('products');

        $this->assertDatabaseMissing('products', $product->toArray());
        $this->assertModelMissing($product);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_api_returns_products_list()
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $response = $this->getJson('/api/products');

        $response->assertJsonFragment([
            'name' => $product1->name,
            'price' => $product1->price
        ]);
        $response->assertJsonCount(2, 'data');
    }

    public function test_api_product_store_successful()
    {
        $product = [
            'name' => 'Product 1',
            'price' => 123
        ];
        $response = $this->postJson('/api/products', $product);

        $response->assertCreated();
        $response->assertSuccessful(); // but not assertOk()
        $response->assertJson([
            'name' => 'Product 1',
            'price' => 12300
        ]);
    }

    public function test_api_product_invalid_store_returns_error()
    {
        $product = [
            'name' => '',
            'price' => 123
        ];
        $response = $this->postJson('/api/products', $product);

        $response->assertUnprocessable();
        $response->assertJsonMissingValidationErrors('price');
        $response->assertInvalid('name');
    }

    public function test_api_product_show_successful()
    {
        $productData = [
            'name' => 'Product 1',
            'price' => 123
        ];
        $product = Product::create($productData);

        $response = $this->getJson('/api/products/' . $product->id);
        $response->assertOk();
        $response->assertJsonPath('data.name', $productData['name']);
        $response->assertJsonMissingPath('data.created_at');
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'price',
            ]
        ]);
    }

    public function test_api_product_update_successful()
    {
        $productData = [
            'name' => 'Product 1',
            'price' => 123
        ];
        $product = Product::create($productData);

        $response = $this->putJson('/api/products/' . $product->id, [
            'name' => 'Product updated',
            'price' => 1234
        ]);
        $response->assertOk();
        $response->assertJsonMissing($productData);
    }

    public function test_api_product_delete_logged_in_admin()
    {
        $product = Product::factory()->create();
        $response = $this->actingAs($this->admin)->deleteJson('/api/products/' . $product->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('products', $product->toArray());
        $this->assertDatabaseCount('products', 0);
    }

    public function test_api_product_delete_restricted_by_auth()
    {
        $product = Product::factory()->create();
        $response = $this->deleteJson('/api/products/' . $product->id);

        $response->assertUnauthorized();
    }

    public function test_product_service_create_returns_product()
    {
        $product = (new ProductService())->create('Test product', 1234);

        $this->assertInstanceOf(Product::class, $product);
    }

    public function test_product_service_create_validation()
    {
        try {
            (new ProductService())->create('Too big', 1234567);
        } catch (\Exception $e) {
            $this->assertInstanceOf(NumberFormatException::class, $e);
        }
    }

    public function test_download_product_success()
    {
        $response = $this->get('/download');
        $response->assertOk();
        $response->assertHeader('Content-Disposition',
            'attachment; filename=product-specification.pdf');
    }

    public function test_product_shows_when_published_at_correct_time()
    {
        $product = Product::factory()->create([
            'published_at' => now()->addDay()->setTime(14, 00),
        ]);

        $this->freezeTime(function () use ($product) {
            $this->travelTo(now()->addDay()->setTime(14, 01));
            $response = $this->actingAs($this->user)->get('products');
            $response->assertSeeText($product->name);
        });

//        $response = $this->actingAs($this->user)->get('/products');
//        $response->assertDontSeeText($product->name);
    }

    public function test_artisan_publish_command_successful()
    {
        $this->artisan('product:publish 1')
            ->assertExitCode(-1)
            ->expectsOutput('Product not found');
    }

    public function test_job_product_publish_successful()
    {
        $product = Product::factory()->create();
        $this->assertNull($product->published_at);

        (new ProductPublishJob(2))->handle();

        $product->refresh();
        $this->assertNotNull($product->published_at);
    }
}
