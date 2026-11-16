<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentController extends Controller
{
    /**
     * عرض قائمة الطلاب (للأدمن فقط).
     *
     * يدعم:
     * - Pagination: ?page=1&per_page=10
     * - Filter by status: ?status=pending أو ?status=approved
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->where('role', UserRole::STUDENT);

        $status = $request->query('status');
        if ($status !== null) {
            $query->where('status', $status);
        }

        $query->latest();

        $perPage = (int) $request->query('per_page', '10');
        $students = $query->paginate($perPage);

        return ApiResponse::paginated(
            'Students retrieved successfully',
            $students,
            StudentResource::class,
        );
    }

    /**
     * الموافقة على طالب (للأدمن فقط).
     *
     * بيغير حالة الطالب من pending إلى approved.
     */
    public function approve(User $student): JsonResponse
    {
        if (! $student->isStudent()) {
            return ApiResponse::error(
                'The specified user is not a student.',
                400,
            );
        }

        if ($student->isApproved()) {
            return ApiResponse::error(
                'Student is already approved.',
                400,
            );
        }

        $student->update(['status' => UserStatus::APPROVED]);

        return ApiResponse::success(
            'Student approved successfully.',
            new StudentResource($student->fresh()),
        );
    }
}
