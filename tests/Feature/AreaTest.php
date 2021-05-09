<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AreaTest extends TestCase
{
    public function testGetCities()
    {
        $response = $this->get('/api/v1/landing-page-registration/areas/cities');
        $response->assertStatus(200);
    }

    public function testGetSubArea()
    {
        $response = $this->get('/api/v1/landing-page-registration/areas/subarea');
        $response->assertStatus(200);
    }
}
