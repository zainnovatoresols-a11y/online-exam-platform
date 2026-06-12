<?php

namespace App\Jobs;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Notifications\CandidateInvitationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class SendCandidateInvitation implements ShouldQueue
{
    use Queueable;

    public ?string $urlRoot = null;

    public function __construct(public int $invitationId, ?string $urlRoot = null)
    {
        $this->urlRoot = $urlRoot;
    }

    public function handle(): void
    {
        $invitation = Invitation::query()
            ->with(['test.organization', 'test.creator'])
            ->find($this->invitationId);

        if (! $invitation || ! $invitation->isPending()) {
            return;
        }

        try {
            if ($this->urlRoot) {
                URL::forceRootUrl($this->urlRoot);
            }

            Notification::route('mail', $invitation->email)
                ->notify(new CandidateInvitationNotification($invitation));

            $invitation->update([
                'status' => InvitationStatus::Sent,
            ]);
        } finally {
            URL::forceRootUrl(null);
        }
    }
}
