<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_a_short_lived_token_and_server_owned_role(): void
    {
        $user = User::factory()->create([
            'email' => 'student.login@example.test',
            'password' => 'Correct-Horse-42!',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'STUDENT.LOGIN@example.test',
            'password' => 'Correct-Horse-42!',
            'role' => User::ROLE_ADMIN,
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', User::ROLE_STUDENT)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'flutter-web',
        ]);
        $this->assertTrue(Hash::check('Correct-Horse-42!', $user->fresh()->password));
        $this->assertNotSame('Correct-Horse-42!', $user->fresh()->password);
    }

    public function test_wrong_password_and_unknown_email_return_the_same_safe_error(): void
    {
        User::factory()->create([
            'email' => 'known@example.test',
            'password' => 'Correct-Horse-42!',
        ]);

        foreach ([
            ['email' => 'known@example.test', 'password' => 'wrong'],
            ['email' => 'unknown@example.test', 'password' => 'wrong'],
        ] as $credentials) {
            $this->postJson('/api/v1/auth/login', $credentials)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
                ->assertJsonPath('error.message', 'The email or password is incorrect.');
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_requires_a_valid_email_and_password(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_repeated_failed_login_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.14'])
                ->postJson('/api/v1/auth/login', [
                    'email' => 'missing@example.test',
                    'password' => 'incorrect',
                ])
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.14'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'missing@example.test',
                'password' => 'incorrect',
            ])
            ->assertTooManyRequests();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.test',
            'password' => 'Correct-Horse-42!',
        ]);
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Correct-Horse-42!',
        ])->assertOk();
        $token = $login->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
