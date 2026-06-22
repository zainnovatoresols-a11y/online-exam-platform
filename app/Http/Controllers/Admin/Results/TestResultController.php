<?php

namespace App\Http\Controllers\Admin\Results;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\CandidateTestDetail;
use App\Models\CodeExecutionRun;
use App\Models\Invitation;
use App\Models\ProctoringEvent;
use App\Models\ProctoringRecording;
use App\Models\ProctoringRecordingChunk;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
                    'attempt.proctoringReview:id,test_attempt_id,status',
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
            'proctoringReview.reviewedBy:id,name,email',
            'answers' => fn ($query) => $query->with([
                'question:id,test_id,type,body,marks,order',
                'question.options:id,question_id,body,is_correct',
                'selectedOption:id,question_id,body,is_correct',
            ]),
            'codeExecutionRuns' => fn ($query) => $query
                ->where('run_type', 'final')
                ->latest('created_at'),
            'codeExecutionRuns.question:id,test_id,type,body,marks,order',
            'codeExecutionRuns.attemptAnswer:id,test_attempt_id,question_id,language,submitted_code,is_correct,score',
            'codeExecutionRuns.testCaseResults' => fn ($query) => $query->orderBy('id'),
        ]);

        $finalRunsByQuestion = $attempt->codeExecutionRuns
            ->groupBy('question_id')
            ->map(fn (Collection $runs): ?CodeExecutionRun => $runs->first());

        $proctoringSummaryEvents = $attempt->proctoringEvents()
            ->get(['event_type', 'severity']);
        $proctoringEvents = $attempt->proctoringEvents()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->paginate(15, ['*'], 'proctoring_page')
            ->withQueryString();

        $proctoringEvents->through(fn (ProctoringEvent $event): array => $this->proctoringEventPayload($event));

        $proctoringRecordings = $attempt->proctoringRecordings()->get();

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
                ->map(fn (AttemptAnswer $answer): array => $this->answerPayload(
                    $answer,
                    $finalRunsByQuestion->get((int) $answer->question_id),
                ))
                ->values(),
            'proctoring_summary' => $this->proctoringSummary($proctoringSummaryEvents),
            'proctoring_events' => $proctoringEvents,
            'proctoring_review' => $this->proctoringReviewPayload($attempt),
            'proctoring_recording_summary' => $this->proctoringRecordingSummary($proctoringRecordings),
            'proctoring_camera_recording_chunks' => $this->proctoringRecordingChunks($attempt, 'camera', 'camera_recording_page'),
            'proctoring_screen_recording_chunks' => $this->proctoringRecordingChunks($attempt, 'screen', 'screen_recording_page'),
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
            'proctoring_review_status' => $attempt?->proctoringReview?->status ?? 'needs_review',
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
    private function answerPayload(AttemptAnswer $answer, ?CodeExecutionRun $executionRun = null): array
    {
        $type = $answer->question?->type
            ?? ($answer->submitted_code !== null ? QuestionType::Coding->value : QuestionType::Mcq->value);

        return [
            'id' => $answer->id,
            'type' => $type,
            'question' => $answer->question ? [
                'id' => $answer->question->id,
                'type' => $type,
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
            'language' => $answer->language,
            'submitted_code' => $answer->submitted_code,
            'execution_run' => $executionRun
                ? $this->codeExecutionRunPayload($executionRun)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function codeExecutionRunPayload(CodeExecutionRun $run): array
    {
        $visibleResults = $run->testCaseResults->where('is_hidden', false);
        $hiddenResults = $run->testCaseResults->where('is_hidden', true);

        return [
            'id' => $run->id,
            'status' => $run->status,
            'run_type' => $run->run_type,
            'language' => $run->language,
            'score_awarded' => $run->score_awarded !== null ? (float) $run->score_awarded : null,
            'max_score' => $run->max_score !== null ? (float) $run->max_score : null,
            'passed' => $run->passed,
            'result_summary' => $run->result_summary,
            'error_message' => $run->error_message,
            'visible_summary' => $this->testCaseSummary($visibleResults),
            'hidden_summary' => $this->testCaseSummary($hiddenResults),
            'started_at' => $run->started_at?->toISOString(),
            'finished_at' => $run->finished_at?->toISOString(),
            'test_case_results' => $run->testCaseResults
                ->map(fn ($result): array => [
                    'id' => $result->id,
                    'question_test_case_id' => $result->question_test_case_id,
                    'is_hidden' => $result->is_hidden,
                    'status' => $result->status,
                    'passed' => $result->passed,
                    'input' => $result->input,
                    'expected_output' => $result->expected_output,
                    'actual_output' => $result->actual_output,
                    'stdout' => $result->stdout,
                    'stderr' => $result->stderr,
                    'compile_output' => $result->compile_output,
                    'message' => $result->message,
                    'time' => $result->time,
                    'memory' => $result->memory,
                    'judge0_status_id' => $result->judge0_status_id,
                    'judge0_status_description' => $result->judge0_status_description,
                ])
                ->values(),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $results
     * @return array{total: int, passed: int, failed: int}
     */
    private function testCaseSummary(Collection $results): array
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
            'drag_drop_attempts' => $events
                ->whereIn('event_type', ['drag_attempt', 'drop_attempt'])
                ->count(),
            'acknowledged_violations' => $events
                ->where('event_type', 'proctoring_violation_acknowledged')->count(),
            'recording_permission_denials' => $events
                ->whereIn('event_type', [
                    'camera_recording_permission_denied',
                    'screen_recording_permission_denied',
                ])
                ->count(),
            'recording_errors' => $events->filter(fn (ProctoringEvent $event): bool => str_ends_with($event->event_type, '_recording_error')
                || str_ends_with($event->event_type, '_recording_chunk_failed'))->count(),
            'screen_share_ended' => $events->where('event_type', 'screen_share_ended')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function proctoringEventPayload(ProctoringEvent $event): array
    {
        return [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'severity' => $event->severity,
            'occurred_at' => $event->occurred_at?->toISOString(),
            'ip_address' => $event->ip_address,
            'user_agent' => $event->user_agent,
            'metadata' => $event->metadata ?? [],
            'created_at' => $event->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function proctoringReviewPayload(TestAttempt $attempt): array
    {
        $review = $attempt->proctoringReview;

        return [
            'id' => $review?->id,
            'status' => $review?->status ?? 'needs_review',
            'risk_level' => $review?->risk_level,
            'reason_codes' => $review?->reason_codes ?? [],
            'notes' => $review?->notes,
            'reviewed_at' => $review?->reviewed_at?->toISOString(),
            'reviewed_by' => $review?->reviewedBy ? [
                'id' => $review->reviewedBy->id,
                'name' => $review->reviewedBy->name,
                'email' => $review->reviewedBy->email,
            ] : null,
        ];
    }

    /**
     * @param  Collection<int, ProctoringRecording>  $recordings
     * @return array<string, mixed>
     */
    private function proctoringRecordingSummary(Collection $recordings): array
    {
        $camera = $recordings->firstWhere('recording_type', 'camera');
        $screen = $recordings->firstWhere('recording_type', 'screen');

        return [
            'camera_status' => $camera?->status ?? 'not_started',
            'camera_chunk_count' => $camera?->chunk_count ?? 0,
            'camera_total_size_bytes' => $camera?->total_size_bytes ?? 0,
            'camera_started_at' => $camera?->started_at?->toISOString(),
            'camera_stopped_at' => $camera?->stopped_at?->toISOString(),
            'camera_merged_status' => $camera?->merged_status ?? 'not_started',
            'camera_merged_url' => $this->mergedRecordingUrl($camera),
            'camera_merged_at' => $camera?->merged_at?->toISOString(),
            'camera_merged_size_bytes' => $camera?->merged_size_bytes ?? 0,
            'camera_merge_error' => $camera?->merge_error,
            'screen_status' => $screen?->status ?? 'not_started',
            'screen_chunk_count' => $screen?->chunk_count ?? 0,
            'screen_total_size_bytes' => $screen?->total_size_bytes ?? 0,
            'screen_started_at' => $screen?->started_at?->toISOString(),
            'screen_stopped_at' => $screen?->stopped_at?->toISOString(),
            'screen_merged_status' => $screen?->merged_status ?? 'not_started',
            'screen_merged_url' => $this->mergedRecordingUrl($screen),
            'screen_merged_at' => $screen?->merged_at?->toISOString(),
            'screen_merged_size_bytes' => $screen?->merged_size_bytes ?? 0,
            'screen_merge_error' => $screen?->merge_error,
        ];
    }

    private function mergedRecordingUrl(?ProctoringRecording $recording): ?string
    {
        if (! $recording || $recording->merged_status !== 'completed') {
            return null;
        }

        if (! $recording->merged_disk || ! $recording->merged_path) {
            return null;
        }

        return route('admin.proctoring-recordings.merged-video.show', $recording);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function proctoringRecordingChunks(
        TestAttempt $attempt,
        string $recordingType,
        string $pageName,
    ): LengthAwarePaginator {
        $chunks = $attempt->proctoringRecordingChunks()
            ->with('event:id,event_type,severity,occurred_at')
            ->where('recording_type', $recordingType)
            ->orderBy('sequence')
            ->orderBy('id')
            ->paginate(12, ['*'], $pageName)
            ->withQueryString();

        return $chunks->through(fn (ProctoringRecordingChunk $chunk): array => $this->proctoringRecordingChunkPayload($chunk));
    }

    /**
     * @return array<string, mixed>
     */
    private function proctoringRecordingChunkPayload(ProctoringRecordingChunk $chunk): array
    {
        return [
            'id' => $chunk->id,
            'recording_type' => $chunk->recording_type,
            'sequence' => $chunk->sequence,
            'mime_type' => $chunk->mime_type,
            'size_bytes' => $chunk->size_bytes,
            'duration_ms' => $chunk->duration_ms,
            'recorded_at' => $chunk->recorded_at?->toISOString(),
            'uploaded_at' => $chunk->uploaded_at?->toISOString(),
            'ip_address' => $chunk->ip_address,
            'user_agent' => $chunk->user_agent,
            'metadata' => $chunk->metadata ?? [],
            'url' => route('admin.proctoring-recording-chunks.show', $chunk),
            'event' => $chunk->event ? [
                'id' => $chunk->event->id,
                'event_type' => $chunk->event->event_type,
                'severity' => $chunk->event->severity,
                'occurred_at' => $chunk->event->occurred_at?->toISOString(),
            ] : null,
        ];
    }
}
