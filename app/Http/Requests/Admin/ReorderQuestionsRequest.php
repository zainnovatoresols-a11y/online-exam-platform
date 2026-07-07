<?php

namespace App\Http\Requests\Admin;

use App\Models\Test;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'question_ids' => ['bail', 'required', 'array', 'min:1', 'max:200'],
            'question_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Test|null $test */
            $test = $this->route('test');

            if (! $test instanceof Test) {
                return;
            }

            $submittedIds = collect($this->input('question_ids', []))
                ->map(fn (mixed $value): int => (int) $value)
                ->sort()
                ->values()
                ->all();

            $existingIds = $test->questions()
                ->pluck('id')
                ->map(fn (mixed $value): int => (int) $value)
                ->sort()
                ->values()
                ->all();

            if ($submittedIds !== $existingIds) {
                $validator->errors()->add(
                    'question_ids',
                    'The submitted question order is invalid.',
                );
            }
        });
    }
}
