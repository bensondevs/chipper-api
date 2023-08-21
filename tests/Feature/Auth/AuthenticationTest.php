<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AuthenticationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_user_can_login_with_email_and_password(): void
    {
        $user = User::factory()->create();

        $response = $this->json('POST', route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['name', 'email'], 'token']);
    }

    public function test_a_users_can_not_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->json('POST', route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    public function test_a_user_can_logout()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', route('logout'));

        $response->assertNoContent();
    }
}
