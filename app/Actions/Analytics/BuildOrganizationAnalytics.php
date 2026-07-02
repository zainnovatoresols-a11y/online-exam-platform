<?php

namespace App\Actions\Analytics;

use App\Actions\Proctoring\CalculateProctoringRiskScore;
use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\TestStatus;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildOrganizationAnalytics
{
    public function __construct(
        private readonly CalculateProctoringRiskScore $calculateRiskScore,
    ) {}

    /**
     * @param  array{from?: string|null, to?: string|null, test_status?: string|null, review_status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function handle(Organization $organization, array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $admins = $this->admins($organization);
        $tests = $this->filteredTests($organization, $normalizedFilters);
        $testIds = $tests->modelKeys();
        $invitations = $this->filteredInvitations($organization, $testIds, $normalizedFilters);
        $attempts = $this->filteredAttempts($organization, $testIds, $normalizedFilters);
        $submittedAttempts = $attempts
            ->filter(fn (TestAttempt $attempt): bool => $attempt->submitted_at !== null || $attempt->status === AttemptStatus::Submitted)
            ->values();
        $riskRows = $attempts
            ->map(fn (TestAttempt $attempt): array => [
                'attempt' => $attempt,
                'risk' => $this->calculateRiskScore->handle($attempt),
            ])
            ->values();

        return [
            'filters' => $normalizedFilters,
            'overview' => $this->overviewPayload($admins, $tests, $invitations, $attempts, $submittedAttempts, $riskRows),
            'test_status_breakdown' => $this->testStatusBreakdownPayload($tests),
            'attempt_status_breakdown' => $this->attemptStatusBreakdownPayload($invitations, $attempts),
            'score_summary' => $this->scoreSummaryPayload($submittedAttempts),
            'risk_breakdown' => $this->riskBreakdownPayload($riskRows),
            'review_breakdown' => $this->reviewBreakdownPayload($attempts),
            'admin_activity' => $this->adminActivityPayload($admins, $tests, $invitations, $attempts),
            'test_summaries' => $this->testSummariesPayload($tests, $invitations, $attempts, $riskRows),
            'top_suspicious_attempts' => $this->topSuspiciousAttemptsPayload($riskRows),
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, test_status?: string|null, review_status?: string|null}  $filters
     * @return array{from: string|null, to: string|null, test_status: string|null, review_status: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'from' => ($filters['from'] ?? null) ?: null,
            'to' => ($filters['to'] ?? null) ?: null,
            'test_status' => ($filters['test_status'] ?? null) ?: null,
            'review_status' => ($filters['review_status'] ?? null) ?: null,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function admins(Organization $organization): Collection
    {
        return $organization->users()
            ->adminAccounts()
            ->select(['id', 'organization_id', 'name', 'email'])
            ->latest('id')
            ->get();
    }

    /**
     * @param  array{from: string|null, to: string|null, test_status: string|null, review_status: string|null}  $filters
     * @return Collection<int, Test>
     */
    private function filteredTests(Organization $organization, array $filters): Collection
    {
        $query = $organization->tests()
            ->select([
                'id',
                'organization_id',
                'created_by_id',
                'title',
                'status',
                'created_at',
                'published_at',
                'closed_at',
            ])
            ->with(['creator:id,name,email']);

        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if ($filters['test_status']) {
            $query->where('status', $filters['test_status']);
        }

        return $query
            ->latest('id')
            ->get();
    }

    /**
     * @param  list<int>  $testIds
     * @param  array{from: string|null, to: string|null, test_status: string|null, review_status: string|null}  $filters
     * @return Collection<int, Invitation>
     */
    private function filteredInvitations(Organization $organization, array $testIds, array $filters): Collection
    {
        if ($testIds === []) {
            return collect();
        }

        $query = Invitation::query()
            ->select([
                'id',
                'organization_id',
                'test_id',
                'invited_by',
                'candidate_user_id',
                'name',
                'email',
                'status',
                'accepted_at',
                'created_at',
            ])
            ->where('organization_id', $organization->id)
            ->whereIn('test_id', $testIds)
            ->with(['attempt:id,invitation_id,status,started_at,submitted_at,expires_at,created_at']);

        if ($filters['review_status']) {
            $query->whereHas('attempt', function (Builder $attemptQuery) use ($filters): void {
                $this->applyAttemptReviewFilter($attemptQuery, $filters['review_status']);
            });
        } else {
            $this->applyInvitationDateFilters($query, $filters);
        }

        return $query
            ->latest('id')
            ->get();
    }

    /**
     * @param  list<int>  $testIds
     * @param  array{from: string|null, to: string|null, test_status: string|null, review_status: string|null}  $filters
     * @return Collection<int, TestAttempt>
     */
    private function filteredAttempts(Organization $organization, array $testIds, array $filters): Collection
    {
        if ($testIds === []) {
            return collect();
        }

        $query = TestAttempt::query()
            ->select([
                'id',
                'organization_id',
                'test_id',
                'invitation_id',
                'candidate_user_id',
                'status',
                'started_at',
                'submitted_at',
                'expires_at',
                'score',
                'max_score',
                'percentage',
                'passed',
                'created_at',
            ])
            ->where('organization_id', $organization->id)
            ->whereIn('test_id', $testIds)
            ->with([
                'test:id,title,status,created_by_id',
                'invitation:id,test_id,name,email,status,invited_by',
                'candidate:id,name,email',
                'candidateDetail:id,test_attempt_id,name,email',
                'proctoringEvents:id,test_attempt_id,event_type,severity,metadata,occurred_at',
                'proctoringReview:id,test_attempt_id,status',
            ]);

        $this->applyAttemptDateFilters($query, $filters);

        if ($filters['review_status']) {
            $this->applyAttemptReviewFilter($query, $filters['review_status']);
        }

        return $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{from: string|null, to: string|null, test_status: string|null, review_status: string|null}  $filters
     */
    private function applyAttemptDateFilters(Builder $query, array $filters): void
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
    }

    /**
     * @param  array{from: string|null, to: string|null, test_status: string|null, review_status: string|null}  $filters
     */
    private function applyInvitationDateFilters(Builder $query, array $filters): void
    {
        if ($filters['from']) {
            $query->where(function (Builder $dateQuery) use ($filters): void {
                $dateQuery
                    ->where(function (Builder $acceptedQuery) use ($filters): void {
                        $acceptedQuery
                            ->whereNotNull('accepted_at')
                            ->whereDate('accepted_at', '>=', $filters['from']);
                    })
                    ->orWhere(function (Builder $fallbackQuery) use ($filters): void {
                        $fallbackQuery
                            ->whereNull('accepted_at')
                            ->whereDate('created_at', '>=', $filters['from']);
                    });
            });
        }

        if ($filters['to']) {
            $query->where(function (Builder $dateQuery) use ($filters): void {
                $dateQuery
                    ->where(function (Builder $acceptedQuery) use ($filters): void {
                        $acceptedQuery
                            ->whereNotNull('accepted_at')
                            ->whereDate('accepted_at', '<=', $filters['to']);
                    })
                    ->orWhere(function (Builder $fallbackQuery) use ($filters): void {
                        $fallbackQuery
                            ->whereNull('accepted_at')
                            ->whereDate('created_at', '<=', $filters['to']);
                    });
            });
        }
    }

    private function applyAttemptReviewFilter(Builder $query, string $reviewStatus): void
    {
        if ($reviewStatus === 'needs_review') {
            $query->where(function (Builder $reviewQuery): void {
                $reviewQuery
                    ->whereDoesntHave('proctoringReview')
                    ->orWhereHas('proctoringReview', fn (Builder $builder) => $builder->where('status', 'needs_review'));
            });

            return;
        }

        $query->whereHas('proctoringReview', fn (Builder $builder) => $builder->where('status', $reviewStatus));
    }

    /**
     * @param  Collection<int, User>  $admins
     * @param  Collection<int, Test>  $tests
     * @param  Collection<int, Invitation>  $invitations
     * @param  Collection<int, TestAttempt>  $attempts
     * @param  Collection<int, TestAttempt>  $submittedAttempts
     * @param  Collection<int, array{attempt: TestAttempt, risk: array<string, mixed>}>  $riskRows
     * @return array<string, int|float|null>
     */
    private function overviewPayload(
        Collection $admins,
        Collection $tests,
        Collection $invitations,
        Collection $attempts,
        Collection $submittedAttempts,
        Collection $riskRows,
    ): array {
        $passCount = $submittedAttempts->where('passed', true)->count();
        $failCount = $submittedAttempts->where('passed', false)->count();
        $submittedCount = $submittedAttempts->count();

        return [
            'total_admins' => $admins->count(),
            'total_tests' => $tests->count(),
            'total_invitations' => $invitations->count(),
            'accepted_invitations' => $invitations
                ->filter(fn (Invitation $invitation): bool => $invitation->status === InvitationStatus::Accepted)
                ->count(),
            'unique_candidates' => $this->uniqueCandidateCount($invitations, $attempts),
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
            'high_risk_attempts' => $riskRows
                ->filter(fn (array $row): bool => in_array($row['risk']['level'], ['high', 'critical'], true))
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, Invitation>  $invitations
     * @param  Collection<int, TestAttempt>  $attempts
     */
    private function uniqueCandidateCount(Collection $invitations, Collection $attempts): int
    {
        return $invitations
            ->map(fn (Invitation $invitation): ?string => $invitation->email ? strtolower($invitation->email) : null)
            ->merge($attempts->map(function (TestAttempt $attempt): ?string {
                $email = $attempt->candidateDetail?->email
                    ?? $attempt->invitation?->email
                    ?? $attempt->candidate?->email;

                if ($email) {
                    return strtolower($email);
                }

                return $attempt->candidate_user_id !== null
                    ? 'user:'.$attempt->candidate_user_id
                    : null;
            }))
            ->filter()
            ->unique()
            ->count();
    }

    /**
     * @param  Collection<int, Test>  $tests
     * @return array<string, int>
     */
    private function testStatusBreakdownPayload(Collection $tests): array
    {
        return [
            'draft' => $tests->where('status', TestStatus::Draft->value)->count(),
            'published' => $tests->where('status', TestStatus::Published->value)->count(),
            'closed' => $tests->where('status', TestStatus::Closed->value)->count(),
        ];
    }

    /**
     * @param  Collection<int, Invitation>  $invitations
     * @param  Collection<int, TestAttempt>  $attempts
     * @return array<string, int>
     */
    private function attemptStatusBreakdownPayload(Collection $invitations, Collection $attempts): array
    {
        return [
            'not_started' => $invitations
                ->filter(fn (Invitation $invitation): bool => $invitation->attempt === null)
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
     * @param  Collection<int, TestAttempt>  $submittedAttempts
     * @return array<string, float|int|null>
     */
    private function scoreSummaryPayload(Collection $submittedAttempts): array
    {
        if ($submittedAttempts->isEmpty()) {
            return [
                'average_score' => null,
                'highest_score' => null,
                'lowest_score' => null,
                'average_percentage' => null,
            ];
        }

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
        ];
    }

    /**
     * @param  Collection<int, array{attempt: TestAttempt, risk: array<string, mixed>}>  $riskRows
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
     * @param  Collection<int, User>  $admins
     * @param  Collection<int, Test>  $tests
     * @param  Collection<int, Invitation>  $invitations
     * @param  Collection<int, TestAttempt>  $attempts
     * @return list<array<string, mixed>>
     */
    private function adminActivityPayload(Collection $admins, Collection $tests, Collection $invitations, Collection $attempts): array
    {
        return $admins
            ->map(function (User $admin) use ($tests, $invitations, $attempts): array {
                $adminTestIds = $tests
                    ->where('created_by_id', $admin->id)
                    ->pluck('id');

                return [
                    'admin_id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'tests_count' => $adminTestIds->count(),
                    'invitations_count' => $invitations
                        ->filter(fn (Invitation $invitation): bool => (int) $invitation->invited_by === (int) $admin->id)
                        ->count(),
                    'attempts_count' => $attempts
                        ->filter(fn (TestAttempt $attempt): bool => $adminTestIds->contains($attempt->test_id))
                        ->count(),
                ];
            })
            ->sortByDesc('tests_count')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Test>  $tests
     * @param  Collection<int, Invitation>  $invitations
     * @param  Collection<int, TestAttempt>  $attempts
     * @param  Collection<int, array{attempt: TestAttempt, risk: array<string, mixed>}>  $riskRows
     * @return list<array<string, mixed>>
     */
    private function testSummariesPayload(Collection $tests, Collection $invitations, Collection $attempts, Collection $riskRows): array
    {
        $riskByTest = $riskRows->groupBy(fn (array $row): int => (int) $row['attempt']->test_id);

        return $tests
            ->map(function (Test $test) use ($invitations, $attempts, $riskByTest): array {
                $testInvitations = $invitations->where('test_id', $test->id);
                $testAttempts = $attempts->where('test_id', $test->id)->values();
                $submittedAttempts = $testAttempts
                    ->filter(fn (TestAttempt $attempt): bool => $attempt->submitted_at !== null || $attempt->status === AttemptStatus::Submitted)
                    ->values();
                $testRiskRows = $riskByTest->get($test->id, collect());
                $submittedCount = $submittedAttempts->count();
                $passCount = $submittedAttempts->where('passed', true)->count();

                return [
                    'test_id' => $test->id,
                    'title' => $test->title,
                    'status' => $test->status,
                    'creator' => $test->creator ? [
                        'id' => $test->creator->id,
                        'name' => $test->creator->name,
                        'email' => $test->creator->email,
                    ] : null,
                    'invitations_count' => $testInvitations->count(),
                    'attempts_count' => $testAttempts->count(),
                    'submitted_attempts_count' => $submittedCount,
                    'pass_rate' => $submittedCount > 0
                        ? round(($passCount / $submittedCount) * 100, 2)
                        : null,
                    'average_score' => $submittedCount > 0
                        ? round((float) $submittedAttempts->avg('score'), 2)
                        : null,
                    'average_risk_score' => $testRiskRows->isNotEmpty()
                        ? round((float) $testRiskRows->avg('risk.score'), 2)
                        : 0,
                    'highest_risk_score' => $testRiskRows->isNotEmpty()
                        ? (int) $testRiskRows->max('risk.score')
                        : 0,
                    'results_url' => route('admin.tests.results.index', $test),
                    'analytics_url' => route('admin.tests.results.analytics', $test),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array{attempt: TestAttempt, risk: array<string, mixed>}>  $riskRows
     * @return list<array<string, mixed>>
     */
    private function topSuspiciousAttemptsPayload(Collection $riskRows): array
    {
        return $riskRows
            ->map(function (array $row): array {
                /** @var TestAttempt $attempt */
                $attempt = $row['attempt'];

                return [
                    'attempt_id' => $attempt->id,
                    'test_id' => $attempt->test_id,
                    'test_title' => $attempt->test?->title,
                    'candidate_name' => $attempt->candidateDetail?->name
                        ?? $attempt->invitation?->name
                        ?? $attempt->candidate?->name,
                    'candidate_email' => $attempt->candidateDetail?->email
                        ?? $attempt->invitation?->email
                        ?? $attempt->candidate?->email,
                    'submitted_at' => $attempt->submitted_at?->toISOString(),
                    'score' => $attempt->score,
                    'percentage' => $attempt->percentage !== null
                        ? (float) $attempt->percentage
                        : null,
                    'review_status' => $attempt->proctoringReview?->status ?? 'needs_review',
                    'risk' => $row['risk'],
                    'result_url' => route('admin.tests.results.show', [$attempt->test_id, $attempt]),
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
}
