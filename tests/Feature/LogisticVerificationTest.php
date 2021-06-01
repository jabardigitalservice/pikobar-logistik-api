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
    use RefreshDatabase;

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
        $this->logisticVerification = factory(LogisticVerification::class)->create(['agency_id' => $this->agency->id]);
    }

    public function testVerificationCodeRegistration()
    {
        $response = $this->json('POST', '/api/v1/verification-registration', ['register_id' => $this->agency->id]);
        $response->assertStatus(Response::HTTP_OK);
    }

    public function testVerificationResendCode()
    {
        $response = $this->json('POST', '/api/v1/verification-resend', [
            'register_id' => $this->agency->id,
            'reset' => 1,
        ]);
        $response->assertStatus(Response::HTTP_OK);
    }

    public function testVerificationCodeRegistrationNotFound()
    {
        $agencyId = rand(10000, 99999);
        $response = $this->json('POST', '/api/v1/verification-registration', ['register_id' => $agencyId]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testVerificationConfirmationFail()
    {
        $response = $this->json('POST', '/api/v1/verification-confirmation', [
            'register_id' => $this->agency->id,
            'verification_code1' => rand(0,9),
            'verification_code2' => rand(0,9),
            'verification_code3' => rand(0,9),
            'verification_code4' => rand(0,9),
            'verification_code5' => rand(0,9)
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testVerificationConfirmationSuccess()
    {
        $token = $this->logisticVerification->token;
        $response = $this->json('POST', '/api/v1/verification-confirmation', [
            'register_id' => $this->agency->id,
            'verification_code1' => substr($token, 0, 1),
            'verification_code2' => substr($token, 1, 1),
            'verification_code3' => substr($token, 2, 1),
            'verification_code4' => substr($token, 3, 1),
            'verification_code5' => substr($token, 4, 1),
        ]);
        $response->assertStatus(Response::HTTP_OK);
    }
}
