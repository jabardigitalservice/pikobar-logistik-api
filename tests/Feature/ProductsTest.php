<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductsTest extends TestCase
{
    public function testGetProducts()
    {
        $response = $this->get('/api/v1/landing-page-registration/products');
        $response->assertStatus(200);
    }

    public function testGetProductById()
    {
        $productId = 1;
        $response = $this->get('/api/v1/landing-page-registration/product-unit/' . $productId);
        $response->assertStatus(200);
    }
}
