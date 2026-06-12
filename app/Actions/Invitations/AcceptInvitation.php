<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AcceptInvitation
{
    /**
     * Accept a pending invitation and return the candidate user.
     *
     * @throws AuthorizationException
     */
    public function handle(Invitation $invitation, ?User $currentUser = null): User
    {
        if ($currentUser && strtolower($currentUser->email) !== strtolower($invitation->email)) {
            throw new AuthorizationException('This invitation belongs to a different email address.');
        }

        $candidate = $currentUser ?? User::firstOrCreate(
            ['email' => strtolower($invitation->email)],
            [
                'name' => $invitation->name ?: $invitation->email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ],
        );

        Role::findOrCreate(UserRole::Candidate->value, 'web');

        if (! $candidate->hasRole(UserRole::Candidate->value)) {
            $candidate->assignRole(UserRole::Candidate->value);
        }

        if ($candidate->organization_id === null && $invitation->organization_id !== null) {
            $candidate->update([
                'organization_id' => $invitation->organization_id,
            ]);
        }

        if (
            $candidate->organization_id === null
            && $invitation->organization_id === null
            && $candidate->created_by_id === null
        ) {
            $candidate->update([
                'created_by_id' => $invitation->invited_by,
            ]);
        }

        if ($candidate->email_verified_at === null) {
            $candidate->update([
                'email_verified_at' => now(),
            ]);
        }

        $invitation->update([
            'candidate_user_id' => $candidate->id,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        Auth::login($candidate);

        return $candidate;
    }
}
