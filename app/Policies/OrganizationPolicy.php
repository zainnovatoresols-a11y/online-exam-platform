<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isPlatformSuperAdmin()
            || $user->belongsToOrganization($organization);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformSuperAdmin();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isPlatformSuperAdmin()
            || $user->belongsToOrganization($organization);
    }

    public function createAdmin(User $user, Organization $organization): bool
    {
        return $user->isPlatformSuperAdmin()
            || $user->belongsToOrganization($organization);
    }
}
