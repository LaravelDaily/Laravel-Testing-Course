<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    public function test_product_price_set_successfully()
    {
        $product = new Product([
            'name' => 'Whatever',
            'price' => 1.23
        ]);

        $this->assertEquals(123, $product->price);
    }
}
