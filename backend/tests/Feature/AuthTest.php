<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_returns_a_token_that_identifies_the_new_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
        ]);

        $response->assertCreated()->assertJsonPath('data.user.email', 'ada@example.com');
        $this->assertNotSame('correct-horse', User::first()->password, 'The password must be stored hashed.');

        $this->withToken($response->json('data.token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.name', 'Ada');
    }

    public function test_registration_is_validated(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_logging_in_with_the_right_password_returns_a_token(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct-horse']);

        $this->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'correct-horse'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);
    }

    public function test_a_wrong_password_and_an_unknown_email_get_the_same_answer(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct-horse']);

        $wrongPassword = $this->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'wrong-horse']);
        $unknownEmail = $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'correct-horse']);

        $wrongPassword->assertUnprocessable();
        $unknownEmail->assertUnprocessable();
        $this->assertSame($wrongPassword->json('errors'), $unknownEmail->json('errors'));
    }

    public function test_logging_out_revokes_the_token(): void
    {
        $token = User::factory()->create()->createToken('web')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        // A fresh application instance, so the guard does not reuse the user it resolved before.
        $this->refreshApplication();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_the_current_user_requires_a_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
