<?php

namespace Tests\Unit;

use App\Models\QuestionTestCase;
use App\Services\CodeExecution\CodeExecutionException;
use App\Services\CodeExecution\Judge0CodeExecutionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Judge0CodeExecutionServiceTest extends TestCase
{
    public function test_rapidapi_headers_are_sent_to_judge0(): void
    {
        config([
            'judge0.rapidapi_key' => 'test-rapidapi-key',
            'judge0.rapidapi_host' => 'judge0-ce.p.rapidapi.com',
            'judge0.submission_wait' => true,
        ]);

        Http::fake([
            '*' => Http::response([
                'token' => 'judge0-token',
                'stdout' => 'cba',
                'status' => [
                    'id' => 3,
                    'description' => 'Accepted',
                ],
            ]),
        ]);

        $testCase = new QuestionTestCase([
            'input' => 'abc',
            'expected_output' => 'cba',
            'is_hidden' => false,
        ]);
        $testCase->id = 1;

        app(Judge0CodeExecutionService::class)->runVisibleTestCases(
            language: 'javascript',
            sourceCode: 'console.log("cba");',
            testCases: [$testCase],
        );

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-RapidAPI-Key', 'test-rapidapi-key')
            && $request->hasHeader('X-RapidAPI-Host', 'judge0-ce.p.rapidapi.com')
            && str_contains($request->url(), '/submissions'));
    }

    public function test_connection_failures_return_clear_code_runner_message(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Connection refused');
        });

        $testCase = new QuestionTestCase([
            'input' => 'abc',
            'expected_output' => 'cba',
            'is_hidden' => false,
        ]);
        $testCase->id = 1;

        $this->expectException(CodeExecutionException::class);
        $this->expectExceptionMessage('Code runner is not reachable. Please contact the test administrator.');

        app(Judge0CodeExecutionService::class)->runVisibleTestCases(
            language: 'javascript',
            sourceCode: 'console.log("cba");',
            testCases: [$testCase],
        );
    }
}
