<?php

namespace App\Http\Controllers\Candidate\PublicTests;

use App\Actions\Attempts\SaveCodingAnswer;
use App\Actions\Attempts\SaveMcqAnswers;
use App\Actions\Attempts\StartPublicMcqAttempt;
use App\Actions\Attempts\SubmitMcqAttempt;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Attempts\SaveCodingAnswerRequest;
use App\Http\Requests\Candidate\Attempts\SaveMcqAnswersRequest;
use App\Http\Requests\Candidate\Attempts\SubmitMcqAttemptRequest;
use App\Models\Invitation;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PublicAttemptController extends Controller
{
    public function show(
        Request $request,
        string $attemptToken,
        StartPublicMcqAttempt $startPublicMcqAttempt,
    ): Response|HttpResponse|RedirectResponse {
        $invitation = $this->findInvitation($attemptToken);

        if (! $invitation) {
            return $this->statusPage($request, 'invalid', 'Invalid assessment link.', 404);
        }

        if ($response = $this->blockedStatus($request, $invitation)) {
            return $response;
        }

        if (! $this->hasSubmittedDetails($invitation)) {
            return to_route('candidate.public-tests.policy', [
                'publicToken' => $invitation->test->public_token,
                'invite' => $invitation->token,
            ]);
        }

        try {
            $attempt = $invitation->attempt ?: $startPublicMcqAttempt->handle($invitation);
        } catch (AuthorizationException $exception) {
            return $this->statusPage($request, 'unavailable', $exception->getMessage(), 403, $invitation->test);
        }

        if ($attempt->isSubmitted()) {
            return $this->result($attempt, $attemptToken);
        }

        $attempt->load([
            'test.organization:id,name',
            'test.creator:id,name,email',
            'test.questions' => fn ($query) => $query
                ->orderBy('order')
                ->orderBy('id'),
            'test.questions.options:id,question_id,body',
            'test.questions.testCases' => fn ($query) => $query
                ->where('is_hidden', false)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'answers:id,test_attempt_id,question_id,selected_option_id,language,submitted_code',
        ]);

        $answersByQuestion = $attempt->answers->keyBy('question_id');

        return Inertia::render('Candidate/Attempts/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'access_token' => $attemptToken,
                'is_public' => true,
                'status' => $attempt->status->value,
                'started_at' => $attempt->started_at?->toISOString(),
                'expires_at' => $attempt->expires_at?->toISOString(),
                'server_now' => now()->toISOString(),
            ],
            'test' => $this->testPayload($attempt),
            'questions' => $attempt->test->questions
                ->map(fn (Question $question): array => $this->questionPayload($question, $answersByQuestion->get($question->id)))
                ->values(),
            'saved_answers' => $attempt->answers
                ->filter(fn ($answer): bool => $answer->selected_option_id !== null)
                ->mapWithKeys(fn ($answer): array => [
                    (string) $answer->question_id => $answer->selected_option_id,
                ])
                ->all(),
        ]);
    }

    public function save(
        SaveMcqAnswersRequest $request,
        string $attemptToken,
        SaveMcqAnswers $saveMcqAnswers,
    ): RedirectResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);

        $saveMcqAnswers->handle($attempt, $request->validated('answers'));

        return back()->with('success', 'Answers saved successfully.');
    }

    public function saveCoding(
        SaveCodingAnswerRequest $request,
        string $attemptToken,
        SaveCodingAnswer $saveCodingAnswer,
    ): RedirectResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);

        $validated = $request->validated();
        $question = Question::query()->findOrFail($validated['question_id']);

        $saveCodingAnswer->handle(
            $attempt,
            $question,
            $validated['language'],
            $validated['submitted_code'] ?? null,
        );

        return back()->with('success', 'Coding answer saved successfully.');
    }

    public function submit(
        SubmitMcqAttemptRequest $request,
        string $attemptToken,
        SubmitMcqAttempt $submitMcqAttempt,
    ): RedirectResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);

        $submitMcqAttempt->handle($attempt, $request->validated('answers'));

        return to_route('candidate.public-attempts.show', $attemptToken)
            ->with('success', 'Test submitted successfully.');
    }

    private function findInvitation(string $attemptToken): ?Invitation
    {
        return Invitation::query()
            ->with([
                'test.organization:id,name',
                'test.creator:id,name,email',
                'candidateDetail',
                'attempt',
            ])
            ->where('token', $attemptToken)
            ->first();
    }

    private function attemptForToken(string $attemptToken): ?TestAttempt
    {
        $invitation = $this->findInvitation($attemptToken);

        if (! $invitation || $this->blockedStatus(request(), $invitation) || ! $this->hasSubmittedDetails($invitation)) {
            return null;
        }

        return $invitation->attempt;
    }

    private function blockedStatus(Request $request, Invitation $invitation): ?HttpResponse
    {
        if (! $invitation->test->isPublished()) {
            return $this->statusPage($request, 'not_published', 'This test has not been published yet.', 403, $invitation->test);
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

        $startsAt = $invitation->starts_at ?? $invitation->test->starts_at;

        if ($this->hasSubmittedDetails($invitation) && $startsAt !== null && now()->lessThan($startsAt)) {
            return $this->statusPage($request, 'not_started', 'This test has not started yet.', 403, $invitation->test);
        }

        return null;
    }

    private function hasSubmittedDetails(Invitation $invitation): bool
    {
        return $invitation->isAccepted()
            && $invitation->policy_accepted_at !== null
            && $invitation->candidateDetail !== null;
    }

    private function statusPage(Request $request, string $status, string $message, int $statusCode, ?Test $test = null): HttpResponse
    {
        return Inertia::render('Candidate/PublicTests/Status', [
            'status' => $status,
            'message' => $message,
            'test' => $test ? $this->standaloneTestPayload($test) : null,
        ])->toResponse($request)->setStatusCode($statusCode);
    }

    private function result(TestAttempt $attempt, string $attemptToken): Response
    {
        $attempt->load([
            'test.organization:id,name',
            'test.creator:id,name,email',
        ]);

        return Inertia::render('Candidate/Attempts/Result', [
            'attempt' => [
                'id' => $attempt->id,
                'access_token' => $attemptToken,
                'is_public' => true,
                'status' => $attempt->status->value,
                'started_at' => $attempt->started_at?->toISOString(),
                'submitted_at' => $attempt->submitted_at?->toISOString(),
                'expires_at' => $attempt->expires_at?->toISOString(),
            ],
            'test' => $this->testPayload($attempt),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function testPayload(TestAttempt $attempt): array
    {
        return $this->standaloneTestPayload($attempt->test);
    }

    /**
     * @return array<string, mixed>
     */
    private function standaloneTestPayload(Test $test): array
    {
        return [
            'id' => $test->id,
            'title' => $test->title,
            'duration_minutes' => $test->duration_minutes,
            'pass_mark' => $test->pass_mark,
            'status' => $test->status,
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

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(Question $question, mixed $savedAnswer): array
    {
        if ($question->type === QuestionType::Coding->value) {
            return [
                'id' => $question->id,
                'type' => $question->type,
                'body' => $question->body,
                'marks' => $question->marks,
                'difficulty' => $question->difficulty,
                'supported_languages' => $question->supported_languages ?? [],
                'starter_code' => $question->starter_code ?? [],
                'visible_test_cases' => $question->testCases->map(fn ($testCase): array => [
                    'id' => $testCase->id,
                    'input' => $testCase->input,
                    'expected_output' => $testCase->expected_output,
                ])->values(),
                'saved_answer' => $savedAnswer ? [
                    'language' => $savedAnswer->language,
                    'submitted_code' => $savedAnswer->submitted_code,
                ] : null,
                'options' => [],
            ];
        }

        return [
            'id' => $question->id,
            'type' => $question->type,
            'body' => $question->body,
            'marks' => $question->marks,
            'options' => $question->options->map(fn ($option): array => [
                'id' => $option->id,
                'body' => $option->body,
            ])->values(),
        ];
    }
}
