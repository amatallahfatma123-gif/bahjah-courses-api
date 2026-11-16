<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StudentApprovalTest extends TestCase
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

    private function actAsPendingStudent(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::PENDING,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    // ------------------------------------------------------------------
    // Pending Student Tests
    // ------------------------------------------------------------------

    public function test_a_pending_student_can_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::PENDING,
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_a_pending_student_cannot_access_courses(): void
    {
        $this->actAsPendingStudent();

        $this->getJson('/api/courses')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Your account is pending admin approval.',
                'data' => null,
            ]);
    }

    public function test_a_pending_student_gets_403_when_reading_a_single_course(): void
    {
        $this->actAsPendingStudent();
        $course = \App\Models\Course::factory()->published()->create();

        $this->getJson("/api/courses/{$course->id}")
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Your account is pending admin approval.',
            ]);
    }

    // ------------------------------------------------------------------
    // Admin Approval Tests
    // ------------------------------------------------------------------

    public function test_an_admin_can_list_students(): void
    {
        $this->actAsAdmin();
        User::factory()->count(3)->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::PENDING,
        ]);

        $this->getJson('/api/students')
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Students retrieved successfully'])
            ->assertJsonStructure([
                'data' => [
                    'items' => ['*' => ['id', 'name', 'email', 'status', 'created_at']],
                    'meta',
                ],
            ]);
    }

    public function test_an_admin_can_approve_a_pending_student(): void
    {
        $this->actAsAdmin();
        $student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::PENDING,
        ]);

        $this->patchJson("/api/students/{$student->id}/approve")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Student approved successfully.'])
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'status' => 'approved',
        ]);
    }

    public function test_an_approved_student_can_access_courses(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::APPROVED,
        ]);
        Sanctum::actingAs($student);
        \App\Models\Course::factory()->count(3)->published()->create();

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.meta.total', 3);
    }

    public function test_approving_an_already_approved_student_returns_400(): void
    {
        $this->actAsAdmin();
        $student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::APPROVED,
        ]);

        $this->patchJson("/api/students/{$student->id}/approve")
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Student is already approved.']);
    }

    public function test_approving_an_admin_returns_400(): void
    {
        $this->actAsAdmin();
        $otherAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => UserStatus::APPROVED,
        ]);

        $this->patchJson("/api/students/{$otherAdmin->id}/approve")
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'The specified user is not a student.']);
    }

    // ------------------------------------------------------------------
    // Authorization Tests
    // ------------------------------------------------------------------

    public function test_a_student_cannot_list_students(): void
    {
        $this->actAsPendingStudent();

        $this->getJson('/api/students')
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'Admin access required.']);
    }

    public function test_a_student_cannot_approve_another_student(): void
    {
        $this->actAsPendingStudent();
        $other = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::PENDING,
        ]);

        $this->patchJson("/api/students/{$other->id}/approve")
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'Admin access required.']);
    }
}
