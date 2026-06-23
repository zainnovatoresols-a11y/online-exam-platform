<?php

namespace App\Jobs;

use App\Actions\Attempts\GradeCodingQuestion;
use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\CodeExecutionRun;
use App\Models\Question;
use App\Models\TestAttempt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GradeAttemptCodingAnswers implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(public int $attemptId) {}

    public function handle(GradeCodingQuestion $gradeCodingQuestion): void
    {
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

        $this->refreshAttemptScore($attempt);
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

    private function refreshAttemptScore(TestAttempt $attempt): void
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

        $attempt->update([
            'score' => $score,
            'max_score' => $maxScore,
            'total_marks' => $maxScore,
            'percentage' => $percentage,
            'passed' => $percentage >= (int) $attempt->test->pass_mark,
        ]);
    }
}
