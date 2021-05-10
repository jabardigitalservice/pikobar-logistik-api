<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use App\User;

class AcceptanceReportTest extends TestCase
{
    // use RefreshDatabase;

    public function test_realization_items_list()
    {
        $agencyId = 1661;
        $response = $this->get('/api/v1/logistic-report/realization-item/' . $agencyId);
        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_post_acceptance_report()
    {

    }
}
