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

$factory->define(App\User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'username' => $faker->userName,
        'name_district_city' => $faker->state,
        'code_district_city' => $faker->numerify('##.##'),
        'roles' => 'dinkesprov',
        'agency_name' => 'Dinas Kesehatan',
        'email' => $faker->email,
        'password' => bcrypt('secret'), // secret
        'handphone' => $faker->phoneNumber,
        'phase' => 'surat',
    ];
});
