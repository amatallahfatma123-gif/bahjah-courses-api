<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'status'            => UserStatus::class,
        ];
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    // ============================================
    // Helper Methods — Role
    // ============================================

    /**
     * هل المستخدم أدمن؟
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * هل المستخدم طالب؟
     */
    public function isStudent(): bool
    {
        return $this->role === UserRole::STUDENT;
    }

    // ============================================
    // Helper Methods — Status
    // ============================================

    /**
     * هل الحساب في انتظار الموافقة؟
     */
    public function isPending(): bool
    {
        return $this->status === UserStatus::PENDING;
    }

    /**
     * هل الحساب معتمد؟
     */
    public function isApproved(): bool
    {
        return $this->status === UserStatus::APPROVED;
    }

    /**
     * هل الطالب معتمد ويستطيع الوصول للكورسات؟
     * (أدمن يعتبر معتمداً تلقائياً)
     */
    public function canAccessCourses(): bool
    {
        return $this->isAdmin() || $this->isApproved();
    }
}
