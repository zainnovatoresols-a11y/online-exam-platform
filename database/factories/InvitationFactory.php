<?php

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => null,
            'test_id' => Test::factory(),
            'invited_by' => User::factory(),
            'candidate_user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(64),
            'status' => InvitationStatus::Pending,
            'starts_at' => null,
            'expires_at' => null,
        ];
    }
}
