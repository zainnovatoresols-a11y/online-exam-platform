<?php

namespace App\Actions\Questions;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Support\Facades\DB;

class CreateCodingQuestion
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Test $test, array $data): Question
    {
        return DB::transaction(function () use ($test, $data): Question {
            $supportedLanguages = $data['supported_languages'];

            $question = $test->questions()->create([
                'type' => QuestionType::Coding->value,
                'body' => $data['body'],
                'marks' => $data['marks'],
                'order' => $data['order'] ?? ((int) $test->questions()->max('order')) + 1,
                'difficulty' => $data['difficulty'],
                'time_limit_ms' => $data['time_limit_ms'],
                'memory_limit_kb' => $data['memory_limit_kb'],
                'supported_languages' => $supportedLanguages,
                'starter_code' => $this->starterCodeForSelectedLanguages(
                    $data['starter_code'] ?? [],
                    $supportedLanguages,
                ),
            ]);

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
