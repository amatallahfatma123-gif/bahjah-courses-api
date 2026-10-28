<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CourseWriteTest extends TestCase
{
    use RefreshDatabase;

    private function actAsUser(): User
    {
        $user = User::factory()->create();
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
    // Create
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
        $user = $this->actAsUser();

        $this->postJson('/api/courses', [
            'title' => 'Laravel for Beginners',
            'description' => 'Start here.',
            'price' => 49.5,
            'is_published' => true,
        ])
            ->assertCreated()
            ->assertJson(['success' => true, 'message' => 'Course created successfully'])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.title', 'Laravel for Beginners')
            ->assertJsonPath('data.description', 'Start here.')
            ->assertJsonPath('data.price', 49.5)
            ->assertJsonPath('data.is_published', true);

        $this->assertDatabaseHas('courses', [
            'user_id' => $user->id,
            'title' => 'Laravel for Beginners',
            'price' => 49.5,
            'is_published' => true,
        ]);
    }

    public function test_creating_a_course_with_only_a_title_applies_the_defaults(): void
    {
        $user = $this->actAsUser();

        $this->postJson('/api/courses', ['title' => 'Only a title'])
            ->assertCreated()
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.is_published', false);

        $this->assertDatabaseHas('courses', [
            'user_id' => $user->id,
            'title' => 'Only a title',
            'price' => 0,
            'is_published' => false,
        ]);
    }

    public function test_creating_a_course_ignores_a_user_id_sent_in_the_body(): void
    {
        $user = $this->actAsUser();
        $other = User::factory()->create();

        $this->postJson('/api/courses', ['title' => 'Spoofed owner', 'user_id' => $other->id])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('courses', ['title' => 'Spoofed owner', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('courses', ['user_id' => $other->id]);
    }

    public function test_creating_a_course_rejects_an_empty_body(): void
    {
        $this->actAsUser();

        $this->postJson('/api/courses', [])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_creating_a_course_rejects_an_invalid_title(): void
    {
        $this->actAsUser();

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
        $this->actAsUser();

        foreach ([-1, 'free', 1000000] as $price) {
            $this->postJson('/api/courses', ['title' => 'Priced', 'price' => $price])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['price']);
        }

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_creating_a_course_rejects_an_invalid_published_flag(): void
    {
        $this->actAsUser();

        $this->postJson('/api/courses', ['title' => 'Flagged', 'is_published' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_published']);

        $this->assertDatabaseCount('courses', 0);
    }

    // ------------------------------------------------------------------
    // Update by the owner
    // ------------------------------------------------------------------

    public function test_updating_a_course_requires_authentication(): void
    {
        $course = Course::factory()->create(['title' => 'Original']);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Changed'])
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original']);
    }

    public function test_the_owner_can_update_a_course_with_put(): void
    {
        $user = $this->actAsUser();
        $course = $this->courseOwnedBy($user, ['title' => 'Original', 'price' => 10, 'is_published' => false]);

        $this->putJson("/api/courses/{$course->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'price' => 75.5,
            'is_published' => true,
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Course updated successfully'])
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.user_id', $user->id)
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

    public function test_the_owner_can_partially_update_a_course_with_patch(): void
    {
        $user = $this->actAsUser();
        $course = $this->courseOwnedBy($user, ['title' => 'Keep this title', 'price' => 20, 'is_published' => false]);

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
        $user = $this->actAsUser();
        $other = User::factory()->create();
        $course = $this->courseOwnedBy($user);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Still mine', 'user_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'user_id' => $user->id]);
    }

    public function test_updating_a_course_rejects_invalid_data_and_changes_nothing(): void
    {
        $user = $this->actAsUser();
        $course = $this->courseOwnedBy($user, ['title' => 'Original', 'price' => 10]);

        $this->putJson("/api/courses/{$course->id}", ['title' => '', 'price' => -5])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['title', 'price']);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original', 'price' => 10]);
    }

    public function test_updating_an_unknown_course_returns_404(): void
    {
        $this->actAsUser();

        $this->putJson('/api/courses/999999', ['title' => 'Nothing'])
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found', 'data' => null]);
    }

    // ------------------------------------------------------------------
    // Update by someone else
    // ------------------------------------------------------------------

    public function test_a_non_owner_cannot_update_a_course_with_put(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create(['title' => 'Original', 'price' => 10]);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Hacked', 'price' => 1])
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'Forbidden', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original', 'price' => 10]);
    }

    public function test_a_non_owner_cannot_update_a_course_with_patch(): void
    {
        $this->actAsUser();
        $course = Course::factory()->unpublished()->create();

        $this->patchJson("/api/courses/{$course->id}", ['is_published' => true])
            ->assertForbidden()
            ->assertJson(['success' => false, 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'is_published' => false]);
    }

    public function test_a_non_owner_gets_403_even_when_the_body_is_invalid(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create(['title' => 'Original']);

        // Authorization runs before validation, so this is 403 and not 422.
        $this->putJson("/api/courses/{$course->id}", ['title' => ''])
            ->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Original']);
    }

    // ------------------------------------------------------------------
    // Delete
    // ------------------------------------------------------------------

    public function test_deleting_a_course_requires_authentication(): void
    {
        $course = Course::factory()->create();

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_the_owner_can_delete_a_course(): void
    {
        $user = $this->actAsUser();
        $course = $this->courseOwnedBy($user);

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Course deleted successfully']);

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);

        $this->getJson("/api/courses/{$course->id}")->assertNotFound();
    }

    public function test_deleting_a_course_leaves_the_other_courses_untouched(): void
    {
        $user = $this->actAsUser();
        $target = $this->courseOwnedBy($user);
        $mine = $this->courseOwnedBy($user);
        $theirs = Course::factory()->create();

        $this->deleteJson("/api/courses/{$target->id}")->assertOk();

        $this->assertDatabaseMissing('courses', ['id' => $target->id]);
        $this->assertDatabaseHas('courses', ['id' => $mine->id]);
        $this->assertDatabaseHas('courses', ['id' => $theirs->id]);
    }

    public function test_a_non_owner_cannot_delete_a_course(): void
    {
        $this->actAsUser();
        $course = Course::factory()->create();

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'Forbidden', 'data' => null]);

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_the_owner_can_still_delete_after_a_stranger_was_refused(): void
    {
        $owner = User::factory()->create();
        $course = $this->courseOwnedBy($owner);

        $this->actAsUser();
        $this->deleteJson("/api/courses/{$course->id}")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/courses/{$course->id}")->assertOk();

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_deleting_an_unknown_course_returns_404(): void
    {
        $this->actAsUser();

        $this->deleteJson('/api/courses/999999')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found', 'data' => null]);
    }

    // ------------------------------------------------------------------
    // Courses without an owner (user_id is null)
    // Requires the user_id column to be nullable. If it is not, delete this section.
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
