<?php

namespace App\Actions\Results;

use App\Models\AttemptProctoringReview;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;

class UpdateAttemptProctoringReview
{
    /**
     * @param  array{status: string, risk_level?: string|null, reason_codes?: list<string>|null, notes?: string|null}  $data
     */
    public function handle(Test $test, TestAttempt $attempt, User $reviewer, array $data): AttemptProctoringReview
    {
        abort_unless((int) $attempt->test_id === (int) $test->id, 404);

        return AttemptProctoringReview::query()->updateOrCreate([
            'test_attempt_id' => $attempt->id,
        ], [
            'test_id' => $test->id,
            'organization_id' => $attempt->organization_id ?? $test->organization_id,
            'reviewed_by_user_id' => $reviewer->id,
            'status' => $data['status'],
            'risk_level' => $data['risk_level'] ?? null,
            'reason_codes' => array_values($data['reason_codes'] ?? []),
            'notes' => $data['notes'] ?? null,
            'reviewed_at' => now(),
        ]);
    }
}
