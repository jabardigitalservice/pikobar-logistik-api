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

$factory->define(App\OutgoingLetter::class, function (Faker $faker) {
    return [
        'user_id' => rand(),
        'letter_number' => $faker->numerify($faker->company . '/##/##'),
        'letter_name' => $faker->name,
        'letter_date' => date('Y-m-d H:i:s'),
        'status' => 'not_approved',
    ];
});
