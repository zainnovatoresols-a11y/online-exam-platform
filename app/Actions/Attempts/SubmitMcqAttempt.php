<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Jobs\GradeAttemptCodingAnswers;
use App\Jobs\MergeProctoringRecording;
use App\Models\Question;
use App\Models\TestAttempt;
use App\Services\CodeExecution\CodeExecutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitMcqAttempt
{
    public function __construct(
        private readonly GradeCodingQuestion $gradeCodingQuestion,
    ) {}

    /**
     * Submit and score an attempt.
     *
     * @param  array<int|string, int|string>  $answers
     */
    public function handle(TestAttempt $attempt, array $answers): TestAttempt
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test attempt is not in progress.',
            ]);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'This test has expired and can no longer be submitted.',
            ]);
        }

        $queueFinalGrading = (bool) config('code_execution.queue_final_grading', false);

        try {
            $submittedAttempt = DB::transaction(function () use ($attempt, $answers, $queueFinalGrading): TestAttempt {
                $attempt->load([
                    'test.questions' => fn ($query) => $query
                        ->orderBy('order')
                        ->orderBy('id'),
                    'test.questions.options',
                    'test.questions.testCases' => fn ($query) => $query
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ]);

                [$mcqScore, $mcqMaxScore] = $this->gradeMcqAnswers(
                    attempt: $attempt,
                    questions: $attempt->test->questions
                        ->where('type', QuestionType::Mcq->value)
                        ->values(),
                    answers: $answers,
                );

                $codingQuestions = $attempt->test->questions
                    ->where('type', QuestionType::Coding->value)
                    ->values();

                [$codingScore, $codingMaxScore] = $queueFinalGrading
                    ? $this->queueCodingAnswers($attempt, $codingQuestions)
                    : $this->gradeCodingAnswers($attempt, $codingQuestions);

                $score = $mcqScore + $codingScore;
                $maxScore = $mcqMaxScore + $codingMaxScore;
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
        } catch (CodeExecutionException) {
            throw ValidationException::withMessages([
                'attempt' => 'Code execution is temporarily unavailable. Please try submitting again.',
            ]);
        }

        if ($queueFinalGrading) {
            GradeAttemptCodingAnswers::dispatch($submittedAttempt->id);
        }

        $this->queueProctoringRecordingMerges($submittedAttempt);

        return $submittedAttempt;
    }

    private function queueProctoringRecordingMerges(TestAttempt $attempt): void
    {
        $attempt->proctoringRecordings()
            ->where('chunk_count', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('merged_status')
                    ->orWhereIn('merged_status', ['pending', 'failed']);
            })
            ->get(['id'])
            ->each(fn ($recording): mixed => MergeProctoringRecording::dispatch($recording->id)->afterCommit());
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  array<int|string, int|string>  $answers
     * @return array{0: int, 1: int}
     */
    private function gradeMcqAnswers(TestAttempt $attempt, Collection $questions, array $answers): array
    {
        $score = 0;
        $maxScore = 0;

        $attempt->answers()
            ->whereHas('question', fn ($query) => $query->where('type', QuestionType::Mcq->value))
            ->delete();

        foreach ($questions as $question) {
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

        return [$score, $maxScore];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{0: int, 1: int}
     */
    private function gradeCodingAnswers(TestAttempt $attempt, Collection $questions): array
    {
        $score = 0;
        $maxScore = 0;

        foreach ($questions as $question) {
            $maxScore += (int) $question->marks;
            $score += $this->gradeCodingQuestion->handle($attempt, $question);
        }

        return [$score, $maxScore];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{0: int, 1: int}
     */
    private function queueCodingAnswers(TestAttempt $attempt, Collection $questions): array
    {
        $maxScore = 0;

        foreach ($questions as $question) {
            $maxScore += (int) $question->marks;
            $this->gradeCodingQuestion->prepareQueuedRun($attempt, $question);
        }

        return [0, $maxScore];
    }
}
