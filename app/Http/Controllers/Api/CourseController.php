<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\IndexCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    public function index(IndexCourseRequest $request): JsonResponse
    {
        $courses = $this->courseService->paginate(
            $request->searchTerm(),
            $request->publishedFilter(),
            $request->perPage(),
        );

        return ApiResponse::paginated('Courses retrieved successfully', $courses, CourseResource::class);
    }

    public function show(Course $course): JsonResponse
    {
        return ApiResponse::success('Course retrieved successfully', new CourseResource($course));
    }
}
