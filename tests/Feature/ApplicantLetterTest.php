<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use App\MasterFaskes;
use App\OutgoingLetter;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicantLetterTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = factory(User::class)->create();
        $this->faskes = factory(MasterFaskes::class)->create();
        $this->agency = factory(Agency::class)->create([
            'master_faskes_id' => $this->faskes->id,
            'agency_type' => $this->faskes->id_tipe_faskes,
        ]);
        $this->applicant = factory(Applicant::class)->create(['agency_id' => $this->agency->id]);
        $this->outgoingLetter = factory(OutgoingLetter::class)->create(['user_id' => $this->admin->id]);
    }

    public function testGetApplicantLetter()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/application-letter', [
            'outgoing_letter_id' => $this->outgoingLetter->id
        ]);
        $response->assertSuccessful();
    }

    public function testGetSearchByLetterNumberApplicantLetter()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/application-letter/search-by-letter-number', [
            'request_letter_id' => rand(),
            'application_letter_number' => $this->faker->name
        ]);
        $response->assertSuccessful();
    }

    public function testGetApplicantLetterById()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/application-letter/' . rand());
        $response->assertSuccessful();
    }

    public function testStoreApplicantLetter()
    {
        $letterRequest [] = [
            'applicant_id' => $this->agency->id
        ];
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/application-letter', [
            'outgoing_letter_id' => $this->outgoingLetter->id,
            'letter_request' => json_encode($letterRequest),
        ]);
        $response->assertSuccessful();
    }
}
