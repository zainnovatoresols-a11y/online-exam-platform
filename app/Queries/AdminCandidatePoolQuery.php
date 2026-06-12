<?php

namespace App\Queries;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminCandidatePoolQuery
{
    /**
     * Build the candidate pool query for an admin.
     *
     * @return Builder<User>
     */
    public function query(User $admin): Builder
    {
        return User::query()
            ->role(UserRole::Candidate->value)
            ->where(function (Builder $query) use ($admin): void {
                if ($admin->organization_id !== null) {
                    $query->where('organization_id', $admin->organization_id);

                    return;
                }

                $query->whereNull('organization_id')
                    ->where('created_by_id', $admin->id);
            });
    }
}
