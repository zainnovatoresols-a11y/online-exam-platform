<?php

namespace App\Services\CodeExecution;

use App\Data\CodeExecution\CodeRunResult;
use App\Data\CodeExecution\CodeTestCaseResult;
use App\Models\QuestionTestCase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Judge0CodeExecutionService implements CodeExecutionService
{
    public function runVisibleTestCases(
        string $language,
        string $sourceCode,
        iterable $testCases,
        ?int $timeLimitMs = null,
        ?int $memoryLimitKb = null,
    ): CodeRunResult {
        $languageId = config("judge0.language_ids.{$language}");

        if (! $languageId) {
            throw new CodeExecutionException('Judge0 language is not configured.');
        }

        $results = collect($testCases)
            ->map(fn (QuestionTestCase $testCase): CodeTestCaseResult => $this->runTestCase(
                (int) $languageId,
                $sourceCode,
                $testCase,
                $timeLimitMs,
                $memoryLimitKb,
            ))
            ->values();

        return new CodeRunResult(
            status: $results->every(fn (CodeTestCaseResult $result): bool => $result->passed)
                ? 'completed'
                : 'completed_with_failures',
            testCaseResults: $results,
        );
    }

    private function runTestCase(
        int $languageId,
        string $sourceCode,
        QuestionTestCase $testCase,
        ?int $timeLimitMs,
        ?int $memoryLimitKb,
    ): CodeTestCaseResult {
        try {
            $response = $this->client()
                ->post($this->url('/submissions'), [
                    'source_code' => $sourceCode,
                    'language_id' => $languageId,
                    'stdin' => $testCase->input,
                    'cpu_time_limit' => $timeLimitMs ? $timeLimitMs / 1000 : null,
                    'memory_limit' => $memoryLimitKb,
                ]);
        } catch (ConnectionException $exception) {
            throw new CodeExecutionException(
                'Code runner is not reachable. Please contact the test administrator.',
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw new CodeExecutionException('Judge0 is unavailable.');
        }

        $submission = $response->json();
        $token = $submission['token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new CodeExecutionException('Judge0 did not return a submission token.');
        }

        $result = config('judge0.submission_wait')
            ? $submission
            : $this->pollSubmission($token);

        return $this->resultFromJudge0($testCase, $token, $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function pollSubmission(string $token): array
    {
        $attempts = max((int) config('judge0.poll_attempts'), 1);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->client()->get($this->url("/submissions/{$token}"));
            } catch (ConnectionException $exception) {
                throw new CodeExecutionException(
                    'Code runner is not reachable. Please contact the test administrator.',
                    previous: $exception,
                );
            }

            if ($response->failed()) {
                throw new CodeExecutionException('Judge0 is unavailable.');
            }

            $result = $response->json();
            $statusId = (int) data_get($result, 'status.id', 0);

            if ($statusId >= 3) {
                return $result;
            }

            usleep(max((int) config('judge0.poll_sleep_ms'), 0) * 1000);
        }

        throw new CodeExecutionException('Judge0 timed out while running the code.');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function resultFromJudge0(QuestionTestCase $testCase, string $token, array $result): CodeTestCaseResult
    {
        $stdout = $result['stdout'] ?? null;
        $actualOutput = $stdout;
        $expectedOutput = $testCase->expected_output;
        $statusId = data_get($result, 'status.id');
        $statusDescription = data_get($result, 'status.description');
        $passed = (int) $statusId === 3
            && rtrim((string) $actualOutput) === rtrim((string) $expectedOutput);

        return new CodeTestCaseResult(
            questionTestCaseId: $testCase->id,
            isHidden: (bool) $testCase->is_hidden,
            status: $passed ? 'passed' : 'failed',
            passed: $passed,
            input: $testCase->input,
            expectedOutput: $expectedOutput,
            actualOutput: $actualOutput,
            stdout: $stdout,
            stderr: $result['stderr'] ?? null,
            compileOutput: $result['compile_output'] ?? null,
            message: $result['message'] ?? null,
            time: isset($result['time']) ? (string) $result['time'] : null,
            memory: isset($result['memory']) ? (int) $result['memory'] : null,
            judge0Token: $token,
            judge0StatusId: $statusId !== null ? (int) $statusId : null,
            judge0StatusDescription: is_string($statusDescription) ? $statusDescription : null,
        );
    }

    private function client(): PendingRequest
    {
        $client = Http::timeout((int) config('judge0.request_timeout'))
            ->acceptJson()
            ->asJson()
            ->withQueryParameters([
                'base64_encoded' => 'false',
                'wait' => config('judge0.submission_wait') ? 'true' : 'false',
            ]);

        if (filled(config('judge0.rapidapi_key'))) {
            $client = $client->withHeaders([
                'X-RapidAPI-Key' => (string) config('judge0.rapidapi_key'),
                'X-RapidAPI-Host' => (string) config('judge0.rapidapi_host'),
            ]);
        } elseif (filled(config('judge0.api_key'))) {
            $client = $client->withHeaders([
                'X-Auth-Token' => (string) config('judge0.api_key'),
            ]);
        }

        return $client;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('judge0.base_url'), '/').$path;
    }
}
