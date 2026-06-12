<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\DB;

class SubmitMcqAttempt
{
    /**
     * Submit and score an MCQ attempt.
     *
     * @param array<int|string, int|string> $answers
     */
    public function handle(TestAttempt $attempt, array $answers): TestAttempt
    {
        return DB::transaction(function () use ($attempt, $answers): TestAttempt {
            $attempt->load('test.questions.options');

            $score = 0;
            $totalMarks = 0;

            $attempt->answers()->delete();

            foreach ($attempt->test->questions as $question) {
                $selectedOptionId = (int) ($answers[$question->id] ?? $answers[(string) $question->id]);
                $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
                $isCorrect = (bool) $selectedOption?->is_correct;
                $questionScore = $isCorrect ? (int) $question->marks : 0;

                $score += $questionScore;
                $totalMarks += (int) $question->marks;

                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'selected_option_id' => $selectedOptionId,
                    'is_correct' => $isCorrect,
                    'score' => $questionScore,
                ]);
            }

            $attempt->update([
                'status' => AttemptStatus::Submitted,
                'submitted_at' => now(),
                'score' => $score,
                'total_marks' => $totalMarks,
            ]);

            return $attempt->refresh();
        });
    }
}
