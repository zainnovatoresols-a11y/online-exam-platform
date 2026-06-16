<?php

namespace App\Services\CodeExecution;

use App\Data\CodeExecution\CodeRunResult;
use App\Data\CodeExecution\CodeTestCaseResult;
use App\Models\QuestionTestCase;

class FakeCodeExecutionService implements CodeExecutionService
{
    public function runVisibleTestCases(
        string $language,
        string $sourceCode,
        iterable $testCases,
        ?int $timeLimitMs = null,
        ?int $memoryLimitKb = null,
    ): CodeRunResult {
        $results = collect($testCases)
            ->map(function (QuestionTestCase $testCase) use ($sourceCode): CodeTestCaseResult {
                $actualOutput = str_contains($sourceCode, '__FAIL__')
                    ? 'wrong output'
                    : $testCase->expected_output;
                $passed = rtrim((string) $actualOutput) === rtrim((string) $testCase->expected_output);

                return new CodeTestCaseResult(
                    questionTestCaseId: $testCase->id,
                    isHidden: (bool) $testCase->is_hidden,
                    status: $passed ? 'passed' : 'failed',
                    passed: $passed,
                    input: $testCase->input,
                    expectedOutput: $testCase->expected_output,
                    actualOutput: $actualOutput,
                    stdout: $actualOutput,
                    judge0Token: 'fake-token-'.$testCase->id,
                    judge0StatusId: 3,
                    judge0StatusDescription: 'Accepted',
                );
            })
            ->values();

        return new CodeRunResult(
            status: $results->every(fn (CodeTestCaseResult $result): bool => $result->passed)
                ? 'completed'
                : 'completed_with_failures',
            testCaseResults: $results,
        );
    }
}
