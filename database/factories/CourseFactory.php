<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * @var list<string>
     */
    private const TOPICS = ['Laravel', 'PHP', 'MySQL', 'Git', 'Docker', 'Vue.js', 'Testing'];

    /**
     * @var list<string>
     */
    private const FORMATS = ['for Beginners', 'Fundamentals', 'Essentials', 'Masterclass', 'Advanced Techniques'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(self::TOPICS) . ' ' . fake()->randomElement(self::FORMATS),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 0, 200),
            'is_published' => fake()->boolean(70),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['is_published' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function withTopic(string $topic): static
    {
        return $this->state(fn (): array => [
            'title' => $topic . ' ' . fake()->randomElement(self::FORMATS),
        ]);
    }
}
