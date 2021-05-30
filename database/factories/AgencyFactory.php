<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\Agency::class, function (Faker $faker) {
    return [
        'master_faskes_id' => rand(1, 6000),
        'agency_type' => rand(1, 5),
        'agency_name' => $faker->name,
        'phone_number' => $faker->numerify('02########'),
        'location_district_code' => '32.73',
        'location_subdistrict_code' => $faker->numerify('32.73.##'),
        'location_village_code' => $faker->numerify('32.73.##.####'),
        'location_address' => $faker->address,
        'completeness' => 1,
        'is_reference' => 1,
        'total_covid_patients' => rand(0, 100),
        'total_isolation_room' => rand(0, 100),
        'total_bedroom' => rand(0, 100),
        'total_health_worker' => rand(0, 100)
    ];
});
