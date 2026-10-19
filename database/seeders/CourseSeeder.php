<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Course::factory()->count(4)->withTopic('Laravel')->published()->create();
        Course::factory()->count(2)->withTopic('Laravel')->unpublished()->create();
        Course::factory()->count(4)->published()->create();
    }
}
