<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_can_register(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
        ])
            ->assertCreated()
            ->assertJson(['success' => true, 'message' => 'User registered successfully'])
            ->assertJsonPath('data.user.email', 'ahmed@example.com');
    }

    public function test_a_new_user_is_created_as_a_student(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
        ])->assertCreated();

        $user = User::where('email', 'ahmed@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::STUDENT, $user->role);
    }

    public function test_a_new_user_is_created_as_pending(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
        ])->assertCreated();

        $user = User::where('email', 'ahmed@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserStatus::PENDING, $user->status);
    }

    public function test_a_user_cannot_set_their_own_role_during_registration(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_a_user_cannot_set_their_own_status_during_registration(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'status' => 'approved',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }
}
