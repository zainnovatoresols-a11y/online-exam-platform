<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;

class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->isPlatformSuperAdmin()
            || $user->isOrganizationSuperAdmin();
    }

    public function view(User $user, Test $test): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        if (! $user->isAdmin() && ! $user->isOrganizationSuperAdmin()) {
            return false;
        }

        return $test->belongsToAdminScope($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
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

    private function ownsTest(User $user, Test $test): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return $test->belongsToAdminScope($user);
    }
}
