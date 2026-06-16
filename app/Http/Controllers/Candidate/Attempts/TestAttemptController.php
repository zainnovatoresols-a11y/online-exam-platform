<?php

namespace App\Http\Controllers\Candidate\Attempts;

use App\Actions\Attempts\SaveMcqAnswers;
use App\Actions\Attempts\StartMcqAttempt;
use App\Actions\Attempts\SubmitMcqAttempt;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Attempts\SaveMcqAnswersRequest;
use App\Http\Requests\Candidate\Attempts\SubmitMcqAttemptRequest;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TestAttemptController extends Controller
{
    public function store(Test $test, StartMcqAttempt $startMcqAttempt): RedirectResponse
    {
        Gate::authorize('viewTest', [Invitation::class, $test]);

        try {
            $attempt = $startMcqAttempt->handle($test, request()->user());
        } catch (AuthorizationException) {
            abort(403);
        }

        return to_route('candidate.attempts.show', $attempt);
    }

    public function show(TestAttempt $attempt): Response
    {
        Gate::authorize('view', $attempt);

        if ($attempt->isSubmitted()) {
            return $this->result($attempt);
        }

        $attempt->load([
            'test.organization:id,name',
            'test.creator:id,name,email',
            'test.questions' => fn ($query) => $query
                ->where('type', QuestionType::Mcq->value)
                ->orderBy('order')
                ->orderBy('id'),
            'test.questions.options:id,question_id,body',
            'answers:id,test_attempt_id,question_id,selected_option_id',
        ]);

        return Inertia::render('Candidate/Attempts/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'started_at' => $attempt->started_at?->toISOString(),
                'expires_at' => $attempt->expires_at?->toISOString(),
                'server_now' => now()->toISOString(),
            ],
            'test' => $this->testPayload($attempt),
            'questions' => $attempt->test->questions->map(fn ($question): array => [
                'id' => $question->id,
                'body' => $question->body,
                'marks' => $question->marks,
                'options' => $question->options->map(fn ($option): array => [
                    'id' => $option->id,
                    'body' => $option->body,
                ])->values(),
            ])->values(),
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
        TestAttempt $attempt,
        SaveMcqAnswers $saveMcqAnswers,
    ): RedirectResponse {
        Gate::authorize('save', $attempt);

        $saveMcqAnswers->handle($attempt, $request->validated('answers'));

        return back()->with('success', 'Answers saved successfully.');
    }

    public function submit(
        SubmitMcqAttemptRequest $request,
        TestAttempt $attempt,
        SubmitMcqAttempt $submitMcqAttempt,
    ): RedirectResponse {
        Gate::authorize('submit', $attempt);

        $submitMcqAttempt->handle($attempt, $request->validated('answers'));

        return to_route('candidate.attempts.show', $attempt)
            ->with('success', 'Test submitted successfully.');
    }

    private function result(TestAttempt $attempt): Response
    {
        $attempt->load([
            'test.organization:id,name',
            'test.creator:id,name,email',
        ]);

        return Inertia::render('Candidate/Attempts/Result', [
            'attempt' => [
                'id' => $attempt->id,
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
        return [
            'id' => $attempt->test->id,
            'title' => $attempt->test->title,
            'duration_minutes' => $attempt->test->duration_minutes,
            'pass_mark' => $attempt->test->pass_mark,
            'status' => $attempt->test->status,
            'organization' => $attempt->test->organization ? [
                'id' => $attempt->test->organization->id,
                'name' => $attempt->test->organization->name,
            ] : null,
            'creator' => $attempt->test->creator ? [
                'id' => $attempt->test->creator->id,
                'name' => $attempt->test->creator->name,
                'email' => $attempt->test->creator->email,
            ] : null,
        ];
    }
}
