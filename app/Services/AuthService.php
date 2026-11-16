<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    private const TOKEN_NAME = 'api-token';

    /**
     * تسجيل مستخدم جديد.
     *
     * القاعدة الأساسية (Task 2):
     *   كل تسجيل جديد = student + pending
     *   بغض النظر عن أي role أو status في $data.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::STUDENT,
            'status' => UserStatus::PENDING,
        ]);
    }

    public function attempt(string $email, string $password): ?User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function issueToken(User $user): string
    {
        return $user->createToken(self::TOKEN_NAME)->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
