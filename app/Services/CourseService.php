<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class CourseService
{
    private const DEFAULT_PER_PAGE = 5;

    /**
     * @return LengthAwarePaginator<int, Course>
     */
    public function paginate(?string $search, ?bool $published, ?int $perPage = null): LengthAwarePaginator
    {
        return Course::query()
            ->search($search)
            ->when(
                $published !== null,
                static fn (Builder $query): Builder => $query->published((bool) $published),
            )
            ->latestFirst()
            ->paginate($perPage ?? self::DEFAULT_PER_PAGE);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Course
    {
        return $owner->courses()->create($data)->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Course $course, array $data): Course
    {
        $course->update($data);

        return $course->refresh();
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }
}
