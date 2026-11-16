<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UnifiedErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ينشئ أدمن ويعمل login.
     */
    private function actAsAdmin(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => UserStatus::APPROVED,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_a_method_that_is_not_allowed_returns_the_unified_405(): void
    {
        $this->getJson('/api/register')
            ->assertStatus(405)
            ->assertJsonStructure(['success', 'message', 'data', 'errors']);
    }

    public function test_an_unknown_api_path_returns_the_unified_404(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
                'data' => null,
                'errors' => [],
            ]);
    }

    public function test_an_unknown_api_path_returns_json_without_an_accept_header(): void
    {
        $this->get('/api/does-not-exist')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_a_protected_route_returns_401_json_without_an_accept_header(): void
    {
        $this->get('/api/courses')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_a_validation_error_returns_422_json_without_an_accept_header(): void
    {
        $this->actAsAdmin();

        $this->post('/api/courses', [])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['title']);
    }

    public function test_an_unexpected_error_returns_a_generic_500_without_leaking_details(): void
    {
        // تسجيل route مؤقت بيرمي exception
        \Illuminate\Support\Facades\Route::get('/api/test-boom', function () {
            throw new \RuntimeException('Super secret internal detail');
        });

        $this->getJson('/api/test-boom')
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Server Error',
                'data' => null,
                'errors' => [],
            ])
            ->assertJsonMissing(['Super secret internal detail']);
    }
}
