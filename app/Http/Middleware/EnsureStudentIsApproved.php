<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStudentIsApproved
{
    /**
     * يمنع الطالب المعلّق (pending) من الوصول للكورسات.
     *
     * القاعدة:
     * - الأدمن يعدي دائماً (مش طالب، مش محتاج موافقة).
     * - الطالب المعتمد (approved) يعدي.
     * - الطالب المعلّق (pending) يرفض بـ 403.
     *
     * ملاحظة: هذا الـ Middleware يعمل بعد auth:sanctum،
     * لذا المستخدم موجود دائماً في $request->user().
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // الأدمن يعدي دائماً
        if ($user->isAdmin()) {
            return $next($request);
        }

        // الطالب المعتمد يعدي
        if ($user->isApproved()) {
            return $next($request);
        }

        // الطالب المعلّق (أو أي حالة أخرى) يرفض
        return ApiResponse::error(
            'Your account is pending admin approval.',
            Response::HTTP_FORBIDDEN,
        );
    }
}
