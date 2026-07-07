<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class McqQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => trim((string) $this->input('body')),
            'options' => collect($this->input('options', []))
                ->map(fn (mixed $option): mixed => is_array($option)
                    ? [
                        ...$option,
                        'body' => trim((string) ($option['body'] ?? '')),
                        'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]
                    : $option)
                ->values()
                ->all(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['bail', 'required', 'string', 'min:5', 'max:20000'],
            'marks' => ['bail', 'required', 'integer', 'min:1', 'max:100000'],
            'options' => ['bail', 'required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'array:body,is_correct'],
            'options.*.body' => ['bail', 'required', 'string', 'min:1', 'max:5000', 'distinct:ignore_case'],
            'options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $correctCount = collect($this->input('options', []))
                ->filter(fn (mixed $option): bool => filter_var(
                    data_get($option, 'is_correct'),
                    FILTER_VALIDATE_BOOLEAN,
                ))
                ->count();

            if ($correctCount !== 1) {
                $validator->errors()->add('options', 'Exactly one option must be marked correct.');
            }
        });
    }
}
