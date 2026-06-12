<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestAttempt>
 */
class TestAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'invitation_id' => null,
            'candidate_user_id' => User::factory(),
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
            'score' => 0,
            'total_marks' => 0,
        ];
    }
}
