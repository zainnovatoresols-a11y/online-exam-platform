<?php

namespace Database\Factories;

use App\Enums\TestStatus;
use App\Models\Organization;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Test>
 */
class TestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'created_by_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'duration_minutes' => 60,
            'pass_mark' => 50,
            'starts_at' => null,
            'public_token' => Str::random(48),
            'public_access_enabled' => false,
            'candidate_fields' => [],
            'policy_text' => null,
            'status' => TestStatus::Draft->value,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);
    }
}
