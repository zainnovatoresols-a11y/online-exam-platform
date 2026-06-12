<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class CandidatePolicy
{
    public function viewAnyCandidate(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function createCandidate(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function updateCandidate(User $user, User $candidate): bool
    {
        return $this->ownsCandidate($user, $candidate);
    }

    public function deleteCandidate(User $user, User $candidate): bool
    {
        return $this->ownsCandidate($user, $candidate);
    }

    private function ownsCandidate(User $admin, User $candidate): bool
    {
        if (! $this->isAdmin($admin) || ! $candidate->isCandidate()) {
            return false;
        }

        if ($admin->organization_id !== null) {
            return $candidate->organization_id === $admin->organization_id;
        }

        return $candidate->organization_id === null
            && $candidate->created_by_id === $admin->id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::Admin->value);
    }
}
