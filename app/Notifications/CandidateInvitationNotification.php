<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidateInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Invitation $invitation)
    {
        $this->invitation->loadMissing(['test.organization', 'test.creator']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $test = $this->invitation->test;
        $owner = $test->organization?->name ?? $test->creator?->name ?? 'Quiz Admin';
        $url = $test->public_token
            ? route('candidate.public-tests.policy', [
                'publicToken' => $test->public_token,
                'email' => $this->invitation->email,
                'invite' => $this->invitation->token,
            ])
            : route('candidate.invitations.show', $this->invitation->token);

        return (new MailMessage)
            ->from((string) config('mail.from.address'), 'Online Quiz Platform')
            ->subject('You are invited to take '.$test->title)
            ->view([
                'html' => 'emails.candidate-invitation',
                'text' => 'emails.candidate-invitation-text',
            ], [
                'candidateName' => $this->invitation->name,
                'testTitle' => $test->title,
                'owner' => $owner,
                'url' => $url,
                'startsAt' => $this->invitation->starts_at?->toDayDateTimeString(),
                'expiresAt' => $this->invitation->expires_at?->toDayDateTimeString(),
            ]);
    }
}
