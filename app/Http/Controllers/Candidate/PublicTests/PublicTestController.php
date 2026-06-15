<?php

namespace App\Http\Controllers\Candidate\PublicTests;

use App\Actions\Invitations\RegisterCandidateForPublicTest;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\PublicTests\PublicTestRegistrationRequest;
use App\Models\Invitation;
use App\Models\Test;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicTestController extends Controller
{
    public function policy(Request $request, string $publicToken): Response|RedirectResponse
    {
        $test = $this->findPublicTest($publicToken);

        if ($redirect = $this->registeredCandidateRedirect($request, $test, $request->query('email', ''))) {
            return $redirect;
        }

        return Inertia::render('Candidate/PublicTests/Policy', [
            'test' => $this->testPayload($test),
            'email' => $this->email($request->query('email', '')),
        ]);
    }

    public function acceptPolicy(Request $request, string $publicToken): RedirectResponse
    {
        $test = $this->findPublicTest($publicToken);

        if ($redirect = $this->registeredCandidateRedirect($request, $test, $request->input('email', ''))) {
            return $redirect;
        }

        $request->session()->put($this->policySessionKey($test), true);

        return to_route('candidate.public-tests.register', [
            'publicToken' => $test->public_token,
            'email' => $this->email($request->input('email', '')),
        ]);
    }

    public function register(Request $request, string $publicToken): Response|RedirectResponse
    {
        $test = $this->findPublicTest($publicToken);

        if ($redirect = $this->registeredCandidateRedirect($request, $test, $request->query('email', ''))) {
            return $redirect;
        }

        if (! $request->session()->get($this->policySessionKey($test), false)) {
            return to_route('candidate.public-tests.policy', [
                'publicToken' => $test->public_token,
                'email' => $this->email($request->query('email', '')),
            ]);
        }

        return Inertia::render('Candidate/PublicTests/Register', [
            'test' => $this->testPayload($test),
            'email' => $this->email($request->query('email', '')),
        ]);
    }

    public function store(
        PublicTestRegistrationRequest $request,
        string $publicToken,
        RegisterCandidateForPublicTest $registerCandidate,
    ): RedirectResponse {
        $test = $this->findPublicTest($publicToken);

        if (! $request->session()->get($this->policySessionKey($test), false)) {
            return to_route('candidate.public-tests.policy', [
                'publicToken' => $test->public_token,
                'email' => $this->email($request->input('email', '')),
            ]);
        }

        try {
            $registerCandidate->handle($test, $request->validated(), $request->user());
        } catch (AuthorizationException) {
            abort(403);
        }

        $request->session()->forget($this->policySessionKey($test));

        return to_route('candidate.tests.show', $test);
    }

    private function findPublicTest(string $publicToken): Test
    {
        $test = Test::query()
            ->with(['organization:id,name', 'creator:id,name,email'])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        abort_unless($test->isPublished(), 404);

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

    private function registeredCandidateRedirect(Request $request, Test $test, mixed $email): ?RedirectResponse
    {
        $candidateEmail = $this->email($email);
        $candidate = $request->user();

        if ($candidate) {
            $userEmail = $this->email($candidate->email);

            if ($candidateEmail !== '' && $candidateEmail !== $userEmail) {
                return null;
            }

            $candidateEmail = $userEmail;
        }

        if ($candidateEmail === '') {
            return null;
        }

        $invitation = Invitation::query()
            ->where('test_id', $test->id)
            ->where('email', $candidateEmail)
            ->when($candidate, fn ($query) => $query->where('candidate_user_id', $candidate->id))
            ->where('status', InvitationStatus::Accepted->value)
            ->whereNotNull('candidate_user_id')
            ->whereNotNull('policy_accepted_at')
            ->whereNotNull('candidate_profile')
            ->first();

        return $invitation
            ? to_route('candidate.tests.show', $test)
            : null;
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
