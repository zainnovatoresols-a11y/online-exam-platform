<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Notifications\CandidateInvitationNotification;
use Illuminate\Support\Facades\Notification;

class ResendInvitation
{
    public function handle(Invitation $invitation): void
    {
        Notification::route('mail', $invitation->email)
            ->notify(new CandidateInvitationNotification($invitation));

        if ($invitation->isPending()) {
            $invitation->update([
                'status' => InvitationStatus::Sent,
            ]);
        }
    }
}
