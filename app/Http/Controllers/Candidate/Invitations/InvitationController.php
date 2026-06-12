<?php

namespace App\Http\Controllers\Candidate\Invitations;

use App\Actions\Invitations\AcceptInvitation;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Invitations\AcceptInvitationRequest;
use App\Models\Invitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response|HttpResponse|RedirectResponse
    {
        $invitation = $this->findInvitation($token);

        if (! $invitation) {
            return $this->statusPage($request, 'invalid', 'Invalid invitation link.', 404);
        }

        $invitation->load(['test.organization:id,name', 'test.creator:id,name,email', 'organization:id,name', 'inviter:id,name,email']);

        if ($this->markExpiredIfNeeded($invitation)) {
            return $this->statusPage($request, 'expired', 'This invitation has expired.', 403, $invitation);
        }

        if ($invitation->isRevoked()) {
            return $this->statusPage($request, 'revoked', 'This invitation has been revoked.', 403, $invitation);
        }

        if ($invitation->isAccepted()) {
            if ($request->user()?->id === $invitation->candidate_user_id) {
                return to_route('candidate.tests.show', $invitation->test);
            }

            return $this->statusPage($request, 'accepted', 'This invitation has already been accepted.', 200, $invitation);
        }

        return Inertia::render('Candidate/Invitations/Show', [
            'invitation' => $this->invitationPayload($invitation),
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token, AcceptInvitation $acceptInvitation): Response|HttpResponse|RedirectResponse
    {
        $invitation = $this->findInvitation($token);

        if (! $invitation) {
            return $this->statusPage($request, 'invalid', 'Invalid invitation link.', 404);
        }

        $invitation->load(['test.organization:id,name', 'test.creator:id,name,email', 'organization:id,name', 'inviter:id,name,email']);

        if ($this->markExpiredIfNeeded($invitation)) {
            return $this->statusPage($request, 'expired', 'This invitation has expired.', 403, $invitation);
        }

        if ($invitation->isRevoked()) {
            return $this->statusPage($request, 'revoked', 'This invitation has been revoked.', 403, $invitation);
        }

        if ($invitation->isAccepted()) {
            if ($request->user()?->id === $invitation->candidate_user_id) {
                return to_route('candidate.tests.show', $invitation->test);
            }

            return $this->statusPage($request, 'accepted', 'This invitation has already been accepted.', 200, $invitation);
        }

        try {
            $acceptInvitation->handle($invitation, $request->user());
        } catch (AuthorizationException) {
            abort(403);
        }

        return to_route('candidate.tests.show', $invitation->test);
    }

    private function findInvitation(string $token): ?Invitation
    {
        return Invitation::query()
            ->where('token', $token)
            ->first();
    }

    private function markExpiredIfNeeded(Invitation $invitation): bool
    {
        if (! $invitation->isAcceptable() || ! $invitation->hasExpired()) {
            return false;
        }

        $invitation->update([
            'status' => InvitationStatus::Expired,
        ]);

        return true;
    }

    private function statusPage(Request $request, string $status, string $message, int $statusCode, ?Invitation $invitation = null): HttpResponse
    {
        return Inertia::render('Candidate/Invitations/Status', [
            'status' => $status,
            'message' => $message,
            'invitation' => $invitation ? $this->invitationPayload($invitation) : null,
        ])->toResponse($request)->setStatusCode($statusCode);
    }

    /**
     * @return array<string, mixed>
     */
    private function invitationPayload(Invitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'token' => $invitation->token,
            'email' => $invitation->email,
            'name' => $invitation->name,
            'status' => $invitation->status->value,
            'starts_at' => $invitation->starts_at?->toISOString(),
            'expires_at' => $invitation->expires_at?->toISOString(),
            'accepted_at' => $invitation->accepted_at?->toISOString(),
            'test' => [
                'id' => $invitation->test->id,
                'title' => $invitation->test->title,
                'duration_minutes' => $invitation->test->duration_minutes,
                'pass_mark' => $invitation->test->pass_mark,
                'status' => $invitation->test->status,
                'organization' => $invitation->test->organization ? [
                    'id' => $invitation->test->organization->id,
                    'name' => $invitation->test->organization->name,
                ] : null,
                'creator' => $invitation->test->creator ? [
                    'id' => $invitation->test->creator->id,
                    'name' => $invitation->test->creator->name,
                    'email' => $invitation->test->creator->email,
                ] : null,
            ],
        ];
    }
}
