<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================
        // 1. الأدمن (للاختبار)
        // ============================================
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => UserRole::ADMIN,
            'status' => UserStatus::APPROVED,
        ]);

        // ============================================
        // 2. طالب معلّق (Pending Student)
        // ============================================
        User::factory()->create([
            'name' => 'Pending Student',
            'email' => 'student.pending@example.com',
            'password' => 'password',
            'role' => UserRole::STUDENT,
            'status' => UserStatus::PENDING,
        ]);

        // ============================================
        // 3. طالب معتمد (Approved Student)
        // ============================================
        User::factory()->create([
            'name' => 'Approved Student',
            'email' => 'student.approved@example.com',
            'password' => 'password',
            'role' => UserRole::STUDENT,
            'status' => UserStatus::APPROVED,
        ]);

        // ============================================
        // 4. الكورسات
        // ============================================
        $this->call(CourseSeeder::class);
    }
}
