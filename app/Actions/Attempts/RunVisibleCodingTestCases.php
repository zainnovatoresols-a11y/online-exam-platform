<?php

namespace App\Actions\Attempts;

use App\Data\CodeExecution\CodeTestCaseResult;
use App\Enums\QuestionType;
use App\Models\CodeExecutionRun;
use App\Models\Question;
use App\Models\TestAttempt;
use App\Services\CodeExecution\CodeExecutionException;
use App\Services\CodeExecution\CodeExecutionService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RunVisibleCodingTestCases
{
    public function __construct(
        private readonly CodeExecutionService $codeExecutionService,
        private readonly SaveCodingAnswer $saveCodingAnswer,
    ) {}

    /**
     * Run a coding answer against visible test cases only.
     *
     * @return array<string, mixed>
     */
    public function handle(TestAttempt $attempt, Question $question, string $language, string $submittedCode): array
    {
        $this->validateRun($attempt, $question, $language);

        $visibleTestCases = $question->testCases()
            ->where('is_hidden', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($visibleTestCases->isEmpty()) {
            $this->fail('run', 'No visible test cases are available for this question.');
        }

        try {
            $this->saveCodingAnswer->handle($attempt, $question, $language, $submittedCode);
        } catch (ValidationException $exception) {
            $this->failWithValidationException($exception);
        }

        $attemptAnswer = $attempt->answers()
            ->where('question_id', $question->id)
            ->first();

        $run = CodeExecutionRun::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'attempt_answer_id' => $attemptAnswer?->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'language' => $language,
            'status' => 'running',
            'source_code' => $submittedCode,
            'started_at' => now(),
        ]);

        try {
            $result = $this->codeExecutionService->runVisibleTestCases(
                language: $language,
                sourceCode: $submittedCode,
                testCases: $visibleTestCases,
                timeLimitMs: $question->time_limit_ms,
                memoryLimitKb: $question->memory_limit_kb,
            );
        } catch (CodeExecutionException $exception) {
            $run->update([
                'status' => 'failed',
                'result_summary' => [
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                    'total' => $visibleTestCases->count(),
                    'passed' => 0,
                    'failed' => $visibleTestCases->count(),
                ],
                'finished_at' => now(),
            ]);

            $this->fail('run', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            $run->update([
                'status' => 'failed',
                'result_summary' => [
                    'status' => 'failed',
                    'message' => 'Unable to run code right now.',
                    'total' => $visibleTestCases->count(),
                    'passed' => 0,
                    'failed' => $visibleTestCases->count(),
                ],
                'finished_at' => now(),
            ]);

            $this->fail('run', 'Unable to run code right now.');
        }

        return DB::transaction(function () use ($run, $result): array {
            $summary = $result->summary();

            $run->update([
                'status' => $summary['status'],
                'result_summary' => $summary,
                'finished_at' => now(),
            ]);

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

            return [
                'id' => $run->id,
                'status' => $run->status,
                'summary' => $summary,
                'results' => $result->results()
                    ->map(fn (CodeTestCaseResult $testCaseResult): array => $testCaseResult->toPayload())
                    ->values(),
            ];
        });
    }

    private function validateRun(TestAttempt $attempt, Question $question, string $language): void
    {
        if (! $attempt->isInProgress()) {
            $this->fail('attempt', 'This test attempt is not in progress.');
        }

        if ($attempt->isExpired()) {
            $this->fail('attempt', 'This test has expired and code can no longer be run.');
        }

        if ((int) $question->test_id !== (int) $attempt->test_id) {
            $this->fail('question_id', 'Question is invalid for this test.');
        }

        if ($question->type !== QuestionType::Coding->value) {
            $this->fail('question_id', 'Question must be a coding question.');
        }

        if (! in_array($language, $question->supported_languages ?? [], true)) {
            $this->fail('language', 'Selected language is not supported for this question.');
        }
    }

    private function failWithValidationException(ValidationException $exception): void
    {
        $errors = $exception->errors();
        $field = array_key_first($errors) ?? 'run';
        $message = $errors[$field][0] ?? 'Unable to run code right now.';

        $this->fail($field, $message);
    }

    private function fail(string $field, string $message): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => [
                $field => [$message],
            ],
        ], 422));
    }
}
