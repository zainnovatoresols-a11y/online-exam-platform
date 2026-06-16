<?php

namespace App\Http\Controllers\Admin\Results;

use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TestResultController extends Controller
{
    public function index(Test $test): Response
    {
        Gate::authorize('view', $test);

        $test->load(['organization:id,name', 'creator:id,name,email'])
            ->loadCount(['invitations', 'attempts']);

        return Inertia::render('Admin/Results/Index', [
            'test' => $this->testPayload($test),
            'results' => $test->invitations()
                ->with([
                    'candidate:id,name,email,phone,stack_name',
                    'candidateDetail',
                    'attempt.candidate:id,name,email,phone,stack_name',
                    'attempt.candidateDetail',
                ])
                ->latest('id')
                ->paginate(15)
                ->through(fn (Invitation $invitation): array => $this->resultRowPayload($invitation))
                ->withQueryString(),
        ]);
    }

    public function show(Test $test, TestAttempt $attempt): Response
    {
        Gate::authorize('view', $test);
        abort_unless((int) $attempt->test_id === (int) $test->id, 404);

        $test->load(['organization:id,name', 'creator:id,name,email'])
            ->loadCount(['invitations', 'attempts']);

        $attempt->load([
            'candidate:id,name,email,phone,stack_name',
            'candidateDetail',
            'invitation.candidate:id,name,email,phone,stack_name',
            'invitation.candidateDetail',
            'answers' => fn ($query) => $query->with([
                'question:id,test_id,body,marks,order',
                'question.options:id,question_id,body,is_correct',
                'selectedOption:id,question_id,body,is_correct',
            ]),
        ]);

        return Inertia::render('Admin/Results/Show', [
            'test' => $this->testPayload($test),
            'invitation' => $attempt->invitation
                ? $this->invitationPayload($attempt->invitation)
                : null,
            'candidate' => $this->candidatePayload(
                $attempt->candidateDetail ?? $attempt->invitation?->candidateDetail,
                $attempt->candidate ?? $attempt->invitation?->candidate,
                $attempt->invitation,
            ),
            'attempt' => $this->attemptPayload($attempt),
            'answers' => $attempt->answers
                ->sortBy(fn (AttemptAnswer $answer): string => sprintf(
                    '%010d-%010d',
                    (int) ($answer->question?->order ?? 0),
                    (int) ($answer->question?->id ?? 0),
                ))
                ->map(fn (AttemptAnswer $answer): array => $this->answerPayload($answer))
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resultRowPayload(Invitation $invitation): array
    {
        $attempt = $invitation->attempt;

        return [
            'invitation' => $this->invitationPayload($invitation),
            'candidate' => $this->candidatePayload(
                $attempt?->candidateDetail ?? $invitation->candidateDetail,
                $attempt?->candidate ?? $invitation->candidate,
                $invitation,
            ),
            'attempt' => $attempt ? $this->attemptPayload($attempt) : null,
            'attempt_status' => $attempt?->status->value ?? 'not_started',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testPayload(Test $test): array
    {
        return [
            'id' => $test->id,
            'title' => $test->title,
            'status' => $test->status,
            'duration_minutes' => $test->duration_minutes,
            'pass_mark' => $test->pass_mark,
            'starts_at' => $test->starts_at?->toISOString(),
            'organization' => $test->organization ? [
                'id' => $test->organization->id,
                'name' => $test->organization->name,
            ] : null,
            'creator' => $test->creator ? [
                'id' => $test->creator->id,
                'name' => $test->creator->name,
                'email' => $test->creator->email,
            ] : null,
            'invitations_count' => $test->invitations_count ?? null,
            'attempts_count' => $test->attempts_count ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invitationPayload(Invitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'status' => $invitation->status->value,
            'starts_at' => $invitation->starts_at?->toISOString(),
            'expires_at' => $invitation->expires_at?->toISOString(),
            'accepted_at' => $invitation->accepted_at?->toISOString(),
            'policy_accepted_at' => $invitation->policy_accepted_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptPayload(TestAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'status' => $attempt->status->value,
            'score' => $attempt->score,
            'max_score' => $attempt->max_score,
            'total_marks' => $attempt->total_marks,
            'percentage' => $attempt->percentage !== null ? (float) $attempt->percentage : null,
            'passed' => $attempt->passed,
            'started_at' => $attempt->started_at?->toISOString(),
            'submitted_at' => $attempt->submitted_at?->toISOString(),
            'expires_at' => $attempt->expires_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function candidatePayload(?CandidateTestDetail $detail, ?User $candidate, ?Invitation $invitation): array
    {
        $fields = $detail?->fields ?? $invitation?->candidate_profile ?? [];

        return [
            'id' => $candidate?->id,
            'name' => $detail?->name ?? $invitation?->name ?? $candidate?->name,
            'email' => $detail?->email ?? $invitation?->email ?? $candidate?->email,
            'phone' => $detail?->phone ?? $candidate?->phone ?? ($fields['phone'] ?? null),
            'stack_name' => $detail?->stack_name ?? $candidate?->stack_name ?? ($fields['stack_name'] ?? null),
            'fields' => $fields,
            'details_submitted_at' => $detail?->submitted_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function answerPayload(AttemptAnswer $answer): array
    {
        return [
            'id' => $answer->id,
            'question' => $answer->question ? [
                'id' => $answer->question->id,
                'body' => $answer->question->body,
                'marks' => $answer->question->marks,
                'order' => $answer->question->order,
            ] : null,
            'selected_option' => $answer->selectedOption ? [
                'id' => $answer->selectedOption->id,
                'body' => $answer->selectedOption->body,
                'is_correct' => $answer->selectedOption->is_correct,
            ] : null,
            'correct_options' => $answer->question?->options
                ->filter(fn ($option): bool => (bool) $option->is_correct)
                ->map(fn ($option): array => [
                    'id' => $option->id,
                    'body' => $option->body,
                ])
                ->values() ?? [],
            'is_correct' => $answer->is_correct,
            'score' => $answer->score,
        ];
    }
}
