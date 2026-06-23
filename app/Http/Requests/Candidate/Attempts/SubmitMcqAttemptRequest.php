<?php

namespace App\Http\Requests\Candidate\Attempts;

use App\Enums\QuestionType;
use App\Models\Invitation;
use App\Models\TestAttempt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitMcqAttemptRequest extends FormRequest
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
            'answers.*' => ['required', 'integer', 'exists:question_options,id'],
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
                'test.questions' => fn ($query) => $query
                    ->orderBy('order')
                    ->orderBy('id'),
                'test.questions.options',
                'answers:id,test_attempt_id,question_id,language,submitted_code',
            ]);
            $answers = collect($this->input('answers', []));

            foreach ($attempt->test->questions as $question) {
                if ($question->type === QuestionType::Coding->value) {
                    $savedAnswer = $attempt->answers->firstWhere('question_id', $question->id);

                    if (! $savedAnswer || blank($savedAnswer->language) || blank($savedAnswer->submitted_code)) {
                        $validator->errors()->add(
                            "coding_answers.{$question->id}",
                            'Please write and save code before submitting this question.',
                        );
                    }

                    continue;
                }

                $selectedOptionId = $answers->get((string) $question->id)
                    ?? $answers->get($question->id);

                if (! $selectedOptionId) {
                    $validator->errors()->add("answers.{$question->id}", 'Please select an answer.');

                    continue;
                }

                $belongsToQuestion = $question->options
                    ->contains('id', (int) $selectedOptionId);

                if (! $belongsToQuestion) {
                    $validator->errors()->add("answers.{$question->id}", 'Selected option is invalid for this question.');
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
