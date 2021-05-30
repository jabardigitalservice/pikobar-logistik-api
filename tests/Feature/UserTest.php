<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use App\User;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function testPostLoginSuccess()
    {
        $user = factory(User::class)->create([
            'username'    => 'username@example.net',
            'password' => bcrypt('secret'),
        ]);

        $response = $this->post('/api/v1/login', [
            'username'    => 'username@example.net',
            'password' => 'secret',
        ]);
        $response->assertStatus(Response::HTTP_OK);
    }
}
