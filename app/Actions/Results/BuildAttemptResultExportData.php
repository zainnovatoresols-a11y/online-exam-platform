<?php

namespace App\Actions\Results;

use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\CandidateTestDetail;
use App\Models\CodeExecutionRun;
use App\Models\Invitation;
use App\Models\ProctoringEvent;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildAttemptResultExportData
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Test $test, TestAttempt $attempt): array
    {
        $test->loadMissing(['organization:id,name', 'creator:id,name,email']);

        $attempt->load([
            'candidate:id,name,email,phone,stack_name',
            'candidateDetail',
            'invitation.candidate:id,name,email,phone,stack_name',
            'invitation.candidateDetail',
            'proctoringEvents:id,test_attempt_id,event_type,severity',
            'proctoringReview.reviewedBy:id,name,email',
            'answers' => fn ($query) => $query->with([
                'question:id,test_id,type,body,marks,order',
            ]),
            'codeExecutionRuns' => fn ($query) => $query
                ->select([
                    'id',
                    'test_attempt_id',
                    'question_id',
                    'attempt_answer_id',
                    'language',
                    'status',
                    'run_type',
                    'score_awarded',
                    'max_score',
                    'passed',
                    'started_at',
                    'finished_at',
                    'created_at',
                ])
                ->where('run_type', 'final')
                ->latest('created_at'),
            'codeExecutionRuns.testCaseResults' => fn ($query) => $query
                ->select(['id', 'code_execution_run_id', 'is_hidden', 'passed'])
                ->orderBy('id'),
        ]);

        $finalRunsByQuestion = $attempt->codeExecutionRuns
            ->groupBy('question_id')
            ->map(fn (Collection $runs): ?CodeExecutionRun => $runs->first());

        return [
            'test' => $this->testPayload($test),
            'candidate' => $this->candidatePayload(
                $attempt->candidateDetail ?? $attempt->invitation?->candidateDetail,
                $attempt->candidate ?? $attempt->invitation?->candidate,
                $attempt->invitation,
            ),
            'attempt' => $this->attemptPayload($attempt),
            'questions' => $attempt->answers
                ->sortBy(fn (AttemptAnswer $answer): string => sprintf(
                    '%010d-%010d',
                    (int) ($answer->question?->order ?? 0),
                    (int) ($answer->question?->id ?? 0),
                ))
                ->map(fn (AttemptAnswer $answer): array => $this->questionPayload(
                    $answer,
                    $finalRunsByQuestion->get((int) $answer->question_id),
                ))
                ->values()
                ->all(),
            'proctoring_summary' => $this->proctoringSummary($attempt->proctoringEvents),
            'proctoring_review' => $this->reviewPayload($attempt),
            'generated_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testPayload(Test $test): array
    {
        return [
            'title' => $test->title,
            'owner' => $test->organization?->name ?? $test->creator?->name ?? 'Solo admin',
            'duration_minutes' => $test->duration_minutes,
            'pass_mark' => $test->pass_mark,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function candidatePayload(?CandidateTestDetail $detail, ?User $candidate, ?Invitation $invitation): array
    {
        $fields = $detail?->fields ?? $invitation?->candidate_profile ?? [];

        return [
            'name' => $detail?->name ?? $invitation?->name ?? $candidate?->name,
            'email' => $detail?->email ?? $invitation?->email ?? $candidate?->email,
            'phone' => $detail?->phone ?? $candidate?->phone ?? ($fields['phone'] ?? null),
            'stack_name' => $detail?->stack_name ?? $candidate?->stack_name ?? ($fields['stack_name'] ?? null),
            'fields' => $fields,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptPayload(TestAttempt $attempt): array
    {
        return [
            'status' => $attempt->status->value,
            'started_at' => $attempt->started_at,
            'submitted_at' => $attempt->submitted_at,
            'score' => (float) $attempt->score,
            'max_score' => (float) $attempt->max_score,
            'percentage' => $attempt->percentage !== null ? (float) $attempt->percentage : null,
            'passed' => $attempt->passed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(AttemptAnswer $answer, ?CodeExecutionRun $run): array
    {
        $type = $answer->question?->type
            ?? ($answer->language ? QuestionType::Coding->value : QuestionType::Mcq->value);

        return [
            'type' => $type,
            'body' => $answer->question?->body ?? 'Question unavailable',
            'marks' => (float) ($answer->question?->marks ?? 0),
            'score' => (float) $answer->score,
            'is_correct' => $type === QuestionType::Mcq->value ? $answer->is_correct : null,
            'language' => $type === QuestionType::Coding->value ? $answer->language : null,
            'coding_status' => $run?->status,
            'visible_summary' => $run ? $this->caseSummary($run->testCaseResults->where('is_hidden', false)) : null,
            'hidden_summary' => $run ? $this->caseSummary($run->testCaseResults->where('is_hidden', true)) : null,
        ];
    }

    /**
     * @param  Collection<int, mixed>  $results
     * @return array{total: int, passed: int, failed: int}
     */
    private function caseSummary(Collection $results): array
    {
        return [
            'total' => $results->count(),
            'passed' => $results->where('passed', true)->count(),
            'failed' => $results->where('passed', false)->count(),
        ];
    }

    /**
     * @param  Collection<int, ProctoringEvent>  $events
     * @return array<string, int>
     */
    private function proctoringSummary(Collection $events): array
    {
        return [
            'total' => $events->count(),
            'high' => $events->where('severity', 'high')->count(),
            'medium' => $events->where('severity', 'medium')->count(),
            'low' => $events->where('severity', 'low')->count(),
            'tab_switches' => $events->where('event_type', 'tab_hidden')->count(),
            'fullscreen_exits' => $events->where('event_type', 'fullscreen_exited')->count(),
            'clipboard_attempts' => $events
                ->whereIn('event_type', ['copy_attempt', 'paste_attempt', 'cut_attempt'])
                ->count(),
            'right_click_attempts' => $events->where('event_type', 'right_click_attempt')->count(),
            'shortcut_attempts' => $events->where('event_type', 'shortcut_attempt')->count(),
            'recording_permission_denials' => $events
                ->whereIn('event_type', [
                    'camera_recording_permission_denied',
                    'screen_recording_permission_denied',
                ])
                ->count(),
            'screen_share_ended' => $events->where('event_type', 'screen_share_ended')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewPayload(TestAttempt $attempt): array
    {
        $review = $attempt->proctoringReview;

        return [
            'status' => $review?->status ?? 'needs_review',
            'risk_level' => $review?->risk_level,
            'reason_codes' => $review?->reason_codes ?? [],
            'notes' => $review?->notes,
            'reviewed_by' => $review?->reviewedBy?->name,
            'reviewed_at' => $review?->reviewed_at,
        ];
    }
}
