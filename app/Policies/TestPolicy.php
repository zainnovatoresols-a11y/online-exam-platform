<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Test;
use App\Models\User;

class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Test $test): bool
    {
        return $this->isSuperAdmin($user)
            || $this->ownsTest($user, $test);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Test $test): bool
    {
        return $this->ownsTest($user, $test)
            && ($test->isDraft() || $test->isClosed());
    }

    public function delete(User $user, Test $test): bool
    {
        return $this->ownsTest($user, $test)
            && ($test->isDraft() || $test->isClosed());
    }

    public function publish(User $user, Test $test): bool
    {
        return $this->ownsTest($user, $test)
            && ($test->isDraft() || $test->isClosed());
    }

    public function close(User $user, Test $test): bool
    {
        return $this->ownsTest($user, $test) && $test->isPublished();
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::Admin->value);
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }

    private function ownsTest(User $user, Test $test): bool
    {
        if (! $this->isAdmin($user)) {
            return false;
        }

        return $test->belongsToAdminScope($user);
    }
}
