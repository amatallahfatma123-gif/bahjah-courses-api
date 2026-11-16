<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

final class CoursePolicy
{
    /**
     * هل المستخدم يقدر يشوف قائمة الكورسات؟
     * - Admin: نعم (كل الكورسات)
     * - Approved Student: نعم (المنشور فقط — الفلترة في Controller)
     * - Pending Student: لا (الـ Middleware بيمنعه قبل كده)
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isApproved();
    }

    /**
     * هل المستخدم يقدر يشوف كورس معين؟
     * - Admin: نعم (أي كورس)
     * - Approved Student: نعم (المنشور فقط — الفلترة في Controller)
     */
    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->isApproved();
    }

    /**
     * هل المستخدم يقدر ينشئ كورس؟
     * - Admin فقط
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * هل المستخدم يقدر يعدل كورس؟
     * - Admin فقط
     */
    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }

    /**
     * هل المستخدم يقدر يحذف كورس؟
     * - Admin فقط
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }
}
