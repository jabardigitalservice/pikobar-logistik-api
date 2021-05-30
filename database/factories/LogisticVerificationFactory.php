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

$factory->define(App\LogisticVerification::class, function (Faker $faker) {
    return [
        'agency_id' => factory(App\Agency::class),
        'email' => $faker->email,
        'token' => $faker->numerify('#####'),
        'expired_at' => date('Y-m-d H:i:s', strtotime('-1 days'))
    ];
});
