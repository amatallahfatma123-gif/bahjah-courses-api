<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $e): bool => self::isApi($request),
        );

        $exceptions->render(static function (ValidationException $e, Request $request): ?JsonResponse {
            return self::isApi($request)
                ? ApiResponse::error('Validation Error', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors())
                : null;
        });

        $exceptions->render(static function (AuthenticationException $e, Request $request): ?JsonResponse {
            return self::isApi($request)
                ? ApiResponse::error('Unauthenticated', Response::HTTP_UNAUTHORIZED)
                : null;
        });

        $exceptions->render(static function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            return self::isApi($request)
                ? ApiResponse::error('Resource not found', Response::HTTP_NOT_FOUND)
                : null;
        });

        $exceptions->render(static function (Throwable $e, Request $request): ?JsonResponse {
            if (! self::isApi($request)) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            $message = $status >= Response::HTTP_INTERNAL_SERVER_ERROR
                ? 'Server Error'
                : (Response::$statusTexts[$status] ?? 'Error');

            return ApiResponse::error($message, $status);
        });
    }

    private static function isApi(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
