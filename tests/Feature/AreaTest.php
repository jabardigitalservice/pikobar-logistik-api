<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AreaTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = factory(User::class)->create();
    }

    public function test_get_gities()
    {
        $response = $this->json('GET', '/api/v1/landing-page-registration/areas/cities', [
            'city_code' => $this->faker->numerify('##.##')
        ]);
        $response->assertSuccessful();
    }

    public function test_get_sub_area()
    {
        $response = $this->json('GET', '/api/v1/landing-page-registration/areas/subarea', [
            'area_type' => 'subarea',
            'city_code' => $this->faker->numerify('##.##'),
            'kemendagri_kecamatan_nama' => $this->faker->state,
            'kemendagri_kabupaten_kode' => $this->faker->numerify('##.##'),
            'kemendagri_kecamatan_kode' => $this->faker->numerify('##.##.##'),
            'subdistrict_code' => $this->faker->numerify('##.##.##'),
        ]);
        $response->assertSuccessful();
    }

    public function test_get_sub_area_with_type_area_village()
    {
        $response = $this->json('GET', '/api/v1/landing-page-registration/areas/subarea', [
            'area_type' => 'village',
            'subdistrict_code' => $this->faker->numerify('##.##.##'),
            'kemendagri_desa_nama' => $this->faker->state,
            'kemendagri_kecamatan_kode' => $this->faker->numerify('##.##.##'),
            'kemendagri_desa_kode' => $this->faker->numerify('##.##.##.####'),
            'village_code' => $this->faker->numerify('##.##.##.####'),
        ]);
        $response->assertSuccessful();
    }

    public function test_get_city_total_request()
    {
        $response = $this->actingAs($this->admin, 'api')->json('GET', '/api/v1/logistic-request/cities/total-request', [
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s'),
            'sort' => 'asc',
        ]);
        $response->assertSuccessful();
    }
}
