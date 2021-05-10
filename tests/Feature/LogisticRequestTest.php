<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class LogisticRequestTest extends TestCase
{
    // use RefreshDatabase;

    public function test_get_logistic_request_no_auth()
    {
        $response = $this->get('/api/v1/logistic-request');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_get_logistic_request_by_agency_id_no_auth()
    {
        $agencyId = 1661;
        $response = $this->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
