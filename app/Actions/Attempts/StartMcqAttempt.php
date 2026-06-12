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

        return TestAttempt::firstOrCreate(
            [
                'test_id' => $test->id,
                'candidate_user_id' => $candidate->id,
            ],
            [
                'invitation_id' => $invitation->id,
                'status' => AttemptStatus::InProgress,
                'started_at' => now(),
                'score' => 0,
                'total_marks' => (int) $test->questions()->sum('marks'),
            ],
        );
    }
}
