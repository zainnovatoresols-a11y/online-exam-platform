<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitMcqAttempt
{
    /**
     * Submit and score an MCQ attempt.
     *
     * @param  array<int|string, int|string>  $answers
     */
    public function handle(TestAttempt $attempt, array $answers): TestAttempt
    {
        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test has expired and can no longer be submitted.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $answers): TestAttempt {
            $attempt->load([
                'test.questions' => fn ($query) => $query->where('type', QuestionType::Mcq->value),
                'test.questions.options',
            ]);

            $score = 0;
            $maxScore = 0;

            $attempt->answers()
                ->whereHas('question', fn ($query) => $query->where('type', QuestionType::Mcq->value))
                ->delete();

            foreach ($attempt->test->questions as $question) {
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
    }
}
