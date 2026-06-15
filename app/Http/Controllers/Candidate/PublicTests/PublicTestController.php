<?php

namespace App\Http\Controllers\Candidate\PublicTests;

use App\Actions\Attempts\StartPublicMcqAttempt;
use App\Actions\Invitations\RegisterCandidateForPublicTest;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\PublicTests\PublicTestRegistrationRequest;
use App\Models\Invitation;
use App\Models\Test;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PublicTestController extends Controller
{
    public function policy(Request $request, string $publicToken): Response|HttpResponse|RedirectResponse
    {
        $test = $this->findPublicTest($publicToken);

        if (! $test->isPublished()) {
            return $this->statusPage($request, 'not_published', 'This test has not been published yet.', 403, $test);
        }

        $invitation = $this->invitationFromRequest($request, $test);

        if ($this->hasInvitationToken($request) && ! $invitation) {
            return $this->statusPage($request, 'invalid', 'Invalid invitation link.', 404, $test);
        }

        if ($response = $this->blockedInvitationStatus($request, $invitation)) {
            return $response;
        }

        if ($redirect = $this->publicAttemptRedirect($test, $this->emailForRequest($request, $invitation), $invitation?->token)) {
            return $redirect;
        }

        return Inertia::render('Candidate/PublicTests/Policy', [
            'test' => $this->testPayload($test),
            'email' => $this->emailForRequest($request, $invitation),
            'invitation_token' => $invitation?->token,
        ]);
    }

    public function acceptPolicy(Request $request, string $publicToken): HttpResponse|RedirectResponse
    {
        $test = $this->findPublicTest($publicToken);

        if (! $test->isPublished()) {
            return $this->statusPage($request, 'not_published', 'This test has not been published yet.', 403, $test);
        }

        $invitation = $this->invitationFromRequest($request, $test);

        if ($this->hasInvitationToken($request) && ! $invitation) {
            return $this->statusPage($request, 'invalid', 'Invalid invitation link.', 404, $test);
        }

        if ($response = $this->blockedInvitationStatus($request, $invitation)) {
            return $response;
        }

        if ($redirect = $this->publicAttemptRedirect($test, $this->emailForRequest($request, $invitation), $invitation?->token)) {
            return $redirect;
        }

        $request->session()->put($this->policySessionKey($test), true);

        return to_route('candidate.public-tests.register', [
            'publicToken' => $test->public_token,
            'email' => $this->emailForRequest($request, $invitation),
            'invite' => $invitation?->token,
        ]);
    }

    public function register(Request $request, string $publicToken): Response|HttpResponse|RedirectResponse
    {
        $test = $this->findPublicTest($publicToken);

        if (! $test->isPublished()) {
            return $this->statusPage($request, 'not_published', 'This test has not been published yet.', 403, $test);
        }

        $invitation = $this->registrationInvitation($request, $test);

        if ($this->hasInvitationToken($request) && ! $this->invitationFromRequest($request, $test)) {
            return $this->statusPage($request, 'invalid', 'Invalid invitation link.', 404, $test);
        }

        if ($response = $this->blockedInvitationStatus($request, $invitation)) {
            return $response;
        }

        if (! $request->session()->get($this->policySessionKey($test), false)) {
            return to_route('candidate.public-tests.policy', [
                'publicToken' => $test->public_token,
                'email' => $this->emailForRequest($request, $invitation),
                'invite' => $invitation?->token,
            ]);
        }

        if ($response = $this->notStartedCountdown($request, $test, $invitation)) {
            return $response;
        }

        if ($redirect = $this->publicAttemptRedirect($test, $this->emailForRequest($request, $invitation), $invitation?->token)) {
            return $redirect;
        }

        return Inertia::render('Candidate/PublicTests/Register', [
            'test' => $this->testPayload($test),
            'email' => $this->emailForRequest($request, $invitation),
            'invitation_token' => $invitation?->token,
        ]);
    }

    public function store(
        PublicTestRegistrationRequest $request,
        string $publicToken,
        RegisterCandidateForPublicTest $registerCandidate,
        StartPublicMcqAttempt $startPublicMcqAttempt,
    ): HttpResponse|RedirectResponse {
        $test = $this->findPublicTest($publicToken);

        if (! $test->isPublished()) {
            return $this->statusPage($request, 'not_published', 'This test has not been published yet.', 403, $test);
        }

        if (! $request->session()->get($this->policySessionKey($test), false)) {
            return to_route('candidate.public-tests.policy', [
                'publicToken' => $test->public_token,
                'email' => $this->email($request->input('email', '')),
            ]);
        }

        $invitation = $this->registrationInvitation($request, $test);

        if ($this->hasInvitationToken($request) && ! $this->invitationFromRequest($request, $test)) {
            return $this->statusPage($request, 'invalid', 'Invalid invitation link.', 404, $test);
        }

        if ($response = $this->blockedInvitationStatus($request, $invitation)) {
            return $response;
        }

        if ($this->hasNotStarted($test, $invitation)) {
            return to_route('candidate.public-tests.register', [
                'publicToken' => $test->public_token,
                'email' => $this->emailForRequest($request, $invitation),
                'invite' => $invitation?->token,
            ]);
        }

        try {
            $invitation = $registerCandidate->handle($test, $request->validated());
            $startPublicMcqAttempt->handle($invitation);
        } catch (AuthorizationException $exception) {
            return $this->statusPage($request, 'unavailable', $exception->getMessage(), 403, $test);
        }

        $request->session()->forget($this->policySessionKey($test));

        return to_route('candidate.public-attempts.show', $invitation->token);
    }

    private function findPublicTest(string $publicToken): Test
    {
        $test = Test::query()
            ->with(['organization:id,name', 'creator:id,name,email'])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        return $test;
    }

    private function policySessionKey(Test $test): string
    {
        return 'accepted_public_test_policy_'.$test->public_token;
    }

    private function email(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function statusPage(Request $request, string $status, string $message, int $statusCode, ?Test $test = null, array $extra = []): HttpResponse
    {
        return Inertia::render('Candidate/PublicTests/Status', [
            'status' => $status,
            'message' => $message,
            'test' => $test ? $this->testPayload($test) : null,
            ...$extra,
        ])->toResponse($request)->setStatusCode($statusCode);
    }

    private function publicAttemptRedirect(Test $test, mixed $email, ?string $invitationToken = null): ?RedirectResponse
    {
        $candidateEmail = $this->email($email);

        if ($candidateEmail === '' && ! $invitationToken) {
            return null;
        }

        $invitation = Invitation::query()
            ->with(['candidateDetail', 'attempt'])
            ->where('test_id', $test->id)
            ->when(
                $invitationToken,
                fn ($query) => $query->where('token', $invitationToken),
                fn ($query) => $query->where('email', $candidateEmail),
            )
            ->where('status', InvitationStatus::Accepted->value)
            ->whereNotNull('policy_accepted_at')
            ->whereHas('candidateDetail')
            ->latest('id')
            ->first();

        if (! $invitation) {
            return null;
        }

        if ($this->hasNotStarted($test, $invitation)) {
            return null;
        }

        return to_route('candidate.public-attempts.show', $invitation->token);
    }

    private function invitationFromRequest(Request $request, Test $test): ?Invitation
    {
        $token = $this->invitationTokenFromRequest($request);

        if ($token === '') {
            return null;
        }

        return Invitation::query()
            ->with(['candidateDetail', 'attempt'])
            ->where('test_id', $test->id)
            ->where('token', $token)
            ->first();
    }

    private function hasInvitationToken(Request $request): bool
    {
        return $this->invitationTokenFromRequest($request) !== '';
    }

    private function invitationTokenFromRequest(Request $request): string
    {
        return trim((string) ($request->input('invitation_token') ?: $request->query('invite', '')));
    }

    private function registrationInvitation(Request $request, Test $test): ?Invitation
    {
        $invitation = $this->invitationFromRequest($request, $test);

        if ($invitation || $this->hasInvitationToken($request)) {
            return $invitation;
        }

        $email = $this->emailForRequest($request, null);

        if ($email === '') {
            return null;
        }

        return Invitation::query()
            ->with(['candidateDetail', 'attempt'])
            ->where('test_id', $test->id)
            ->where('email', $email)
            ->latest('id')
            ->first();
    }

    private function blockedInvitationStatus(Request $request, ?Invitation $invitation): ?HttpResponse
    {
        if (! $invitation) {
            return null;
        }

        if ($invitation->isRevoked()) {
            return $this->statusPage($request, 'revoked', 'This invitation has been revoked.', 403, $invitation->test);
        }

        if ($invitation->isAcceptable() && $invitation->hasExpired()) {
            $invitation->update([
                'status' => InvitationStatus::Expired,
            ]);
        }

        if ($invitation->isExpired() || $invitation->hasExpired()) {
            return $this->statusPage($request, 'expired', 'This invitation has expired.', 403, $invitation->test);
        }

        return null;
    }

    private function notStartedCountdown(Request $request, Test $test, ?Invitation $invitation): ?HttpResponse
    {
        $startsAt = $this->startsAt($test, $invitation);

        if ($startsAt === null || now()->greaterThanOrEqualTo($startsAt)) {
            return null;
        }

        return $this->statusPage(
            $request,
            'not_started',
            $invitation ? 'This invitation has not started yet.' : 'This test has not started yet.',
            200,
            $test,
            [
                'available_at' => $startsAt->toISOString(),
                'server_now' => now()->toISOString(),
                'action_url' => route('candidate.public-tests.register', [
                    'publicToken' => $test->public_token,
                    'email' => $this->emailForRequest($request, $invitation),
                    'invite' => $invitation?->token,
                ]),
                'action_label' => 'Continue',
            ],
        );
    }

    private function hasNotStarted(Test $test, ?Invitation $invitation): bool
    {
        $startsAt = $this->startsAt($test, $invitation);

        return $startsAt !== null && now()->lessThan($startsAt);
    }

    private function startsAt(Test $test, ?Invitation $invitation): ?Carbon
    {
        return $invitation?->starts_at ?? $test->starts_at;
    }

    private function emailForRequest(Request $request, ?Invitation $invitation): string
    {
        return $this->email($invitation?->email ?? $request->input('email', $request->query('email', '')));
    }

    /**
     * @return array<string, mixed>
     */
    private function testPayload(Test $test): array
    {
        return [
            'id' => $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'duration_minutes' => $test->duration_minutes,
            'pass_mark' => $test->pass_mark,
            'starts_at' => $test->starts_at?->toISOString(),
            'status' => $test->status,
            'public_token' => $test->public_token,
            'public_access_enabled' => $test->public_access_enabled,
            'candidate_fields' => $test->candidateRegistrationFields(),
            'policy_text' => $test->policyText(),
            'organization' => $test->organization ? [
                'id' => $test->organization->id,
                'name' => $test->organization->name,
            ] : null,
            'creator' => $test->creator ? [
                'id' => $test->creator->id,
                'name' => $test->creator->name,
                'email' => $test->creator->email,
            ] : null,
        ];
    }
}
