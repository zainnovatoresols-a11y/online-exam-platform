<?php

namespace App\Actions\Attempts;

use App\Data\CodeExecution\CodeRunResult;
use App\Data\CodeExecution\CodeTestCaseResult;
use App\Models\CodeExecutionRun;
use App\Models\Question;
use App\Models\TestAttempt;
use App\Services\CodeExecution\CodeExecutionException;
use App\Services\CodeExecution\CodeExecutionService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class GradeCodingQuestion
{
    public function __construct(
        private readonly CodeExecutionService $codeExecutionService,
    ) {}

    public function prepareQueuedRun(TestAttempt $attempt, Question $question): ?CodeExecutionRun
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

            return null;
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

            return null;
        }

        return CodeExecutionRun::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'attempt_answer_id' => $answer->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'language' => $answer->language,
            'status' => 'queued',
            'run_type' => 'final',
            'source_code' => (string) $answer->submitted_code,
            'result_summary' => [
                'status' => 'queued',
                'message' => 'Final coding grading is queued.',
                'total' => $question->testCases->count(),
                'passed' => 0,
                'failed' => 0,
            ],
            'max_score' => (int) $question->marks,
        ]);
    }

    public function handle(TestAttempt $attempt, Question $question, ?CodeExecutionRun $run = null): int
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

        $run ??= CodeExecutionRun::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'attempt_answer_id' => $answer->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'language' => $answer->language,
            'run_type' => 'final',
            'source_code' => (string) $answer->submitted_code,
        ]);

        $run->testCaseResults()->delete();
        $run->update([
            'attempt_answer_id' => $answer->id,
            'language' => $answer->language,
            'status' => 'running',
            'source_code' => (string) $answer->submitted_code,
            'error_message' => null,
            'started_at' => now(),
            'finished_at' => null,
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

    public function markRunFailed(CodeExecutionRun $run, string $message, int $totalTestCases): void
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
            'score_awarded' => 0,
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
