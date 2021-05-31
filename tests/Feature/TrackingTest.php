<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class TrackingTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->agency = factory(Agency::class)->create();
        $this->applicant = factory(Applicant::class)->create();
    }

    public function testGetTracking()
    {
        $response = $this->get('/api/v1/landing-page-registration/tracking');
        $response->assertSuccessful();
    }

    public function testGetTrackingByAgencyId()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId);
        $response->assertSuccessful();
    }

    public function testGetTrackingByEmail()
    {
        $email = $this->applicant->email;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $email);
        $response->assertSuccessful();
    }

    public function testGetTrackingByPhone()
    {
        $phone = $this->agency->phone_number;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $phone);
        $response->assertSuccessful();
    }
}
