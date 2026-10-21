<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\IndexCourseRequest;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
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

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create($request->validated());

        return ApiResponse::success('Course created successfully', new CourseResource($course), 201);
    }

    public function show(Course $course): JsonResponse
    {
        return ApiResponse::success('Course retrieved successfully', new CourseResource($course));
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course = $this->courseService->update($course, $request->validated());

        return ApiResponse::success('Course updated successfully', new CourseResource($course));
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->courseService->delete($course);

        return ApiResponse::success('Course deleted successfully');
    }
}
