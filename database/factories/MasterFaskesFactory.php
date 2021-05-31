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

$factory->define(App\MasterFaskes::class, function (Faker $faker) {
    $faskesName = 'FASKES ' . $faker->state . ' ' . $faker->company;
    return [
        'id_tipe_faskes' => rand(1, 3),
        'verification_status' => 'verified',
        'nama_faskes' => $faskesName,
        'poslog_id' => $faker->numerify('219000####'),
        'poslog_name' => $faskesName,
        'is_reference' => rand(0, 1),
        'nomor_izin_sarana' => $faker->numerify('####-####-##'),
        'nama_atasan' => $faker->name,
    ];
});
