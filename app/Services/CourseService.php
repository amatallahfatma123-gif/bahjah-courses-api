<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
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
}
