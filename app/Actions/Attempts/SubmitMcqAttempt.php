<?php

namespace App\Actions\Attempts;

use App\Data\CodeExecution\CodeRunResult;
use App\Data\CodeExecution\CodeTestCaseResult;
use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\CodeExecutionRun;
use App\Models\Question;
use App\Models\TestAttempt;
use App\Services\CodeExecution\CodeExecutionException;
use App\Services\CodeExecution\CodeExecutionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitMcqAttempt
{
    public function __construct(
        private readonly CodeExecutionService $codeExecutionService,
    ) {}

    /**
     * Submit and score an attempt.
     *
     * @param  array<int|string, int|string>  $answers
     */
    public function handle(TestAttempt $attempt, array $answers): TestAttempt
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test attempt is not in progress.',
            ]);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test has expired and can no longer be submitted.',
            ]);
        }

        try {
            return DB::transaction(function () use ($attempt, $answers): TestAttempt {
                $attempt->load([
                    'test.questions' => fn ($query) => $query
                        ->orderBy('order')
                        ->orderBy('id'),
                    'test.questions.options',
                    'test.questions.testCases' => fn ($query) => $query
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ]);

                [$mcqScore, $mcqMaxScore] = $this->gradeMcqAnswers(
                    attempt: $attempt,
                    questions: $attempt->test->questions
                        ->where('type', QuestionType::Mcq->value)
                        ->values(),
                    answers: $answers,
                );

                [$codingScore, $codingMaxScore] = $this->gradeCodingAnswers(
                    attempt: $attempt,
                    questions: $attempt->test->questions
                        ->where('type', QuestionType::Coding->value)
                        ->values(),
                );

                $score = $mcqScore + $codingScore;
                $maxScore = $mcqMaxScore + $codingMaxScore;
                $percentage = $maxScore > 0
                    ? round(($score / $maxScore) * 100, 2)
                    : 0;

                $attempt->update([
                    'status' => AttemptStatus::Submitted,
                    'submitted_at' => now(),
                    'score' => $score,
                    'max_score' => $maxScore,
                    'total_marks' => $maxScore,
                    'percentage' => $percentage,
                    'passed' => $percentage >= (int) $attempt->test->pass_mark,
                ]);

                return $attempt->refresh();
            });
        } catch (CodeExecutionException) {
            throw ValidationException::withMessages([
                'attempt' => 'Code execution is temporarily unavailable. Please try submitting again.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  array<int|string, int|string>  $answers
     * @return array{0: int, 1: int}
     */
    private function gradeMcqAnswers(TestAttempt $attempt, Collection $questions, array $answers): array
    {
        $score = 0;
        $maxScore = 0;

        $attempt->answers()
            ->whereHas('question', fn ($query) => $query->where('type', QuestionType::Mcq->value))
            ->delete();

        foreach ($questions as $question) {
            $selectedOptionId = (int) ($answers[$question->id] ?? $answers[(string) $question->id]);
            $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
            $isCorrect = (bool) $selectedOption?->is_correct;
            $questionScore = $isCorrect ? (int) $question->marks : 0;

            $score += $questionScore;
            $maxScore += (int) $question->marks;

            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $selectedOptionId,
                'is_correct' => $isCorrect,
                'score' => $questionScore,
            ]);
        }

        return [$score, $maxScore];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{0: int, 1: int}
     */
    private function gradeCodingAnswers(TestAttempt $attempt, Collection $questions): array
    {
        $score = 0;
        $maxScore = 0;

        foreach ($questions as $question) {
            $maxScore += (int) $question->marks;
            $score += $this->gradeCodingQuestion($attempt, $question);
        }

        return [$score, $maxScore];
    }

    private function gradeCodingQuestion(TestAttempt $attempt, Question $question): int
    {
        $answer = $attempt->answers()
            ->where('question_id', $question->id)
            ->first();

        if (! $answer || blank($answer->submitted_code) || blank($answer->language)) {
            $attempt->answers()->updateOrCreate(
                ['question_id' => $question->id],
                [
                    'selected_option_id' => null,
                    'is_correct' => false,
                    'score' => 0,
                    'answered_at' => now(),
                ],
            );

            return 0;
        }

        if (! in_array($answer->language, $question->supported_languages ?? [], true)) {
            throw ValidationException::withMessages([
                'language' => 'Saved coding language is not supported for this question.',
            ]);
        }

        if ($question->testCases->isEmpty()) {
            $answer->update([
                'is_correct' => false,
                'score' => 0,
                'answered_at' => $answer->answered_at ?? now(),
            ]);

            return 0;
        }

        $run = CodeExecutionRun::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'attempt_answer_id' => $answer->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'language' => $answer->language,
            'status' => 'running',
            'run_type' => 'final',
            'source_code' => (string) $answer->submitted_code,
            'started_at' => now(),
        ]);

        try {
            $result = $this->codeExecutionService->runTestCases(
                language: $answer->language,
                sourceCode: (string) $answer->submitted_code,
                testCases: $question->testCases,
                timeLimitMs: $question->time_limit_ms,
                memoryLimitKb: $question->memory_limit_kb,
            );
        } catch (CodeExecutionException $exception) {
            $this->markRunFailed($run, $exception->getMessage(), $question->testCases->count());

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $this->markRunFailed($run, 'Unable to run code right now.', $question->testCases->count());

            throw new CodeExecutionException(
                'Code execution is temporarily unavailable. Please try submitting again.',
                previous: $exception,
            );
        }

        $this->storeTestCaseResults($run, $result);

        [$questionScore, $isCorrect] = $this->scoreCodingQuestion($question, $result->results());
        $summary = $this->summaryWithScore($result, $questionScore, (int) $question->marks);

        $run->update([
            'status' => $summary['status'],
            'result_summary' => $summary,
            'score_awarded' => $questionScore,
            'max_score' => (int) $question->marks,
            'passed' => $isCorrect,
            'finished_at' => now(),
        ]);

        $answer->update([
            'is_correct' => $isCorrect,
            'score' => $questionScore,
            'answered_at' => $answer->answered_at ?? now(),
        ]);

        return $questionScore;
    }

    private function markRunFailed(CodeExecutionRun $run, string $message, int $totalTestCases): void
    {
        $run->update([
            'status' => 'failed',
            'result_summary' => [
                'status' => 'failed',
                'message' => $message,
                'total' => $totalTestCases,
                'passed' => 0,
                'failed' => $totalTestCases,
            ],
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }

    private function storeTestCaseResults(CodeExecutionRun $run, CodeRunResult $result): void
    {
        $result->results()
            ->each(fn (CodeTestCaseResult $testCaseResult): mixed => $run->testCaseResults()->create([
                'question_test_case_id' => $testCaseResult->questionTestCaseId,
                'is_hidden' => $testCaseResult->isHidden,
                'status' => $testCaseResult->status,
                'passed' => $testCaseResult->passed,
                'input' => $testCaseResult->input,
                'expected_output' => $testCaseResult->expectedOutput,
                'actual_output' => $testCaseResult->actualOutput,
                'stdout' => $testCaseResult->stdout,
                'stderr' => $testCaseResult->stderr,
                'compile_output' => $testCaseResult->compileOutput,
                'message' => $testCaseResult->message,
                'time' => $testCaseResult->time,
                'memory' => $testCaseResult->memory,
                'judge0_token' => $testCaseResult->judge0Token,
                'judge0_status_id' => $testCaseResult->judge0StatusId,
                'judge0_status_description' => $testCaseResult->judge0StatusDescription,
            ]));
    }

    /**
     * @param  Collection<int, CodeTestCaseResult>  $results
     * @return array{0: int, 1: bool}
     */
    private function scoreCodingQuestion(Question $question, Collection $results): array
    {
        $resultsByTestCaseId = $results->keyBy('questionTestCaseId');
        $totalWeight = 0;
        $passedWeight = 0;
        $allPassed = $question->testCases->isNotEmpty();

        foreach ($question->testCases as $testCase) {
            $weight = max((int) ($testCase->points ?: 1), 1);
            $totalWeight += $weight;

            $passed = (bool) $resultsByTestCaseId->get($testCase->id)?->passed;

            if ($passed) {
                $passedWeight += $weight;
            } else {
                $allPassed = false;
            }
        }

        if ($totalWeight <= 0) {
            return [0, false];
        }

        $score = (int) round(((int) $question->marks * $passedWeight) / $totalWeight);

        return [$score, $allPassed];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryWithScore(CodeRunResult $result, int $score, int $maxScore): array
    {
        $results = $result->results();
        $visibleResults = $results->filter(fn (CodeTestCaseResult $caseResult): bool => ! $caseResult->isHidden);
        $hiddenResults = $results->filter(fn (CodeTestCaseResult $caseResult): bool => $caseResult->isHidden);

        return [
            ...$result->summary(),
            'score_awarded' => $score,
            'max_score' => $maxScore,
            'visible' => [
                'total' => $visibleResults->count(),
                'passed' => $visibleResults->where('passed', true)->count(),
                'failed' => $visibleResults->where('passed', false)->count(),
            ],
            'hidden' => [
                'total' => $hiddenResults->count(),
                'passed' => $hiddenResults->where('passed', true)->count(),
                'failed' => $hiddenResults->where('passed', false)->count(),
            ],
        ];
    }
}
