<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class ProductsTest extends TestCase
{
    // use RefreshDatabase;

    public function testGetProducts()
    {
        $response = $this->get('/api/v1/landing-page-registration/products');
        $response->assertStatus(Response::HTTP_OK);
    }

    public function testGetProductById()
    {
        $productId = 1;
        $response = $this->get('/api/v1/landing-page-registration/product-unit/' . $productId);
        $response->assertStatus(Response::HTTP_OK);
    }
}
