<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use App\LogisticRealizationItems;
use App\MasterFaskes;
use App\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AcceptanceReportTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = factory(User::class)->create();
        $this->masterFaskes = factory(MasterFaskes::class)->create();
        $this->agency = factory(Agency::class)->create([
            'master_faskes_id' => $this->masterFaskes->id,
            'agency_type' => $this->masterFaskes->id_tipe_faskes,
        ]);
        $this->applicant = factory(Applicant::class)->create(['agency_id' => $this->agency->id]);
    }

    public function testGetAcceptanceReport()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/acceptance-report', [
            'search' => $this->faker->name,
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s'),
            'status' => rand(0, 1),
            'city_code' => $this->faker->numerify('##.##'),
        ]);
        $response->assertSuccessful();
    }

    public function testStoreAcceptanceReport()
    {
        Storage::fake('photos');

        $qty = rand(1000,10000);
        $qty_ok = $qty - rand(10, 100);
        $items[] = [
            'id' => rand(),
            'product_id' => rand(1,200),
            'name' => $this->faker->name,
            'qty' => $qty,
            'unit' => 'PCS',
            'status' => LogisticRealizationItems::STATUS_APPROVED,
            'qty_ok' => $qty_ok,
            'qty_nok' => $qty - $qty_ok,
            'quality' => 'Dapat Dipakai'
        ];

        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/acceptance-report', [
            'fullname' => $this->faker->name,
            'position' => $this->faker->jobTitle,
            'phone' => $this->faker->phoneNumber,
            'date' => date('Y-m-d H:i:s'),
            'officer_fullname' => $this->faker->name,
            'note' => $this->faker->text,
            'agency_id' => $this->agency->id,
            'items' => json_encode($items),
            'proof_pic0' => UploadedFile::fake()->image('proof_pic.png'),
            'proof_pic_length' => 1,
            'bast_proof0' => UploadedFile::fake()->image('bast_proof.png'),
            'bast_proof_length' => 1,
            'item_proof0' => UploadedFile::fake()->image('item_proof.png'),
            'item_proof_length' => 1,
            'feedback' => $this->faker->text,
        ]);

        $response->assertSuccessful();
    }

    public function testStoreAcceptanceReportFailItemFormat()
    {
        $qty = rand(1000,10000);
        $qty_ok = $qty - rand(10, 100);
        $items[] = [
            'id' => rand(),
            'product_id' => rand(1,200),
            'name' => $this->faker->text,
            'qty' => $qty,
            'unit' => 'PCS',
            'status' => LogisticRealizationItems::STATUS_APPROVED,
            'qty_ok' => $qty_ok,
            'qty_nok' => $qty - $qty_ok,
            'quality' => 'Dapat Dipakai'
        ];

        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/acceptance-report', [
            'fullname' => $this->faker->name,
            'position' => $this->faker->jobTitle,
            'phone' => $this->faker->phoneNumber,
            'date' => date('Y-m-d H:i:s'),
            'officer_fullname' => $this->faker->name,
            'note' => $this->faker->text,
            'agency_id' => $this->agency->id,
            'items' => $items,
            'proof_pic0' => UploadedFile::fake()->image('proof_pic.png'),
            'proof_pic_length' => 1,
            'bast_proof0' => UploadedFile::fake()->image('bast_proof.png'),
            'bast_proof_length' => 1,
            'item_proof0' => UploadedFile::fake()->image('item_proof.png'),
            'item_proof_length' => 1,
            'feedback' => $this->faker->text,
        ]);
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function testGetAcceptanceReportById()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/acceptance-report/' . $this->agency->id);
        $response->assertSuccessful();
    }

    public function testGetAcceptanceReportStatistic()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/acceptance-report-statistic', [
            'city_code' =>$this->faker->numerify('##.##')
        ]);
        $response->assertSuccessful();
    }

    public function testGetAcceptanceReportEvidence()
    {
        $id = rand();
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/acceptance-report-evidence', [
            'acceptance_report_id' => $id
        ]);
        $response->assertSuccessful();
    }

    public function testGetAcceptanceReportDetail()
    {
        $id = rand();
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/acceptance-report-detail', [
            'acceptance_report_id' => $id
        ]);
        $response->assertSuccessful();
    }

    public function testGetAcceptanceReportRealizationLogisticList()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-report/realization-item/' . $this->agency->id);
        $response->assertSuccessful();
    }
}
