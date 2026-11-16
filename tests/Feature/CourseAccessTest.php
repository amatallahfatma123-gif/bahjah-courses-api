<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CourseAccessTest extends TestCase
{
    use RefreshDatabase;

    private function actAsAdmin(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => UserStatus::APPROVED,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function actAsApprovedStudent(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::APPROVED,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    // ------------------------------------------------------------------
    // Published Courses Tests
    // ------------------------------------------------------------------

    public function test_an_approved_student_only_sees_published_courses(): void
    {
        $this->actAsApprovedStudent();
        Course::factory()->count(3)->published()->create();
        Course::factory()->count(2)->unpublished()->create();

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 3);

        foreach ($this->getJson('/api/courses')->json('data.items') as $item) {
            $this->assertTrue($item['is_published']);
        }
    }

    public function test_an_approved_student_cannot_see_an_unpublished_course(): void
    {
        $this->actAsApprovedStudent();
        $course = Course::factory()->unpublished()->create();

        $this->getJson("/api/courses/{$course->id}")
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found']);
    }

    public function test_an_approved_student_can_see_a_published_course(): void
    {
        $this->actAsApprovedStudent();
        $course = Course::factory()->published()->create();

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Course retrieved successfully'])
            ->assertJsonPath('data.id', $course->id);
    }

    // ------------------------------------------------------------------
    // Admin sees everything
    // ------------------------------------------------------------------

    public function test_an_admin_sees_all_courses_in_the_list(): void
    {
        $this->actAsAdmin();
        Course::factory()->count(3)->published()->create();
        Course::factory()->count(2)->unpublished()->create();

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 5);
    }

    public function test_an_admin_can_see_an_unpublished_course(): void
    {
        $this->actAsAdmin();
        $course = Course::factory()->unpublished()->create();

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.is_published', false);
    }

    // ------------------------------------------------------------------
    // Student cannot write
    // ------------------------------------------------------------------

    public function test_an_approved_student_cannot_create_a_course(): void
    {
        $this->actAsApprovedStudent();

        $this->postJson('/api/courses', ['title' => 'Unauthorized Course'])
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'Forbidden']);
    }

    public function test_an_approved_student_cannot_update_a_course(): void
    {
        $this->actAsApprovedStudent();
        $course = Course::factory()->create();

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Changed'])
            ->assertForbidden();
    }

    public function test_an_approved_student_cannot_delete_a_course(): void
    {
        $this->actAsApprovedStudent();
        $course = Course::factory()->create();

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertForbidden();
    }
}
