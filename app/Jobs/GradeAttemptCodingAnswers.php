<?php

namespace App\Jobs;

use App\Actions\Attempts\GradeCodingQuestion;
use App\Actions\Results\DetermineAttemptFinalOutcome;
use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\CodeExecutionRun;
use App\Models\Question;
use App\Models\TestAttempt;
use App\Services\CodeExecution\CodeExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GradeAttemptCodingAnswers implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(
        public int $attemptId,
        public ?string $expectedDriver = null,
    ) {}

    public function handle(): void
    {
        $determineFinalOutcome = app(DetermineAttemptFinalOutcome::class);

        if (is_string($this->expectedDriver) && $this->expectedDriver !== '') {
            config(['code_execution.driver' => $this->expectedDriver]);
            app()->forgetInstance(CodeExecutionService::class);
        }

        /** @var GradeCodingQuestion $gradeCodingQuestion */
        $gradeCodingQuestion = app(GradeCodingQuestion::class);

        $attempt = TestAttempt::query()
            ->with([
                'test.questions' => fn ($query) => $query
                    ->orderBy('order')
                    ->orderBy('id'),
                'test.questions.testCases' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->find($this->attemptId);

        if (! $attempt || $attempt->status !== AttemptStatus::Submitted) {
            return;
        }

        $codingQuestions = $attempt->test->questions
            ->where('type', QuestionType::Coding->value)
            ->values();

        foreach ($codingQuestions as $question) {
            $run = $this->queuedFinalRun($attempt, $question);

            try {
                $gradeCodingQuestion->handle($attempt, $question, $run);
            } catch (Throwable $exception) {
                report($exception);

                $attempt->answers()
                    ->where('question_id', $question->id)
                    ->update([
                        'is_correct' => false,
                        'score' => 0,
                        'answered_at' => now(),
                    ]);
            }
        }

        $this->refreshAttemptScore($attempt, $determineFinalOutcome);
    }

    private function queuedFinalRun(TestAttempt $attempt, Question $question): ?CodeExecutionRun
    {
        return CodeExecutionRun::query()
            ->where('test_attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->where('run_type', 'final')
            ->where('status', 'queued')
            ->latest('id')
            ->first();
    }

    private function refreshAttemptScore(TestAttempt $attempt, DetermineAttemptFinalOutcome $determineFinalOutcome): void
    {
        $attempt->load([
            'test.questions',
            'answers.question',
        ]);

        $score = (int) $attempt->answers->sum('score');
        $maxScore = (int) $attempt->test->questions->sum('marks');
        $percentage = $maxScore > 0
            ? round(($score / $maxScore) * 100, 2)
            : 0;
        $outcome = $determineFinalOutcome->handle(
            $attempt,
            $percentage,
            (int) $attempt->test->pass_mark,
        );

        $attempt->update([
            'score' => $score,
            'max_score' => $maxScore,
            'total_marks' => $maxScore,
            'percentage' => $percentage,
            'passed' => $outcome['final_passed'],
            'score_passed' => $outcome['score_passed'],
            'proctoring_failed' => $outcome['proctoring_failed'],
            'suspicious_event_count' => $outcome['suspicious_event_count'],
            'final_failure_reason' => $outcome['failure_reason'],
        ]);
    }
}
