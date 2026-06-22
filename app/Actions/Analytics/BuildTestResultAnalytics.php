<?php

namespace App\Actions\Analytics;

use App\Actions\Proctoring\CalculateProctoringRiskScore;
use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class BuildTestResultAnalytics
{
    public function __construct(
        private readonly CalculateProctoringRiskScore $calculateRiskScore,
    ) {}

    /**
     * @param  array{from?: string|null, to?: string|null, status?: string|null, review_status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function handle(Test $test, array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $attempts = $this->filteredAttempts($test, $normalizedFilters);
        $submittedAttempts = $attempts
            ->filter(fn (TestAttempt $attempt): bool => $attempt->submitted_at !== null || $attempt->status === AttemptStatus::Submitted)
            ->values();
        $submittedAttemptIds = $submittedAttempts->modelKeys();
        $answers = $this->submittedAnswers($submittedAttemptIds);
        $questions = $test->questions()
            ->select(['id', 'test_id', 'type', 'body', 'marks', 'order'])
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        $riskRows = $attempts
            ->map(fn (TestAttempt $attempt): array => [
                'attempt' => $attempt,
                'risk' => $this->calculateRiskScore->handle($attempt),
            ])
            ->values();

        return [
            'filters' => $normalizedFilters,
            'overview' => $this->overviewPayload($test, $attempts, $submittedAttempts),
            'score_summary' => $this->scoreSummaryPayload($submittedAttempts, $answers),
            'status_breakdown' => $this->statusBreakdownPayload($test, $attempts),
            'risk_breakdown' => $this->riskBreakdownPayload($riskRows),
            'review_breakdown' => $this->reviewBreakdownPayload($attempts),
            'timing_summary' => $this->timingSummaryPayload($submittedAttempts),
            'question_analytics' => $this->questionAnalyticsPayload($questions, $answers),
            'top_suspicious_attempts' => $this->topSuspiciousAttemptsPayload($riskRows, $test),
            'submission_trend' => $this->submissionTrendPayload($submittedAttempts),
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, status?: string|null, review_status?: string|null}  $filters
     * @return array{from: string|null, to: string|null, status: string|null, review_status: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'from' => ($filters['from'] ?? null) ?: null,
            'to' => ($filters['to'] ?? null) ?: null,
            'status' => ($filters['status'] ?? null) ?: null,
            'review_status' => ($filters['review_status'] ?? null) ?: null,
        ];
    }

    /**
     * @param  array{from: string|null, to: string|null, status: string|null, review_status: string|null}  $filters
     * @return Collection<int, TestAttempt>
     */
    private function filteredAttempts(Test $test, array $filters): Collection
    {
        $query = $test->attempts()
            ->select([
                'id',
                'test_id',
                'invitation_id',
                'candidate_user_id',
                'status',
                'started_at',
                'submitted_at',
                'expires_at',
                'score',
                'max_score',
                'total_marks',
                'percentage',
                'passed',
                'created_at',
            ])
            ->with([
                'invitation:id,test_id,name,email,status',
                'candidate:id,name,email',
                'candidateDetail:id,test_attempt_id,name,email',
                'proctoringEvents:id,test_attempt_id,event_type,severity',
                'proctoringReview:id,test_attempt_id,status',
            ]);

        $this->applyAttemptFilters($query, $filters);

        return $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  list<int>  $attemptIds
     * @return Collection<int, AttemptAnswer>
     */
    private function submittedAnswers(array $attemptIds): Collection
    {
        if ($attemptIds === []) {
            return collect();
        }

        return AttemptAnswer::query()
            ->select([
                'id',
                'test_attempt_id',
                'question_id',
                'is_correct',
                'score',
            ])
            ->with([
                'question:id,test_id,type,body,marks,order',
            ])
            ->whereIn('test_attempt_id', $attemptIds)
            ->get();
    }

    /**
     * @param  array{from: string|null, to: string|null, status: string|null, review_status: string|null}  $filters
     */
    private function applyAttemptFilters(Builder|HasMany $query, array $filters): void
    {
        if ($filters['from']) {
            $query->where(function (Builder $dateQuery) use ($filters): void {
                $dateQuery
                    ->whereDate('started_at', '>=', $filters['from'])
                    ->orWhere(function (Builder $fallbackQuery) use ($filters): void {
                        $fallbackQuery
                            ->whereNull('started_at')
                            ->whereDate('created_at', '>=', $filters['from']);
                    });
            });
        }

        if ($filters['to']) {
            $query->where(function (Builder $dateQuery) use ($filters): void {
                $dateQuery
                    ->whereDate('started_at', '<=', $filters['to'])
                    ->orWhere(function (Builder $fallbackQuery) use ($filters): void {
                        $fallbackQuery
                            ->whereNull('started_at')
                            ->whereDate('created_at', '<=', $filters['to']);
                    });
            });
        }

        if ($filters['status'] === 'expired') {
            $query
                ->where('status', AttemptStatus::InProgress->value)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now());
        } elseif ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['review_status'] === 'needs_review') {
            $query->where(function (Builder $reviewQuery): void {
                $reviewQuery
                    ->whereDoesntHave('proctoringReview')
                    ->orWhereHas('proctoringReview', fn (Builder $builder) => $builder->where('status', 'needs_review'));
            });

            return;
        }

        if ($filters['review_status']) {
            $query->whereHas('proctoringReview', fn (Builder $builder) => $builder->where('status', $filters['review_status']));
        }
    }

    /**
     * @param  Collection<int, TestAttempt>  $attempts
     * @param  Collection<int, TestAttempt>  $submittedAttempts
     * @return array<string, int|float|null>
     */
    private function overviewPayload(Test $test, Collection $attempts, Collection $submittedAttempts): array
    {
        $totalInvitations = $test->invitations()->count();
        $acceptedInvitations = $test->invitations()
            ->whereNotNull('accepted_at')
            ->count();
        $passCount = $submittedAttempts->where('passed', true)->count();
        $failCount = $submittedAttempts->where('passed', false)->count();
        $submittedCount = $submittedAttempts->count();

        return [
            'total_invitations' => $totalInvitations,
            'accepted_invitations' => $acceptedInvitations,
            'started_attempts' => $attempts->count(),
            'submitted_attempts' => $submittedCount,
            'in_progress_attempts' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => $attempt->status === AttemptStatus::InProgress && ! $attempt->isExpired())
                ->count(),
            'pass_count' => $passCount,
            'fail_count' => $failCount,
            'pass_rate' => $submittedCount > 0
                ? round(($passCount / $submittedCount) * 100, 2)
                : null,
        ];
    }

    /**
     * @param  Collection<int, TestAttempt>  $submittedAttempts
     * @param  Collection<int, AttemptAnswer>  $answers
     * @return array<string, float|int|null>
     */
    private function scoreSummaryPayload(Collection $submittedAttempts, Collection $answers): array
    {
        if ($submittedAttempts->isEmpty()) {
            return [
                'average_score' => null,
                'highest_score' => null,
                'lowest_score' => null,
                'average_percentage' => null,
                'pass_percentage' => null,
                'mcq_average_score' => null,
                'coding_average_score' => null,
            ];
        }

        $mcqAverage = $this->averageTypeScorePerAttempt(
            $submittedAttempts,
            $answers,
            QuestionType::Mcq->value,
        );
        $codingAverage = $this->averageTypeScorePerAttempt(
            $submittedAttempts,
            $answers,
            QuestionType::Coding->value,
        );

        return [
            'average_score' => round((float) $submittedAttempts->avg('score'), 2),
            'highest_score' => (float) $submittedAttempts->max('score'),
            'lowest_score' => (float) $submittedAttempts->min('score'),
            'average_percentage' => round(
                (float) $submittedAttempts
                    ->filter(fn (TestAttempt $attempt): bool => $attempt->percentage !== null)
                    ->avg(fn (TestAttempt $attempt): float => (float) $attempt->percentage),
                2,
            ),
            'pass_percentage' => round(
                ($submittedAttempts->where('passed', true)->count() / $submittedAttempts->count()) * 100,
                2,
            ),
            'mcq_average_score' => $mcqAverage,
            'coding_average_score' => $codingAverage,
        ];
    }

    /**
     * @param  Collection<int, TestAttempt>  $submittedAttempts
     * @param  Collection<int, AttemptAnswer>  $answers
     */
    private function averageTypeScorePerAttempt(
        Collection $submittedAttempts,
        Collection $answers,
        string $questionType,
    ): float {
        if ($submittedAttempts->isEmpty()) {
            return 0.0;
        }

        $scoresByAttempt = $answers
            ->filter(fn (AttemptAnswer $answer): bool => $answer->question?->type === $questionType)
            ->groupBy('test_attempt_id')
            ->map(fn (Collection $group): float => (float) $group->sum('score'));

        return round(
            $submittedAttempts
                ->map(fn (TestAttempt $attempt): float => (float) ($scoresByAttempt->get($attempt->id) ?? 0))
                ->avg() ?? 0,
            2,
        );
    }

    /**
     * @param  Collection<int, TestAttempt>  $attempts
     * @return array<string, int>
     */
    private function statusBreakdownPayload(Test $test, Collection $attempts): array
    {
        return [
            'not_started' => $test->invitations()
                ->whereDoesntHave('attempt')
                ->count(),
            'in_progress' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => $attempt->status === AttemptStatus::InProgress && ! $attempt->isExpired())
                ->count(),
            'submitted' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => $attempt->status === AttemptStatus::Submitted)
                ->count(),
            'expired' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => $attempt->isExpired())
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, array{attempt: TestAttempt, risk: array{score: int, level: string, event_count: int, breakdown: array<int, array<string, mixed>>}}>  $riskRows
     * @return array<string, int|float>
     */
    private function riskBreakdownPayload(Collection $riskRows): array
    {
        return [
            'low_count' => $riskRows->where('risk.level', 'low')->count(),
            'medium_count' => $riskRows->where('risk.level', 'medium')->count(),
            'high_count' => $riskRows->where('risk.level', 'high')->count(),
            'critical_count' => $riskRows->where('risk.level', 'critical')->count(),
            'average_risk_score' => $riskRows->isNotEmpty()
                ? round((float) $riskRows->avg('risk.score'), 2)
                : 0,
            'highest_risk_score' => $riskRows->isNotEmpty()
                ? (int) $riskRows->max('risk.score')
                : 0,
        ];
    }

    /**
     * @param  Collection<int, TestAttempt>  $attempts
     * @return array<string, int>
     */
    private function reviewBreakdownPayload(Collection $attempts): array
    {
        return [
            'needs_review' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => ($attempt->proctoringReview?->status ?? 'needs_review') === 'needs_review')
                ->count(),
            'approved' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => $attempt->proctoringReview?->status === 'approved')
                ->count(),
            'flagged' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => $attempt->proctoringReview?->status === 'flagged')
                ->count(),
            'rejected' => $attempts
                ->filter(fn (TestAttempt $attempt): bool => $attempt->proctoringReview?->status === 'rejected')
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, TestAttempt>  $submittedAttempts
     * @return array<string, int|float|null>
     */
    private function timingSummaryPayload(Collection $submittedAttempts): array
    {
        $durations = $submittedAttempts
            ->filter(fn (TestAttempt $attempt): bool => $attempt->submitted_at !== null && $attempt->started_at !== null)
            ->map(fn (TestAttempt $attempt): int => $attempt->started_at->diffInSeconds($attempt->submitted_at))
            ->values();

        if ($durations->isEmpty()) {
            return [
                'average_completion_seconds' => null,
                'fastest_completion_seconds' => null,
                'slowest_completion_seconds' => null,
                'average_time_before_submission_seconds' => null,
            ];
        }

        $average = round((float) $durations->avg(), 2);

        return [
            'average_completion_seconds' => $average,
            'fastest_completion_seconds' => (int) $durations->min(),
            'slowest_completion_seconds' => (int) $durations->max(),
            'average_time_before_submission_seconds' => $average,
        ];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  Collection<int, AttemptAnswer>  $answers
     * @return list<array<string, mixed>>
     */
    private function questionAnalyticsPayload(Collection $questions, Collection $answers): array
    {
        return $questions
            ->map(function (Question $question) use ($answers): array {
                $questionAnswers = $answers->where('question_id', $question->id)->values();
                $attemptedCount = $questionAnswers->count();
                $fullScoreCount = $questionAnswers
                    ->filter(fn (AttemptAnswer $answer): bool => (float) $answer->score >= (float) $question->marks)
                    ->count();
                $successCount = $question->type === QuestionType::Mcq->value
                    ? $questionAnswers->where('is_correct', true)->count()
                    : $fullScoreCount;

                return [
                    'question_id' => $question->id,
                    'order' => $question->order,
                    'type' => $question->type,
                    'body' => $question->body,
                    'marks' => $question->marks,
                    'attempted_count' => $attemptedCount,
                    'average_awarded_score' => $attemptedCount > 0
                        ? round((float) $questionAnswers->avg('score'), 2)
                        : 0,
                    'average_percentage' => $attemptedCount > 0 && (float) $question->marks > 0
                        ? round(
                            $questionAnswers
                                ->avg(fn (AttemptAnswer $answer): float => ((float) $answer->score / (float) $question->marks) * 100)
                            ?? 0,
                            2,
                        )
                        : 0,
                    'zero_score_count' => $questionAnswers
                        ->filter(fn (AttemptAnswer $answer): bool => (float) $answer->score <= 0)
                        ->count(),
                    'full_score_count' => $fullScoreCount,
                    'success_rate' => $attemptedCount > 0
                        ? round(($successCount / $attemptedCount) * 100, 2)
                        : 0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array{attempt: TestAttempt, risk: array{score: int, level: string, event_count: int, breakdown: array<int, array<string, mixed>>}}>  $riskRows
     * @return list<array<string, mixed>>
     */
    private function topSuspiciousAttemptsPayload(Collection $riskRows, Test $test): array
    {
        return $riskRows
            ->map(function (array $row) use ($test): array {
                /** @var TestAttempt $attempt */
                $attempt = $row['attempt'];

                return [
                    'attempt_id' => $attempt->id,
                    'candidate_name' => $attempt->candidateDetail?->name
                        ?? $attempt->invitation?->name
                        ?? $attempt->candidate?->name,
                    'candidate_email' => $attempt->candidateDetail?->email
                        ?? $attempt->invitation?->email
                        ?? $attempt->candidate?->email,
                    'submitted_at' => $attempt->submitted_at?->toISOString(),
                    'score' => $attempt->score,
                    'max_score' => $attempt->max_score,
                    'percentage' => $attempt->percentage !== null
                        ? (float) $attempt->percentage
                        : null,
                    'review_status' => $attempt->proctoringReview?->status ?? 'needs_review',
                    'risk' => $row['risk'],
                    'result_url' => route('admin.tests.results.show', [$test, $attempt]),
                ];
            })
            ->sortByDesc(fn (array $row): string => sprintf(
                '%010d-%s',
                (int) $row['risk']['score'],
                $row['submitted_at'] ?? '0000-00-00T00:00:00Z',
            ))
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TestAttempt>  $submittedAttempts
     * @return list<array<string, mixed>>
     */
    private function submissionTrendPayload(Collection $submittedAttempts): array
    {
        return $submittedAttempts
            ->filter(fn (TestAttempt $attempt): bool => $attempt->submitted_at !== null)
            ->groupBy(fn (TestAttempt $attempt): string => $attempt->submitted_at->toDateString())
            ->map(function (Collection $group, string $date): array {
                return [
                    'date' => $date,
                    'submitted_count' => $group->count(),
                    'average_score' => round((float) $group->avg('score'), 2),
                ];
            })
            ->sortBy('date')
            ->values()
            ->all();
    }
}
