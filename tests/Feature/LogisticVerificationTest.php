<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use App\Enums\ApplicantStatusEnum;
use App\LogisticVerification;
use App\MasterFaskes;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class LogisticVerificationTest extends TestCase
{
    use WithFaker;
    // use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->masterFaskes = factory(MasterFaskes::class)->create();

        $this->agency = factory(Agency::class)->create([
            'master_faskes_id' => $this->masterFaskes->id,
            'agency_type' => $this->masterFaskes->id_tipe_faskes,
        ]);

        $this->applicant = factory(Applicant::class)->create([
            'agency_id' => $this->agency->id,
            'verification_status' => ApplicantStatusEnum::verified(),
            'approval_status' => ApplicantStatusEnum::approved(),
        ]);
    }

    public function test_verification_code_registration()
    {
        $agency = Agency::first();
        $response = $this->json('POST', '/api/v1/verification-registration', ['register_id' => $agency->id]);
        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_verification_resend_code()
    {
        $agency = Agency::first();
        $response = $this->json('POST', '/api/v1/verification-resend', ['register_id' => $agency->id]);
        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_verification_confirmation_fail()
    {
        $agency = Agency::first();
        $response = $this->json('POST', '/api/v1/verification-confirmation', [
            'register_id' => $agency->id,
            'verification_code1' => rand(0,9),
            'verification_code2' => rand(0,9),
            'verification_code3' => rand(0,9),
            'verification_code4' => rand(0,9),
            'verification_code5' => rand(0,9)
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_verification_confirmation_success()
    {
        $agency = Agency::first();
        $this->logisticVerification = factory(LogisticVerification::class)->create(['agency_id' => $agency->id]);
        $verification = LogisticVerification::first();
        $token = $verification->token;
        $response = $this->json('POST', '/api/v1/verification-confirmation', [
            'register_id' => $agency->id,
            'verification_code1' => substr($token, 0, 1),
            'verification_code2' => substr($token, 1, 1),
            'verification_code3' => substr($token, 2, 1),
            'verification_code4' => substr($token, 3, 1),
            'verification_code5' => substr($token, 4, 1),
        ]);
        $response->assertStatus(Response::HTTP_OK);
    }
}
