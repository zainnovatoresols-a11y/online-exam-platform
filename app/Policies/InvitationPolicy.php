<?php

namespace App\Policies;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user, Test $test): bool
    {
        return $this->isAdmin($user) && $test->belongsToAdminScope($user);
    }

    public function create(User $user, Test $test): bool
    {
        return $this->viewAny($user, $test) && $test->isPublished();
    }

    public function resend(User $user, Invitation $invitation): bool
    {
        return $this->isAdmin($user)
            && $invitation->test->belongsToAdminScope($user)
            && $invitation->test->isPublished()
            && $invitation->status === InvitationStatus::Pending;
    }

    public function revoke(User $user, Invitation $invitation): bool
    {
        return $this->isAdmin($user)
            && $invitation->test->belongsToAdminScope($user)
            && $invitation->status === InvitationStatus::Pending;
    }

    public function viewTest(User $user, Test $test): bool
    {
        return $user->hasRole(UserRole::Candidate->value)
            && Invitation::query()
                ->where('test_id', $test->id)
                ->where('candidate_user_id', $user->id)
                ->where('email', $user->email)
                ->where('status', InvitationStatus::Accepted->value)
                ->exists();
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::Admin->value);
    }
}
