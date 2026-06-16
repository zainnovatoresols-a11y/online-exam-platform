<?php

namespace App\Actions\Attempts;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\TestAttempt;
use Illuminate\Validation\ValidationException;

class SaveCodingAnswer
{
    /**
     * Save a candidate's coding answer without executing or grading it.
     *
     * Coding marks are stored but not graded until Judge0 integration.
     */
    public function handle(TestAttempt $attempt, Question $question, string $language, ?string $submittedCode): TestAttempt
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test attempt is not in progress.',
            ]);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test has expired and coding answers can no longer be saved.',
            ]);
        }

        if ((int) $question->test_id !== (int) $attempt->test_id) {
            throw ValidationException::withMessages([
                'question_id' => 'Question is invalid for this test.',
            ]);
        }

        if ($question->type !== QuestionType::Coding->value) {
            throw ValidationException::withMessages([
                'question_id' => 'Question must be a coding question.',
            ]);
        }

        if (! in_array($language, $question->supported_languages ?? [], true)) {
            throw ValidationException::withMessages([
                'language' => 'Selected language is not supported for this question.',
            ]);
        }

        $attempt->answers()->updateOrCreate(
            ['question_id' => $question->id],
            [
                'language' => $language,
                'submitted_code' => $submittedCode,
                'selected_option_id' => null,
                'is_correct' => false,
                'score' => 0,
                'answered_at' => now(),
            ],
        );

        return $attempt->refresh();
    }
}
