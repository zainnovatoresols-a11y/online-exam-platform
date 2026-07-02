<?php

namespace App\Actions\Results;

use App\Actions\Proctoring\CalculateProctoringRiskScore;
use App\Models\TestAttempt;

class DetermineAttemptFinalOutcome
{
    public function __construct(
        private readonly CalculateProctoringRiskScore $calculateRiskScore,
    ) {}

    /**
     * @return array{
     *     score_passed: bool,
     *     suspicious_event_count: int,
     *     proctoring_failed: bool,
     *     final_passed: bool,
     *     failure_reason: string|null
     * }
     */
    public function handle(
        TestAttempt $attempt,
        ?float $percentage = null,
        ?int $passMark = null,
    ): array {
        $attempt->loadMissing('test:id,pass_mark');

        $percentage ??= $attempt->percentage !== null
            ? (float) $attempt->percentage
            : null;
        $passMark ??= (int) ($attempt->test?->pass_mark ?? 0);

        $scorePassed = $percentage !== null && $percentage >= $passMark;
        $suspiciousEventCount = (int) $this->calculateRiskScore->handle($attempt)['event_count'];
        $proctoringFailed = $suspiciousEventCount >= 2;

        return [
            'score_passed' => $scorePassed,
            'suspicious_event_count' => $suspiciousEventCount,
            'proctoring_failed' => $proctoringFailed,
            'final_passed' => $scorePassed && ! $proctoringFailed,
            'failure_reason' => $proctoringFailed
                ? 'Failed due to proctoring violations'
                : null,
        ];
    }

    /**
     * @return array{
     *     score_passed: bool,
     *     suspicious_event_count: int,
     *     proctoring_failed: bool,
     *     final_passed: bool,
     *     failure_reason: string|null
     * }
     */
    public function __invoke(
        TestAttempt $attempt,
        ?float $percentage = null,
        ?int $passMark = null,
    ): array {
        return $this->handle($attempt, $percentage, $passMark);
    }
}
