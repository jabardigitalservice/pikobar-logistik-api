<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogisticRequestTest extends TestCase
{
    public function testGetLogisticRequestNoAuth()
    {
        $response = $this->get('/api/v1/logistic-request');
        $response->assertStatus(401);
    }

    public function testGetLogisticRequestByAgencyIdNoAuth()
    {
        $agencyId = 1661;
        $response = $this->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertStatus(401);
    }
}
