<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class TrackingTest extends TestCase
{
    // use RefreshDatabase;

    public function testGetTracking()
    {
        $response = $this->get('/api/v1/landing-page-registration/tracking');
        $response->assertStatus(Response::HTTP_OK);
    }

    public function testGetTrackingByAgencyId()
    {
        $agencyId = 1661;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId);
        $response->assertStatus(Response::HTTP_OK);
    }

    public function testGetTrackingByEmail()
    {
        $email = 'budiaramdhanrindi@gmail.com';
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $email);
        $response->assertStatus(Response::HTTP_OK);
    }

    public function testGetTrackingByPhone()
    {
        $phone = '081809556334';
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $phone);
        $response->assertStatus(Response::HTTP_OK);
    }
}
