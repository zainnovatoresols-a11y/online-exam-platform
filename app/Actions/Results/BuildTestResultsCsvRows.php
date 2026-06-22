<?php

namespace App\Actions\Results;

use App\Enums\QuestionType;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\ProctoringEvent;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildTestResultsCsvRows
{
    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'Candidate Name',
            'Candidate Email',
            'Candidate Phone',
            'Test Title',
            'Organization',
            'Attempt Status',
            'Started At',
            'Submitted At',
            'Score',
            'Max Score',
            'Percentage',
            'Pass/Fail',
            'MCQ Score',
            'Coding Score',
            'Proctoring Total Events',
            'High Severity Events',
            'Fullscreen Exits',
            'Tab Switches',
            'Clipboard Attempts',
            'Recording Permission Denials',
            'Screen Share Ended Count',
            'Proctoring Review Status',
            'Proctoring Risk Level',
            'Reviewed By',
            'Reviewed At',
        ];
    }

    /**
     * @param  resource  $handle
     */
    public function writeRows(Test $test, mixed $handle): void
    {
        $test->attempts()
            ->with([
                'candidate:id,name,email,phone,stack_name',
                'candidateDetail',
                'invitation:id,test_id,candidate_user_id,name,email,candidate_profile',
                'invitation.candidate:id,name,email,phone,stack_name',
                'invitation.candidateDetail',
                'answers.question:id,type',
                'proctoringEvents:id,test_attempt_id,event_type,severity',
                'proctoringReview.reviewedBy:id,name,email',
            ])
            ->orderBy('id')
            ->chunkById(200, function (Collection $attempts) use ($handle, $test): void {
                $attempts->each(function (TestAttempt $attempt) use ($handle, $test): void {
                    fputcsv($handle, $this->row($test, $attempt));
                });
            });
    }

    /**
     * @return list<string|int|float|null>
     */
    private function row(Test $test, TestAttempt $attempt): array
    {
        $candidate = $this->candidatePayload(
            $attempt->candidateDetail ?? $attempt->invitation?->candidateDetail,
            $attempt->candidate ?? $attempt->invitation?->candidate,
            $attempt->invitation,
        );
        $summary = $this->proctoringSummary($attempt->proctoringEvents);
        $review = $attempt->proctoringReview;

        return [
            $candidate['name'],
            $candidate['email'],
            $candidate['phone'],
            $test->title,
            $test->organization?->name ?? 'Solo',
            $attempt->status->value,
            $this->dateValue($attempt->started_at),
            $this->dateValue($attempt->submitted_at),
            (float) $attempt->score,
            (float) $attempt->max_score,
            $attempt->percentage !== null ? (float) $attempt->percentage : null,
            $attempt->passed === null ? 'Pending' : ($attempt->passed ? 'Passed' : 'Failed'),
            $this->scoreForType($attempt, QuestionType::Mcq->value),
            $this->scoreForType($attempt, QuestionType::Coding->value),
            $summary['total'],
            $summary['high'],
            $summary['fullscreen_exits'],
            $summary['tab_switches'],
            $summary['clipboard_attempts'],
            $summary['recording_permission_denials'],
            $summary['screen_share_ended'],
            $review?->status ?? 'needs_review',
            $review?->risk_level,
            $review?->reviewedBy?->name,
            $this->dateValue($review?->reviewed_at),
        ];
    }

    private function scoreForType(TestAttempt $attempt, string $type): float
    {
        return (float) $attempt->answers
            ->filter(fn ($answer): bool => $answer->question?->type === $type)
            ->sum('score');
    }

    /**
     * @return array{name: ?string, email: ?string, phone: ?string}
     */
    private function candidatePayload(?CandidateTestDetail $detail, ?User $candidate, ?Invitation $invitation): array
    {
        $fields = $detail?->fields ?? $invitation?->candidate_profile ?? [];

        return [
            'name' => $detail?->name ?? $invitation?->name ?? $candidate?->name,
            'email' => $detail?->email ?? $invitation?->email ?? $candidate?->email,
            'phone' => $detail?->phone ?? $candidate?->phone ?? ($fields['phone'] ?? null),
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
            'fullscreen_exits' => $events->where('event_type', 'fullscreen_exited')->count(),
            'tab_switches' => $events->where('event_type', 'tab_hidden')->count(),
            'clipboard_attempts' => $events
                ->whereIn('event_type', ['copy_attempt', 'paste_attempt', 'cut_attempt'])
                ->count(),
            'recording_permission_denials' => $events
                ->whereIn('event_type', [
                    'camera_recording_permission_denied',
                    'screen_recording_permission_denied',
                ])
                ->count(),
            'screen_share_ended' => $events->where('event_type', 'screen_share_ended')->count(),
        ];
    }

    private function dateValue(mixed $date): ?string
    {
        return $date?->toDateTimeString();
    }
}
