<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        $published = fake()->boolean(70); // 70% فرصة يكون published

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(4, true),
            'published' => $published,
            'published_at' => $published ? fake()->dateTimeBetween('-1 year') : null,
        ];
    }

    // State: Post منشور
    public function published(): static
    {
        return $this->state([
            'published' => true,
            'published_at' => now(),
        ]);
    }

    // State: Post مش منشور
    public function draft(): static
    {
        return $this->state([
            'published' => false,
            'published_at' => null,
        ]);
    }
}
