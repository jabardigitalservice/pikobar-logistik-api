<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use App\MasterFaskes;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IncomingLetterTest extends TestCase
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

    public function testGetIncomingLetterWhereMailStatusExists()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/incoming-letter', [
            'sort' => 'desc',
            'letter_date' => date('Y-m-d H:i:s'),
            'district_code' => $this->faker->numerify('##.##'),
            'agency_type' => rand(1, 5),
            'letter_number' => $this->faker->name,
            'mail_status' => 'exists',
        ]);
        $response->assertSuccessful();
    }

    public function testGetIncomingLetterWhereMailStatusNotExists()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/incoming-letter', [
            'sort' => 'desc',
            'letter_date' => date('Y-m-d H:i:s'),
            'district_code' => $this->faker->numerify('##.##'),
            'agency_type' => rand(1, 5),
            'letter_number' => $this->faker->name,
            'mail_status' => 'not exists',
        ]);
        $response->assertSuccessful();
    }
}
