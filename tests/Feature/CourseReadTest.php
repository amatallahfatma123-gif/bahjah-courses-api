<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CourseReadTest extends TestCase
{
    use RefreshDatabase;

    private function actAsUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @param  TestResponse<JsonResponse>  $response
     * @return list<int>
     */
    private function ids(TestResponse $response): array
    {
        return array_map('intval', array_column($response->json('data.items'), 'id'));
    }

    public function test_listing_requires_authentication(): void
    {
        $this->getJson('/api/courses')
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);
    }

    public function test_listing_returns_the_paginated_envelope(): void
    {
        $this->actAsUser();
        Course::factory()->count(7)->create();

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Courses retrieved successfully'])
            ->assertJsonCount(5, 'data.items')
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonPath('data.meta.total', 7)
            ->assertJsonPath('data.meta.last_page', 2)
            ->assertJsonStructure([
                'data' => [
                    'items' => ['*' => ['id', 'user_id', 'title', 'description', 'price', 'is_published', 'created_at', 'updated_at']],
                    'meta',
                ],
            ]);
    }

    public function test_listing_is_sorted_newest_first(): void
    {
        $this->actAsUser();
        $middle = Course::factory()->create(['created_at' => now()->subDays(2)]);
        $newest = Course::factory()->create(['created_at' => now()->subDay()]);
        $oldest = Course::factory()->create(['created_at' => now()->subDays(3)]);

        $response = $this->getJson('/api/courses')->assertOk();

        $this->assertSame([$newest->id, $middle->id, $oldest->id], $this->ids($response));
    }

    public function test_courses_created_at_the_same_moment_are_ordered_by_id_descending(): void
    {
        $this->actAsUser();
        $moment = now()->subHour();
        $first = Course::factory()->create(['created_at' => $moment]);
        $second = Course::factory()->create(['created_at' => $moment]);
        $third = Course::factory()->create(['created_at' => $moment]);

        $response = $this->getJson('/api/courses')->assertOk();

        $this->assertSame([$third->id, $second->id, $first->id], $this->ids($response));
    }

    public function test_the_second_page_returns_the_remaining_courses(): void
    {
        $this->actAsUser();
        Course::factory()->count(7)->create();

        $pageOne = $this->getJson('/api/courses?page=1')->assertOk();
        $pageTwo = $this->getJson('/api/courses?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.meta.current_page', 2);

        $all = array_merge($this->ids($pageOne), $this->ids($pageTwo));

        $this->assertCount(7, array_unique($all));
    }

    public function test_per_page_controls_the_page_size(): void
    {
        $this->actAsUser();
        Course::factory()->count(7)->create();

        $this->getJson('/api/courses?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data.items')
            ->assertJsonPath('data.meta.per_page', 3)
            ->assertJsonPath('data.meta.last_page', 3);

        $this->getJson('/api/courses?per_page=100')
            ->assertOk()
            ->assertJsonCount(7, 'data.items');
    }

    public function test_per_page_outside_the_allowed_range_is_rejected(): void
    {
        $this->actAsUser();

        $this->getJson('/api/courses?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/courses?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_a_page_beyond_the_last_returns_an_empty_list(): void
    {
        $this->actAsUser();
        Course::factory()->count(7)->create();

        $this->getJson('/api/courses?page=99')
            ->assertOk()
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath('data.meta.current_page', 99)
            ->assertJsonPath('data.meta.total', 7);
    }

    public function test_an_invalid_page_is_rejected(): void
    {
        $this->actAsUser();

        $this->getJson('/api/courses?page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);

        $this->getJson('/api/courses?page=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_search_matches_the_title_case_insensitively(): void
    {
        $this->actAsUser();
        Course::factory()->create(['title' => 'Laravel for Beginners']);
        Course::factory()->create(['title' => 'Advanced laravel techniques']);
        Course::factory()->create(['title' => 'PHP Fundamentals']);

        $response = $this->getJson('/api/courses?search=Laravel')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);

        foreach ($response->json('data.items') as $item) {
            $this->assertStringContainsStringIgnoringCase('laravel', $item['title']);
        }
    }

    public function test_search_with_no_match_returns_an_empty_list(): void
    {
        $this->actAsUser();
        Course::factory()->count(3)->create(['title' => 'PHP Fundamentals']);

        $this->getJson('/api/courses?search=zzzz')
            ->assertOk()
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath('data.meta.total', 0);
    }

    public function test_an_empty_search_returns_all_courses(): void
    {
        $this->actAsUser();
        Course::factory()->count(3)->create();

        $this->getJson('/api/courses?search=')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    }

    public function test_published_filter_returns_only_published_courses(): void
    {
        $this->actAsUser();
        Course::factory()->count(3)->published()->create();
        Course::factory()->count(2)->unpublished()->create();

        foreach (['true', '1'] as $value) {
            $response = $this->getJson("/api/courses?published={$value}")
                ->assertOk()
                ->assertJsonPath('data.meta.total', 3);

            foreach ($response->json('data.items') as $item) {
                $this->assertTrue($item['is_published']);
            }
        }
    }

    public function test_published_filter_returns_only_unpublished_courses(): void
    {
        $this->actAsUser();
        Course::factory()->count(3)->published()->create();
        Course::factory()->count(2)->unpublished()->create();

        foreach (['false', '0'] as $value) {
            $response = $this->getJson("/api/courses?published={$value}")
                ->assertOk()
                ->assertJsonPath('data.meta.total', 2);

            foreach ($response->json('data.items') as $item) {
                $this->assertFalse($item['is_published']);
            }
        }
    }

    public function test_published_filter_can_be_combined_with_search(): void
    {
        $this->actAsUser();
        Course::factory()->count(2)->withTopic('Laravel')->published()->create();
        Course::factory()->withTopic('Laravel')->unpublished()->create();
        Course::factory()->create(['title' => 'PHP Fundamentals', 'is_published' => true]);

        $this->getJson('/api/courses?published=true&search=Laravel')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    }

    public function test_an_invalid_published_value_is_rejected(): void
    {
        $this->actAsUser();

        $this->getJson('/api/courses?published=abc')
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation Error', 'data' => null])
            ->assertJsonValidationErrors(['published']);
    }

    public function test_show_returns_a_course_owned_by_another_user(): void
    {
        $viewer = $this->actAsUser();
        $course = Course::factory()->create([
            'title' => 'Laravel for Beginners',
            'price' => 49.5,
            'is_published' => true,
        ]);

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Course retrieved successfully'])
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.user_id', $course->user_id)
            ->assertJsonPath('data.title', 'Laravel for Beginners')
            ->assertJsonPath('data.price', 49.5)
            ->assertJsonPath('data.is_published', true);

        $this->assertNotSame($viewer->id, $course->user_id);
    }

    public function test_show_returns_404_for_an_unknown_course(): void
    {
        $this->actAsUser();

        $this->getJson('/api/courses/999999')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Resource not found', 'data' => null]);
    }

    public function test_show_requires_authentication(): void
    {
        $course = Course::factory()->create();

        $this->getJson("/api/courses/{$course->id}")
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Unauthenticated', 'data' => null]);
    }
}
