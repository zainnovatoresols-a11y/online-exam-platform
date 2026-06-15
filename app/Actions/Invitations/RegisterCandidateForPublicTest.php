<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Test;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterCandidateForPublicTest
{
    /**
     * Store candidate details through a public test link and accept/create their invitation.
     *
     * @param  array{name: string, email: string, invitation_token?: string|null, phone?: string|null, stack_name?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(Test $test, array $data): Invitation
    {
        if (! $test->isPublished()) {
            throw ValidationException::withMessages([
                'email' => 'This test is not available.',
            ]);
        }

        $email = strtolower(trim($data['email']));
        $invitationToken = trim((string) ($data['invitation_token'] ?? ''));

        $invitation = $invitationToken !== ''
            ? $this->invitationForToken($test, $invitationToken)
            : $this->invitationForEmail($test, $email);

        if ($invitationToken !== '' && ! $invitation) {
            throw ValidationException::withMessages([
                'email' => 'This invitation link is invalid.',
            ]);
        }

        if ($invitation && strtolower($invitation->email) !== $email) {
            throw ValidationException::withMessages([
                'email' => 'Please use the email address that received this invitation.',
            ]);
        }

        if ($invitation?->isRevoked()) {
            throw ValidationException::withMessages([
                'email' => 'This invitation has been revoked.',
            ]);
        }

        if ($invitation?->isAcceptable() && $invitation->hasExpired()) {
            $invitation->update([
                'status' => InvitationStatus::Expired,
            ]);
        }

        if ($invitation?->isExpired()) {
            throw ValidationException::withMessages([
                'email' => 'This invitation has expired.',
            ]);
        }

        if (! $invitation && ! $test->public_access_enabled) {
            throw ValidationException::withMessages([
                'email' => 'This email is not allowed to access this test.',
            ]);
        }

        if (! $invitation) {
            $invitation = Invitation::create([
                'organization_id' => $test->organization_id,
                'test_id' => $test->id,
                'invited_by' => $test->created_by_id,
                'name' => $data['name'],
                'email' => $email,
                'token' => $this->token(),
                'status' => InvitationStatus::Pending,
                'starts_at' => $test->starts_at,
                'expires_at' => null,
            ]);
        }

        $invitation->update([
            'name' => $data['name'],
            'email' => $email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => $invitation->accepted_at ?? now(),
            'policy_accepted_at' => $invitation->policy_accepted_at ?? now(),
        ]);

        CandidateTestDetail::updateOrCreate(
            ['invitation_id' => $invitation->id],
            [
                'organization_id' => $test->organization_id,
                'test_id' => $test->id,
                'test_attempt_id' => $invitation->attempt?->id,
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'stack_name' => $data['stack_name'] ?? null,
                'fields' => [
                    'name' => $data['name'],
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'stack_name' => $data['stack_name'] ?? null,
                ],
                'submitted_at' => now(),
            ],
        );

        return $invitation->refresh()->load(['candidateDetail', 'attempt']);
    }

    private function invitationForToken(Test $test, string $token): ?Invitation
    {
        return Invitation::query()
            ->where('test_id', $test->id)
            ->where('token', $token)
            ->latest('id')
            ->first();
    }

    private function invitationForEmail(Test $test, string $email): ?Invitation
    {
        return Invitation::query()
            ->where('test_id', $test->id)
            ->where('email', $email)
            ->latest('id')
            ->first();
    }

    private function token(): string
    {
        do {
            $token = Str::random(64);
        } while (Invitation::query()->where('token', $token)->exists());

        return $token;
    }
}
