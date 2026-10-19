<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return ApiResponse::success('User registered successfully', [
            'user' => new UserResource($user),
            'token' => $this->authService->issueToken($user),
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = $this->authService->attempt(
            (string) $credentials['email'],
            (string) $credentials['password'],
        );

        if ($user === null) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        return ApiResponse::success('Login successful', [
            'user' => new UserResource($user),
            'token' => $this->authService->issueToken($user),
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authService->revokeCurrentToken($user);

        return ApiResponse::success('Logged out successfully');
    }
}
