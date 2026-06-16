<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
use App\Models\Invitation;
use App\Models\TestAttempt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class StartPublicMcqAttempt
{
    /**
     * Start or return the attempt identified by a public invitation token.
     *
     * @throws AuthorizationException
     */
    public function handle(Invitation $invitation): TestAttempt
    {
        $invitation->loadMissing(['test', 'candidateDetail']);
        $test = $invitation->test;

        if (! $test->isPublished()) {
            throw new AuthorizationException('This test is not available.');
        }

        if ($invitation->isRevoked()) {
            throw new AuthorizationException('This invitation has been revoked.');
        }

        if ($invitation->hasExpired()) {
            if ($invitation->isAcceptable()) {
                $invitation->update([
                    'status' => InvitationStatus::Expired,
                ]);
            }

            throw new AuthorizationException('This invitation has expired.');
        }

        if (! $invitation->isAccepted() || $invitation->policy_accepted_at === null || ! $invitation->candidateDetail) {
            throw new AuthorizationException('Candidate details are required before starting this test.');
        }

        $startsAt = $invitation->starts_at ?? $test->starts_at;

        if ($startsAt !== null && now()->lessThan($startsAt)) {
            throw new AuthorizationException('This invitation has not started yet.');
        }

        return DB::transaction(function () use ($invitation, $test): TestAttempt {
            $startedAt = now();
            $maxScore = (int) $test->questions()
                ->where('type', QuestionType::Mcq->value)
                ->sum('marks');

            $attempt = TestAttempt::firstOrCreate(
                [
                    'invitation_id' => $invitation->id,
                ],
                [
                    'test_id' => $test->id,
                    'candidate_user_id' => $invitation->candidate_user_id,
                    'organization_id' => $test->organization_id,
                    'status' => AttemptStatus::InProgress,
                    'started_at' => $startedAt,
                    'expires_at' => $startedAt->copy()->addMinutes((int) $test->duration_minutes),
                    'score' => 0,
                    'max_score' => $maxScore,
                    'total_marks' => $maxScore,
                    'percentage' => null,
                    'passed' => null,
                ],
            );

            if (! $attempt->wasRecentlyCreated && ($attempt->expires_at === null || $attempt->max_score === 0)) {
                $attempt->update([
                    'organization_id' => $attempt->organization_id ?? $test->organization_id,
                    'expires_at' => $attempt->expires_at
                        ?? $attempt->started_at?->copy()->addMinutes((int) $test->duration_minutes),
                    'max_score' => $attempt->max_score ?: $maxScore,
                    'total_marks' => $attempt->total_marks ?: $maxScore,
                ]);
            }

            $invitation->candidateDetail()->update([
                'test_attempt_id' => $attempt->id,
            ]);

            return $attempt->refresh();
        });
    }
}
