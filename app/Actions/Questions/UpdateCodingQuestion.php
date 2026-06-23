<?php

namespace App\Actions\Questions;

use App\Models\Question;
use Illuminate\Support\Facades\DB;

class UpdateCodingQuestion
{
    private const DEFAULT_MEMORY_LIMIT_KB = 128000;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Question $question, array $data): Question
    {
        return DB::transaction(function () use ($question, $data): Question {
            $supportedLanguages = $data['supported_languages'];

            $question->update([
                'body' => $data['body'],
                'marks' => $data['marks'],
                'order' => $data['order'] ?? $question->order,
                'difficulty' => $data['difficulty'],
                'time_limit_ms' => $data['time_limit_ms'],
                'memory_limit_kb' => $data['memory_limit_kb']
                    ?? $question->memory_limit_kb
                    ?? self::DEFAULT_MEMORY_LIMIT_KB,
                'supported_languages' => $supportedLanguages,
                'starter_code' => $this->starterCodeForSelectedLanguages(
                    $data['starter_code'] ?? [],
                    $supportedLanguages,
                ),
            ]);

            $question->testCases()->delete();
            $this->syncTestCases($question, $data['test_cases']);

            return $question->refresh();
        });
    }

    /**
     * @param  array<string, string|null>  $starterCode
     * @param  array<int, string>  $supportedLanguages
     * @return array<string, string|null>
     */
    private function starterCodeForSelectedLanguages(array $starterCode, array $supportedLanguages): array
    {
        return collect($starterCode)
            ->only($supportedLanguages)
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $testCases
     */
    private function syncTestCases(Question $question, array $testCases): void
    {
        foreach ($testCases as $index => $testCase) {
            $question->testCases()->create([
                'input' => $testCase['input'] ?? null,
                'expected_output' => $testCase['expected_output'],
                'is_hidden' => (bool) ($testCase['is_hidden'] ?? false),
                'sort_order' => $index + 1,
                'points' => $testCase['points'] ?? null,
            ]);
        }
    }
}
