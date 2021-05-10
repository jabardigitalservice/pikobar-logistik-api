<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrackingTest extends TestCase
{
    public function testGetTracking()
    {
        $response = $this->get('/api/v1/landing-page-registration/tracking');
        $response->assertStatus(200);
    }

    public function testGetTrackingByAgencyId()
    {
        $agencyId = 1661;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId);
        $response->assertStatus(200);
    }

    public function testGetTrackingByEmail()
    {
        $email = 'budiaramdhanrindi@gmail.com';
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $email);
        $response->assertStatus(200);
    }

    public function testGetTrackingByPhone()
    {
        $phone = '081809556334';
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $phone);
        $response->assertStatus(200);
    }
}
