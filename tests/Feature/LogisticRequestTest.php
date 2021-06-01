<?php

namespace Tests\Feature;

use App\AcceptanceReport;
use App\Agency;
use App\Applicant;
use App\AuthKey;
use App\Enums\ApplicantStatusEnum;
use App\MasterFaskes;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class LogisticRequestTest extends TestCase
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

    public function testGetLogisticRequestNoAuth()
    {
        $response = $this->get('/api/v1/logistic-request');
        $response->assertUnauthorized();
    }

    public function testGetExportLogisticRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request/data/export');
        $response->assertSuccessful();
    }

    public function testGetLogisticRequestList()
    {
        $authKeys = factory(AuthKey::class)->create();
        $response = $this->json('GET', '/api/v1/logistic-request-list', [
            'is_integrated' => rand(0, 1),
            'cut_off_datetime' => date('Y-m-d H:i:s'),
        ], ['Api-Key' => $authKeys->token]);
        $response->assertSuccessful();
    }

    public function testGetLogisticRequestByAgencyIdNoAuth()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertUnauthorized();
    }

    public function testGetUnverifiedPhaseLogisticRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request', [
            'verification_status' => ApplicantStatusEnum::not_verified(),
            'approval_status' => ApplicantStatusEnum::not_approved(),
        ]);
        $response->assertSuccessful();
    }

    public function testGetRecommendationPhaseLogisticRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request', [
            'verification_status' => ApplicantStatusEnum::verified(),
            'approval_status' => ApplicantStatusEnum::not_approved(),
        ]);
        $response->assertSuccessful();
    }

    public function testGetRealizationPhaseLogisticRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request', [
            'verification_status' => ApplicantStatusEnum::verified(),
            'approval_status' => ApplicantStatusEnum::approved(),
            'finalized_by' => 0
        ]);
        $response->assertSuccessful();
    }

    public function testGetFinalizedLogisticRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request', [
            'finalized_by' => 1
        ]);
        $response->assertSuccessful();
    }

    public function testGetRejectedLogisticRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request', [
            'is_rejected' => 1
        ]);
        $response->assertSuccessful();
    }

    public function testGetLogisticRequestFilter()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request', [
            'is_reference' => rand(0, 1),
            'search' => $this->faker->name,
            'agency_name' => $this->faker->company,
            'city_code' => $this->faker->numerify('##.##'),
            'agency_type' => rand(1, 5),
            'completeness' => rand(0, 1),
            'source_data' => rand(0, 1),
            'stock_checking_status' => rand(0, 1),
            'is_urgency' => rand(0, 1),
            'is_integrated' => rand(0, 1),
            'status' => AcceptanceReport::STATUS_REPORTED,
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s'),
        ]);
        $response->assertSuccessful();
    }

    public function testGetLogisticRequestByAgencyId()
    {
        $agency = Agency::first();
        $agencyId = $agency->id;
        $response = $this->actingAs($this->admin, 'api')->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertSuccessful();
    }

    public function testGetLogisticRequestByAgencyIdNotAdmin()
    {
        $notAdmin = factory(User::class)->create(['roles' => 'dinkeskota']);

        $agency = Agency::first();
        $agencyId = $agency->id;
        $response = $this->actingAs($notAdmin, 'api')->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertUnauthorized();
    }

    public function testStoreLogisticRequestFaskes()
    {
        Storage::fake('photos');
        Mail::fake();
        Notification::fake();

        $logisticItems[] = [
            'usage' => $this->faker->text,
            'priority' => 'Menengah',
            'product_id' => rand(1,200),
            'brand' => $this->faker->text,
            'quantity' => rand(1,99999),
            'unit' => 'PCS'
        ];

        $response = $this->json('POST', '/api/v1/logistic-request', [
            'agency_type' => $this->faskes->id_tipe_faskes,
            'agency_name' => $this->faskes->nama_faskes,
            'phone_number' => $this->faker->numerify('081#########'),
            'location_district_code' => $this->faker->numerify('##.##'),
            'location_subdistrict_code' => $this->faker->numerify('##.##.##'),
            'location_village_code' => $this->faker->numerify('##.##.##.####'),
            'location_address' => $this->faker->address,
            'applicant_name' => $this->faker->name,
            'applicants_office' => $this->faker->jobTitle . ' ' . $this->faskes->nama_faskes,
            'email' => $this->faker->email,
            'primary_phone_number' => $this->faker->numerify('081#########'),
            'secondary_phone_number' => $this->faker->numerify('081#########'),
            'master_faskes_id' => $this->faskes->id,
            'logistic_request' => json_encode($logisticItems),
            'letter_file' => UploadedFile::fake()->image('letter_file.jpg'),
            'applicant_file' => UploadedFile::fake()->image('applicant_file.jpg'),
            'application_letter_number' => $this->faker->numerify('SURAT/' . date('Y/m/d') . '/' . $this->faker->company . '/####'),
            'total_covid_patients' => rand(0, 100),
            'total_isolation_room' => rand(0, 100),
            'total_bedroom' => rand(0, 100),
            'total_health_worker' => rand(0, 100)
        ]);
        $response->assertSuccessful();
    }

    public function testStoreLogisticRequestNonFaskes()
    {
        Storage::fake('photos');
        Mail::fake();
        Notification::fake();

        $logisticItems[] = [
            'usage' => $this->faker->text,
            'priority' => 'Menengah',
            'product_id' => rand(1,200),
            'brand' => $this->faker->text,
            'quantity' => rand(1,99999),
            'unit' => 'PCS'
        ];

        $response = $this->json('POST', '/api/v1/logistic-request', [
            'agency_type' => $this->nonFaskes->id_tipe_faskes,
            'agency_name' => $this->nonFaskes->nama_faskes,
            'phone_number' => $this->faker->numerify('081#########'),
            'location_district_code' => $this->faker->numerify('##.##'),
            'location_subdistrict_code' => $this->faker->numerify('##.##.##'),
            'location_village_code' => $this->faker->numerify('##.##.##.####'),
            'location_address' => $this->faker->address,
            'applicant_name' => $this->faker->name,
            'applicants_office' => $this->faker->jobTitle . ' ' . $this->nonFaskes->nama_faskes,
            'email' => $this->faker->email,
            'primary_phone_number' => $this->faker->numerify('081#########'),
            'secondary_phone_number' => $this->faker->numerify('081#########'),
            'master_faskes_id' => $this->nonFaskes->id,
            'logistic_request' => json_encode($logisticItems),
            'letter_file' => UploadedFile::fake()->image('letter_file.jpg'),
            'applicant_file' => UploadedFile::fake()->image('applicant_file.jpg'),
            'application_letter_number' => $this->faker->numerify('SURAT/' . date('Y/m/d') . '/' . $this->faker->company . '/####'),
            'total_covid_patients' => rand(0, 100),
            'total_isolation_room' => rand(0, 100),
            'total_bedroom' => rand(0, 100),
            'total_health_worker' => rand(0, 100)
        ]);
        $response->assertSuccessful();
    }

    public function testLogisticRequestNeeds()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request/need/list', [
            'page' => 1,
            'limit' => 10,
            'agency_id' => $this->agency->id,
        ]);
        $response->assertSuccessful();
    }

    public function testLogisticRequestSummary()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request-summary');
        $response->assertSuccessful();
    }

    public function testPostRequestVerifying()
    {
        Storage::fake('photos');
        Mail::fake();
        Notification::fake();

        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/verification', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'verification_status' => ApplicantStatusEnum::verified(),
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestApproval()
    {
        Storage::fake('photos');
        Mail::fake();
        Notification::fake();

        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/approval', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'approval_status' => ApplicantStatusEnum::approved(),
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestFinal()
    {
        Storage::fake('photos');
        Mail::fake();
        Notification::fake();

        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/final', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'approval_status' => ApplicantStatusEnum::approved(),
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestSetUrgency()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/urgency', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'is_urgency' => rand(0, 1),
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestReturnStatusFromFinalPhase()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/return', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'step' => 'final',
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestReturnStatusFromRealizationPhase()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/return', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'step' => 'realisasi',
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestReturnStatusFromRecommendationPhase()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/return', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'step' => 'rekomendasi',
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestReturnStatusFromRecommendationRejectedPhase()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/return', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'step' => 'ditolak rekomendasi',
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPostRequestReturnStatusFromVerificationRejectedPhase()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/return', [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'step' => 'ditolak verifikasi',
            'url' => 'http:://localhost/#',
        ]);
        $response->assertSuccessful();
    }

    public function testPutRequestUpdateAgencyData()
    {
        $response = $this->actingAs($this->admin, 'api')->json('PUT', '/api/v1/logistic-request/' . $this->applicant->agency_id, [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'master_faskes_id' => $this->faskes->id,
            'update_type' => 1
        ]);
        $response->assertSuccessful();
    }

    public function testPutRequestUpdateApplicantData()
    {
        Storage::fake('photos');

        $response = $this->actingAs($this->admin, 'api')->json('PUT', '/api/v1/logistic-request/' . $this->applicant->agency_id, [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'master_faskes_id' => $this->faskes->id,
            'applicant_file' => UploadedFile::fake()->image('applicant_file_update.jpg'),
            'update_type' => 2
        ]);
        $response->assertSuccessful();
    }

    public function testPutRequestUpdateLetterData()
    {
        Storage::fake('photos');

        $response = $this->actingAs($this->admin, 'api')->json('PUT', '/api/v1/logistic-request/' . $this->applicant->agency_id, [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'master_faskes_id' => $this->faskes->id,
            'letter_file' => UploadedFile::fake()->image('letter_file_update.jpg'),
            'update_type' => 3
        ]);
        $response->assertSuccessful();
    }

    public function testPostUploadLogisticRequestLetter()
    {
        Storage::fake('photos');

        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/letter/' . $this->applicant->agency_id, [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'master_faskes_id' => $this->faskes->id,
            'letter_file' => UploadedFile::fake()->image('letter_file_update.jpg'),
            'update_type' => 3
        ]);
        $response->assertSuccessful();
    }

    public function testPostUploadLogisticRequestApplicantFile()
    {
        Storage::fake('photos');

        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/logistic-request/identity/' . $this->applicant->agency_id, [
            'agency_id' => $this->applicant->id,
            'applicant_id' => $this->applicant->agency_id,
            'master_faskes_id' => $this->faskes->id,
            'applicant_file' => UploadedFile::fake()->image('applicant_file_update.jpg'),
            'update_type' => 2
        ]);
        $response->assertSuccessful();
    }
}
