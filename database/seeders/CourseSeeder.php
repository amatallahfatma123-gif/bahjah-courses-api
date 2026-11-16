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
        $owner = User::query()->where('email', 'test@example.com')->firstOrFail();
        $other = User::query()->where('email', 'other@example.com')->firstOrFail();

        Course::factory()->count(4)->for($owner, 'owner')->withTopic('Laravel')->published()->create();
        Course::factory()->count(2)->for($owner, 'owner')->withTopic('Laravel')->unpublished()->create();
        Course::factory()->count(4)->for($other, 'owner')->published()->create();
    }
}
