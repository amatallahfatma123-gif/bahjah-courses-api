<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    /**
     * يتأكد أن المستخدم الحالي أدمن.
     *
     * القاعدة:
     * - لو المستخدم أدمن: يعدي.
     * - لو المستخدم مش أدمن: يرفض بـ 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $next($request);
        }

        return ApiResponse::error(
            'Admin access required.',
            Response::HTTP_FORBIDDEN,
        );
    }
}
