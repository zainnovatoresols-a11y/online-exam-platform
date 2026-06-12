<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TestAttempt;
use App\Models\User;

class TestAttemptPolicy
{
    public function view(User $user, TestAttempt $attempt): bool
    {
        return $this->ownsAttempt($user, $attempt);
    }

    public function submit(User $user, TestAttempt $attempt): bool
    {
        return $this->ownsAttempt($user, $attempt)
            && $attempt->isInProgress();
    }

    public function save(User $user, TestAttempt $attempt): bool
    {
        return $this->ownsAttempt($user, $attempt)
            && $attempt->isInProgress();
    }

    private function ownsAttempt(User $user, TestAttempt $attempt): bool
    {
        return $user->hasRole(UserRole::Candidate->value)
            && $attempt->candidate_user_id === $user->id;
    }
}
