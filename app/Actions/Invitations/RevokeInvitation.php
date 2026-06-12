<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;

class RevokeInvitation
{
    public function handle(Invitation $invitation): void
    {
        $invitation->update([
            'status' => InvitationStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }
}
