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

class OutgoingLetterTest extends TestCase
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

    public function testStoreOutgoingLetter()
    {
        $letterRequest[] = [
            'applicant_id' => $this->agency->id
        ];
        $response = $this->actingAs($this->admin, 'api')->json('POST', '/api/v1/outgoing-letter', [
            'letter_name' => $this->faker->name,
            'letter_date' => date('Y-m-d H:i:s'),
            'letter_request' => json_encode($letterRequest),
        ]);
        $response->assertSuccessful();
    }

    public function testGetOutgoingLetter()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/outgoing-letter', [
            'sort' => 'desc',
            'limit' => 10,
            'letter_date' => date('Y-m-d H:i:s'),
            'letter_number' => $this->faker->name
        ]);
        $response->assertSuccessful();
    }

    public function testGetOutgoingLetterById()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/outgoing-letter/' . $this->outgoingLetter->id);
        $response->assertSuccessful();
    }
}
