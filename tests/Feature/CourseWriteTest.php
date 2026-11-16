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

final class CourseWriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ينشئ طالب معتمد (approved student) ويعمل login.
     */
    private function actAsUser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::STUDENT,
            'status' => UserStatus::APPROVED,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function courseOwnedBy(User $user, array $attributes = []): Course
    {
        return Course::factory()->create(['user_id' => $user->id, ...$attributes]);
    }

    // ------------------------------------------------------------------
    // Create (Admin only)
    // ------------------------------------------------------------------

    public function test_creating_a_course_requires_authentication(): void
    {
        $this->postJson('/api/courses', ['title' => 'Laravel for Beginners'])
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_creating_a_course_returns_the_created_envelope_and_owner(): void
    {
        $admin = $this->actAsAdmin();

        $this->postJson('/api/courses', [
            'title' => 'Laravel for Beginners',
            'description' => 'Start here.',
            'price' => 49.5,
            'is_published' => true,
        ])
            ->assertCreated()
            ->assertJson(['success' => true, 'message' => 'Course created successfully'])
            ->assertJsonPath('data.user_id', $admin->id)
            ->assertJsonPath('data.title', 'Laravel for Beginners')
            ->assertJsonPath('data.description', 'Start here.')
            ->assertJsonPath('data.price', 49.5)
            ->assertJsonPath('data.is_published', true);

        $this->assertDatabaseHas('courses', [
            'user_id' => $admin->id,
            'title' => 'Laravel for Beginners',
            'price' => 49.5,
            'is_published' => true,
        ]);
    }

    public function test_creating_a_course_with_only_a_title_applies_the_defaults(): void
    {
        $admin = $this->actAsAdmin();

        $this->postJson('/api/courses', ['title' => 'Only a title'])
            ->assertCreated()
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.is_published', false);

        $this->assertDatabaseHas('courses', [
            'user_id' => $admin->id,
            'title' => 'Only a title',
            'price' => 0,
            'is_published' => false,
        ]);
    }

    public function test_creating_a_course_ignores_a_user_id_sent_in_the_body(): void
    {
        $admin = $this->actAsAdmin();
        $other = User::factory()->create();

        $this->postJson('/api/courses', ['title' => 'Spoofed owner', 'user_id' => $other->id])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $admin->id);

        $this->assertDatabaseHas('courses', ['title' => 'Spoofed owner', 'user_id' => $admin->id]);
        $this->assertDatabaseMissing('courses', ['user_id' => $other->id]);
    }

    public function test_creating_a_course_rejects_an_empty_body(): void
    {
        $this->actAsAdmin();

        $this->postJson('/api/courses', [])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_creating_a_course_rejects_an_invalid_title(): void
    {
        $this->actAsAdmin();

        $this->postJson('/api/courses', ['title' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->postJson('/api/courses', ['title' => ['not', 'a', 'string']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_creating_a_course_rejects_an_invalid_price(): void
    {
        $this->actAsAdmin();

        foreach ([-1, 'free', 1000000] as $price) {
            $this->postJson('/api/courses', ['title' => 'Priced', 'price' => $price])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['price']);
        }

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_creating_a_course_rejects_an_invalid_published_flag(): void
    {
        $this->actAsAdmin();

        $this->postJson('/api/courses', ['title' => 'Flagged', 'is_published' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_published']);

        $this->assertDatabaseCount('courses', 0);
    }

    // ------------------------------------------------------------------
    // Update (Admin only)
    // ------------------------------------------------------------------

    public function test_updating_a_course_requires_authentication(): void
    {
        $course = Course::factory()->create(['title' => 'Original']);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Changed'])
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original']);
    }

    public function test_an_admin_can_update_a_course_with_put(): void
    {
        $admin = $this->actAsAdmin();
        $course = $this->courseOwnedBy($admin, ['title' => 'Original', 'price' => 10, 'is_published' => false]);

        $this->putJson("/api/courses/{$course->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'price' => 75.5,
            'is_published' => true,
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Course updated successfully'])
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.user_id', $admin->id)
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.price', 75.5)
            ->assertJsonPath('data.is_published', true);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated title',
            'price' => 75.5,
            'is_published' => true,
        ]);
    }

    public function test_an_admin_can_partially_update_a_course_with_patch(): void
    {
        $admin = $this->actAsAdmin();
        $course = $this->courseOwnedBy($admin, ['title' => 'Keep this title', 'price' => 20, 'is_published' => false]);

        $this->patchJson("/api/courses/{$course->id}", ['is_published' => true])
            ->assertOk()
            ->assertJsonPath('data.is_published', true)
            ->assertJsonPath('data.title', 'Keep this title');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Keep this title',
            'price' => 20,
            'is_published' => true,
        ]);
    }

    public function test_updating_a_course_cannot_change_its_owner(): void
    {
        $admin = $this->actAsAdmin();
        $other = User::factory()->create();
        $course = $this->courseOwnedBy($admin);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Still mine', 'user_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('data.user_id', $admin->id);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'user_id' => $admin->id]);
    }

    public function test_updating_a_course_rejects_invalid_data_and_changes_nothing(): void
    {
        $admin = $this->actAsAdmin();
        $course = $this->courseOwnedBy($admin, ['title' => 'Original', 'price' => 10]);

        $this->putJson("/api/courses/{$course->id}", ['title' => '', 'price' => -5])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['title', 'price']);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original', 'price' => 10]);
    }

    public function test_updating_an_unknown_course_returns_404(): void
    {
        $this->actAsAdmin();

        $this->putJson('/api/courses/999999', ['title' => 'Nothing'])
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found', 'data' => null]);
    }

    // ------------------------------------------------------------------
    // Update by a student (forbidden)
    // ------------------------------------------------------------------

    public function test_a_student_cannot_update_a_course_with_put(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create(['title' => 'Original', 'price' => 10]);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Hacked', 'price' => 1])
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'Forbidden', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original', 'price' => 10]);
    }

    public function test_a_student_cannot_update_a_course_with_patch(): void
    {
        $this->actAsUser();
        $course = Course::factory()->unpublished()->create();

        $this->patchJson("/api/courses/{$course->id}", ['is_published' => true])
            ->assertForbidden()
            ->assertJson(['success' => false, 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'is_published' => false]);
    }

    public function test_a_student_gets_403_even_when_the_body_is_invalid(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create(['title' => 'Original']);

        $this->putJson("/api/courses/{$course->id}", ['title' => ''])
            ->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original']);
    }

    // ------------------------------------------------------------------
    // Delete (Admin only)
    // ------------------------------------------------------------------

    public function test_deleting_a_course_requires_authentication(): void
    {
        $course = Course::factory()->create();

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_an_admin_can_delete_a_course(): void
    {
        $admin = $this->actAsAdmin();
        $course = $this->courseOwnedBy($admin);

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Course deleted successfully']);

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);

        $this->getJson("/api/courses/{$course->id}")->assertNotFound();
    }

    public function test_deleting_a_course_leaves_the_other_courses_untouched(): void
    {
        $admin = $this->actAsAdmin();
        $target = $this->courseOwnedBy($admin);
        $mine = $this->courseOwnedBy($admin);
        $theirs = Course::factory()->create();

        $this->deleteJson("/api/courses/{$target->id}")->assertOk();

        $this->assertDatabaseMissing('courses', ['id' => $target->id]);
        $this->assertDatabaseHas('courses', ['id' => $mine->id]);
        $this->assertDatabaseHas('courses', ['id' => $theirs->id]);
    }

    public function test_a_student_cannot_delete_a_course(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create();

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'Forbidden', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_an_admin_can_still_delete_after_a_student_was_refused(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => UserStatus::APPROVED,
        ]);
        $course = $this->courseOwnedBy($admin);

        $this->actAsUser();
        $this->deleteJson("/api/courses/{$course->id}")->assertForbidden();

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/courses/{$course->id}")->assertOk();

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_deleting_an_unknown_course_returns_404(): void
    {
        $this->actAsAdmin();

        $this->deleteJson('/api/courses/999999')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found', 'data' => null]);
    }

    // ------------------------------------------------------------------
    // Courses without an owner (user_id is null, for example after the owner's account is deleted)
    // ------------------------------------------------------------------

    public function test_nobody_can_update_a_course_that_has_no_owner(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create(['user_id' => null, 'title' => 'Orphan']);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Claimed'])
            ->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Orphan']);
    }

    public function test_nobody_can_delete_a_course_that_has_no_owner(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create(['user_id' => null]);

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}
