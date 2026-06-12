<?php

namespace App\Actions\Attempts;

use App\Models\TestAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveMcqAnswers
{
    /**
     * Save partial or complete MCQ answers without scoring the attempt.
     *
     * @param array<int|string, int|string|null> $answers
     *
     * @throws ValidationException
     */
    public function handle(TestAttempt $attempt, array $answers): TestAttempt
    {
        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test has expired and answers can no longer be saved.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $answers): TestAttempt {
            foreach ($answers as $questionId => $selectedOptionId) {
                if ($selectedOptionId === null || $selectedOptionId === '') {
                    continue;
                }

                $attempt->answers()->updateOrCreate(
                    ['question_id' => (int) $questionId],
                    [
                        'selected_option_id' => (int) $selectedOptionId,
                        'is_correct' => false,
                        'score' => 0,
                    ],
                );
            }

            return $attempt->refresh();
        });
    }
}
