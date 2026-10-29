<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

final class UnifiedErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_method_that_is_not_allowed_returns_the_unified_405(): void
    {
        $this->deleteJson('/api/courses')
            ->assertStatus(405)
            ->assertHeader('Allow')
            ->assertJson(['success' => false, 'message' => 'Method Not Allowed', 'data' => null]);
    }

    public function test_an_unknown_api_path_returns_the_unified_404(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found', 'data' => null]);
    }

    public function test_an_unknown_api_path_returns_json_without_an_accept_header(): void
    {
        $this->get('/api/does-not-exist')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found', 'data' => null]);
    }

    public function test_a_protected_route_returns_401_json_without_an_accept_header(): void
    {
        $this->get('/api/courses')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);

        $this->post('/api/courses', ['title' => 'Laravel for Beginners'])
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);
    }

    public function test_a_validation_error_returns_422_json_without_an_accept_header(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/courses', [])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['title']);
    }

    public function test_an_unexpected_error_returns_a_generic_500_without_leaking_details(): void
    {
        config(['logging.default' => 'null']);

        Route::get('/api/test-boom', static function (): never {
            throw new RuntimeException('database password is hunter2');
        });

        $this->getJson('/api/test-boom')
            ->assertStatus(500)
            ->assertExactJson(['success' => false, 'message' => 'Server Error', 'data' => null]);
    }
}
