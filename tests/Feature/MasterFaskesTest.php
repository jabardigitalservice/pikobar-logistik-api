<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use App\MasterFaskes;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MasterFaskesTest extends TestCase
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

    public function testGetMasterFaskes()
    {
        $response = $this->actingAs($this->admin, 'api')->get('/api/v1/master-faskes');
        $response->assertSuccessful();
    }

    public function testGetMasterFaskesById()
    {
        $response = $this->actingAs($this->admin, 'api')->get('/api/v1/master-faskes/' . $this->faskes->id);
        $response->assertSuccessful();
    }

    public function testStoreMasterFaskes()
    {
        Storage::fake('photos');

        $faskesName = 'FASKES ' . $this->faker->state . ' ' . $this->faker->company;
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/master-faskes', [
            'id_tipe_faskes' => rand(1, 3),
            'verification_status' => 'verified',
            'nama_faskes' => $faskesName,
            'poslog_id' => $this->faker->numerify('219000####'),
            'poslog_name' => $faskesName,
            'is_reference' => rand(0, 1),
            'nomor_izin_sarana' => $this->faker->numerify('####-####-##'),
            'nama_atasan' => $this->faker->name,
            'permit_file' => UploadedFile::fake()->image('permit_file.png'),
        ]);
        $response->assertSuccessful();
    }

    public function testPostVerifyingMasterFaskes()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/verify-master-faskes/' . $this->faskes->id, [
            'verification_status' => 'verified'
        ]);
        $response->assertSuccessful();
    }

    public function testPostRejectingMasterFaskes()
    {
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/verify-master-faskes/' . $this->faskes->id, [
            'verification_status' => 'rejected'
        ]);
        $response->assertSuccessful();
    }

    public function testGetMasterFaskesTypeList()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/master-faskes-type', [
            'is_imported' => rand(0, 1),
            'non_public' => rand(0, 1),
        ]);
        $response->assertSuccessful();
    }

    public function testGetFaskesTypeTotalRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/faskes-type-total-request');
        $response->assertSuccessful();
    }

    public function testGetFaskesTypeTopRequest()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/faskes-type-top-request');
        $response->assertSuccessful();
    }
}
