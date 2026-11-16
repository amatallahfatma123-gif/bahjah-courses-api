<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        // 6 كورسات منشورة (Laravel)
        Course::factory()->count(6)->for($admin, 'owner')->withTopic('Laravel')->published()->create();

        // 2 كورسات غير منشورة (Laravel)
        Course::factory()->count(2)->for($admin, 'owner')->withTopic('Laravel')->unpublished()->create();

        // 2 كورسات منشورة (موضوع تاني)
        Course::factory()->count(2)->for($admin, 'owner')->published()->create();
    }
}
