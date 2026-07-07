<?php

namespace App\Http\Requests\Candidate\Attempts;

use App\Enums\ProgrammingLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'question_id' => ['bail', 'required', 'integer', 'min:1', 'exists:questions,id'],
            'language' => ['bail', 'required', 'string', Rule::enum(ProgrammingLanguage::class)],
            'submitted_code' => ['bail', 'required', 'string', 'min:1', 'max:50000'],
        ];
    }
}
