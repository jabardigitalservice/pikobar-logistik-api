<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use App\MasterFaskes;
use App\User;
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
        $this->admin = factory(User::class)->create();
        $this->faskes = factory(MasterFaskes::class)->create();
        $this->nonFaskes = factory(MasterFaskes::class)->create(['id_tipe_faskes' => rand(4, 5)]);
        $this->agency = factory(Agency::class)->create([
            'master_faskes_id' => $this->faskes->id,
            'agency_type' => $this->faskes->id_tipe_faskes,
        ]);
        $this->applicant = factory(Applicant::class)->create(['agency_id' => $this->agency->id]);
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

    public function testGetTrackingRequestPhaseItemsByAgencyId()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId . '/logistic-request');
        $response->assertSuccessful();
    }

    public function testGetTrackingRecommendationPhaseItemsByAgencyId()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId . '/logistic-recommendation');
        $response->assertSuccessful();
    }

    public function testGetTrackingFinalizationPhaseItemsByAgencyId()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId . '/logistic-finalization');
        $response->assertSuccessful();
    }

    public function testGetTrackingOutboundStogareListByAgencyId()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId . '/logistic-outbound');
        $response->assertSuccessful();
    }

    public function testGetTrackingoutboundPhaseItemsByAgencyId()
    {
        $loId = $this->faker->numerify('LO-A00000000000#####');
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/landing-page-registration/tracking/' . $agencyId . '/logistic-outbound/' . $loId);
        $response->assertSuccessful();
    }
}
