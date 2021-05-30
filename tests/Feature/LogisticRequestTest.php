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
use Illuminate\Http\UploadedFile;

class LogisticRequestTest extends TestCase
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
        $this->applicant = factory(Applicant::class)->create(['agency_id' => $this->agency->id]);
    }

    public function test_get_logistic_request_no_auth()
    {
        $response = $this->get('/api/v1/logistic-request');
        $response->assertUnauthorized();
    }

    public function test_get_logistic_request_by_agency_id_no_auth()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertUnauthorized();
    }

    public function test_get_logistic_request()
    {
        $admin = factory(User::class)->create();
        $response = $this->actingAs($admin, 'api')->get('/api/v1/logistic-request');
        $response->assertSuccessful();
    }

    public function test_get_logistic_request_by_agency_id()
    {
        $admin = factory(User::class)->create();
        $agency = Agency::first();
        $agencyId = $agency->id;
        $response = $this->actingAs($admin, 'api')->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertSuccessful();
    }

    public function test_get_logistic_request_by_agency_id_not_admin()
    {
        $notAdmin = factory(User::class)->create(['roles' => 'dinkeskota']);

        $agency = Agency::first();
        $agencyId = $agency->id;
        $response = $this->actingAs($notAdmin, 'api')->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertUnauthorized();
    }

    public function test_store_logistic_request()
    {
        $logisticItems[] = [
            'usage' => $this->faker->text,
            'priority' => 'Menengah',
            'product_id' => rand(1,200),
            'brand' => $this->faker->text,
            'quantity' => rand(1,99999),
            'unit' => 'PCS'
        ];

        $response = $this->json('POST', '/api/v1/logistic-request', [
            'agency_type' => $this->masterFaskes->id_tipe_faskes,
            'agency_name' => $this->masterFaskes->nama_faskes,
            'phone_number' => $this->faker->numerify('081#########'),
            'location_district_code' => $this->faker->numerify('##.##'),
            'location_subdistrict_code' => $this->faker->numerify('##.##.##'),
            'location_village_code' => $this->faker->numerify('##.##.##.####'),
            'location_address' => $this->faker->address,
            'applicant_name' => $this->faker->name,
            'applicants_office' => $this->faker->jobTitle . ' ' . $this->masterFaskes->nama_faskes,
            'email' => $this->faker->email,
            'primary_phone_number' => $this->faker->numerify('081#########'),
            'secondary_phone_number' => $this->faker->numerify('081#########'),
            'master_faskes_id' => $this->masterFaskes->id,
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
}
