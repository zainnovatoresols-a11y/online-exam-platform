<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Jobs\SendCandidateInvitation;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Str;

class CreateInvitation
{
    /**
     * Create and email a candidate invitation.
     *
     * @param array{name?: string|null, email: string, starts_at: string, expires_at?: string|null, url_root?: string|null} $data
     */
    public function handle(Test $test, User $admin, array $data): Invitation
    {
        $invitation = Invitation::create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'name' => $data['name'] ?? null,
            'email' => strtolower($data['email']),
            'token' => $this->token(),
            'status' => InvitationStatus::Pending,
            'starts_at' => $data['starts_at'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        SendCandidateInvitation::dispatch($invitation->id, $data['url_root'] ?? null);

        return $invitation;
    }

    private function token(): string
    {
        do {
            $token = Str::random(64);
        } while (Invitation::query()->where('token', $token)->exists());

        return $token;
    }
}
