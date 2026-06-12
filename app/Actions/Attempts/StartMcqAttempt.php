<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class StartMcqAttempt
{
    /**
     * Start or return the candidate's existing attempt for a test.
     *
     * @throws AuthorizationException
     */
    public function handle(Test $test, User $candidate): TestAttempt
    {
        if (! $test->isPublished()) {
            throw new AuthorizationException('This test is not available.');
        }

        $invitation = Invitation::query()
            ->where('test_id', $test->id)
            ->where('candidate_user_id', $candidate->id)
            ->where('email', $candidate->email)
            ->where('status', InvitationStatus::Accepted->value)
            ->first();

        if (! $invitation) {
            throw new AuthorizationException('You do not have access to this test.');
        }

        $startsAt = $invitation->starts_at ?? $test->starts_at;

        if ($startsAt !== null && now()->lessThan($startsAt)) {
            throw new AuthorizationException('This invitation has not started yet.');
        }

        $startedAt = now();
        $maxScore = (int) $test->questions()->sum('marks');

        $attempt = TestAttempt::firstOrCreate(
            [
                'invitation_id' => $invitation->id,
            ],
            [
                'test_id' => $test->id,
                'candidate_user_id' => $candidate->id,
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

        return $attempt->refresh();
    }
}
