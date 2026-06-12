<?php

namespace App\Http\Controllers\Candidate\Attempts;

use App\Actions\Attempts\StartMcqAttempt;
use App\Actions\Attempts\SubmitMcqAttempt;
use App\Http\Controllers\Controller;
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
            'test.questions' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'test.questions.options:id,question_id,body',
        ]);

        return Inertia::render('Candidate/Attempts/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'started_at' => $attempt->started_at?->toISOString(),
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
        ]);
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
            'answers.question:id,body,marks',
            'answers.selectedOption:id,body',
        ]);

        return Inertia::render('Candidate/Attempts/Result', [
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'score' => $attempt->score,
                'total_marks' => $attempt->total_marks,
                'started_at' => $attempt->started_at?->toISOString(),
                'submitted_at' => $attempt->submitted_at?->toISOString(),
            ],
            'test' => $this->testPayload($attempt),
            'answers' => $attempt->answers->map(fn ($answer): array => [
                'id' => $answer->id,
                'question' => [
                    'id' => $answer->question->id,
                    'body' => $answer->question->body,
                    'marks' => $answer->question->marks,
                ],
                'selected_option' => $answer->selectedOption ? [
                    'id' => $answer->selectedOption->id,
                    'body' => $answer->selectedOption->body,
                ] : null,
                'is_correct' => $answer->is_correct,
                'score' => $answer->score,
            ])->values(),
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
