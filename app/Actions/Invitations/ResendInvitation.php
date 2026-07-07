<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Notifications\CandidateInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class ResendInvitation
{
    public function handle(Invitation $invitation, ?string $urlRoot = null): void
    {
        try {
            if ($urlRoot) {
                URL::forceRootUrl($urlRoot);
            }

            Notification::route('mail', $invitation->email)
                ->notify(new CandidateInvitationNotification($invitation));
        } finally {
            URL::forceRootUrl(null);
        }

        if ($invitation->isPending()) {
            $invitation->update([
                'status' => InvitationStatus::Sent,
            ]);
        }
    }
}
