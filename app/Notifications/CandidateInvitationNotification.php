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
        $owner = $test->organization?->name ?? $test->creator?->name ?? 'Exam Admin';
        $url = $test->public_token
            ? route('candidate.public-tests.policy', [
                'publicToken' => $test->public_token,
                'email' => $this->invitation->email,
                'invite' => $this->invitation->token,
            ])
            : route('candidate.invitations.show', $this->invitation->token);

        $message = (new MailMessage)
            ->subject('You are invited to take '.$test->title)
            ->greeting('Hello'.($this->invitation->name ? ' '.$this->invitation->name : '').',')
            ->line('You have been invited to take the following test:')
            ->line($test->title)
            ->line('From: '.$owner)
            ->action('Open Test Link', $url);

        if ($this->invitation->starts_at) {
            $message->line('Starts on: '.$this->invitation->starts_at->toDayDateTimeString().'.');
        }

        if ($this->invitation->expires_at) {
            $message->line('This invitation expires on '.$this->invitation->expires_at->toDayDateTimeString().'.');
        }

        return $message->line('Please read and accept the test policy before entering your candidate details.');
    }
}
