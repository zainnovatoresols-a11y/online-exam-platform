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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'marks' => ['required', 'integer', 'min:1'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.body' => ['required', 'string'],
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
