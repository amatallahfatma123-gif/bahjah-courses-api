<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password123';

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'password' => self::PASSWORD,
        ], $overrides);
    }

    private function exhaustRateLimit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email' => 'nobody@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }
    }

    public function test_register_creates_a_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/register', $this->registrationData());

        $response->assertCreated()
            ->assertJson(['success' => true, 'message' => 'User registered successfully'])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'email', 'created_at'], 'token', 'token_type'],
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonMissingPath('data.user.password');

        $user = User::query()->where('email', 'ali@example.com')->firstOrFail();

        $this->assertNotSame(self::PASSWORD, $user->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_rejects_an_empty_body(): void
    {
        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ali@example.com']);

        $this->postJson('/api/register', $this->registrationData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_register_rejects_a_short_password(): void
    {
        $this->postJson('/api/register', $this->registrationData(['password' => 'short']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_register_rejects_an_invalid_email(): void
    {
        $this->postJson('/api/register', $this->registrationData(['email' => 'not-an-email']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        User::factory()->create(['email' => 'ali@example.com', 'password' => self::PASSWORD]);

        $this->postJson('/api/login', ['email' => 'ali@example.com', 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Login successful'])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'email', 'created_at'], 'token', 'token_type'],
            ])
            ->assertJsonPath('data.user.email', 'ali@example.com')
            ->assertJsonMissingPath('data.user.password');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        User::factory()->create(['email' => 'ali@example.com', 'password' => self::PASSWORD]);

        $this->postJson('/api/login', ['email' => 'ali@example.com', 'password' => 'wrong-password'])
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Invalid credentials', 'data' => null]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_gives_the_same_error_for_an_unknown_email(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => self::PASSWORD])
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Invalid credentials', 'data' => null]);
    }

    public function test_login_rejects_an_empty_body(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_deletes_the_current_token_and_the_token_stops_working(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device-a')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Logged out successfully', 'data' => null]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->postJson('/api/logout')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $tokenA = $user->createToken('device-a')->plainTextToken;
        $user->createToken('device-b');

        $this->withToken($tokenA)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'device-b']);
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'device-a']);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);
    }

    public function test_the_eleventh_login_attempt_in_a_minute_is_rate_limited(): void
    {
        $this->exhaustRateLimit();

        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJson(['success' => false, 'message' => 'Too Many Requests', 'data' => null]);
    }

    public function test_the_rate_limit_is_shared_between_login_and_register(): void
    {
        $this->exhaustRateLimit();

        $this->postJson('/api/register', $this->registrationData())
            ->assertTooManyRequests();

        $this->assertDatabaseCount('users', 0);
    }
}
