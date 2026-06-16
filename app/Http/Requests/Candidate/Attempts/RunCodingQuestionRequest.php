<?php

namespace App\Http\Requests\Candidate\Attempts;

use Illuminate\Foundation\Http\FormRequest;

class RunCodingQuestionRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'language' => ['required', 'string'],
            'submitted_code' => ['required', 'string', 'max:50000'],
        ];
    }
}
