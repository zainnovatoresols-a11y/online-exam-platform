<?php

namespace App\Http\Requests\Candidate\Attempts;

use App\Enums\QuestionType;
use App\Models\Invitation;
use App\Models\TestAttempt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveMcqAnswersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['present', 'array'],
            'answers.*' => ['nullable', 'integer', 'exists:question_options,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $attempt = $this->attempt();

            if (! $attempt) {
                return;
            }

            $attempt->load([
                'test.questions' => fn ($query) => $query->where('type', QuestionType::Mcq->value),
                'test.questions.options',
            ]);
            $answers = collect($this->input('answers', []));

            foreach ($answers as $questionId => $selectedOptionId) {
                if ($selectedOptionId === null || $selectedOptionId === '') {
                    continue;
                }

                $question = $attempt->test->questions
                    ->firstWhere('id', (int) $questionId);

                if (! $question) {
                    $validator->errors()->add("answers.{$questionId}", 'Question is invalid for this test.');

                    continue;
                }

                $belongsToQuestion = $question->options
                    ->contains('id', (int) $selectedOptionId);

                if (! $belongsToQuestion) {
                    $validator->errors()->add("answers.{$questionId}", 'Selected option is invalid for this question.');
                }
            }
        });
    }

    private function attempt(): ?TestAttempt
    {
        $attempt = $this->route('attempt');

        if ($attempt instanceof TestAttempt) {
            return $attempt;
        }

        $attemptToken = $this->route('attemptToken');

        if (! is_string($attemptToken)) {
            return null;
        }

        return Invitation::query()
            ->with('attempt')
            ->where('token', $attemptToken)
            ->first()
            ?->attempt;
    }
}
