<?php

use App\Enums\ApplicantStatusEnum;
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

$factory->define(App\Applicant::class, function (Faker $faker) {
    return [
        'agency_id' => factory(App\Agency::class),
        'applicant_name' => $faker->name,
        'applicants_office' => $faker->jobTitle . ' ' . $faker->company,
        'file' => rand(1, 9999),
        'email' => $faker->email,
        'primary_phone_number' => $faker->phoneNumber,
        'secondary_phone_number' => $faker->phoneNumber,
        'verification_status' => ApplicantStatusEnum::not_verified(),
        'approval_status' => ApplicantStatusEnum::not_approved(),
        'application_letter_number' => $faker->numerify('SURAT/' . date('Y/m/d') . '/' . $faker->company . '/####'),
        'source_data' => 'pikobar',
        'created_by' => factory(App\User::class),
        'is_urgency' => rand(0, 1)
    ];
});
