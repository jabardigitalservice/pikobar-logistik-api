<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class AreaTest extends TestCase
{
    // use RefreshDatabase;

    public function test_get_gities()
    {
        $response = $this->get('/api/v1/landing-page-registration/areas/cities');
        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_get_sub_area()
    {
        $response = $this->get('/api/v1/landing-page-registration/areas/subarea');
        $response->assertStatus(Response::HTTP_OK);
    }
}
