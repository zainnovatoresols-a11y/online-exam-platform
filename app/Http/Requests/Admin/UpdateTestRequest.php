<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestRequest extends FormRequest
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
            'title' => trim((string) $this->input('title')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'public_access_enabled' => $this->boolean('public_access_enabled'),
            'candidate_fields' => collect($this->input('candidate_fields', []))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'policy_text' => $this->filled('policy_text') ? trim((string) $this->input('policy_text')) : null,
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
            'title' => ['bail', 'required', 'string', 'min:3', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['bail', 'required', 'integer', 'min:1', 'max:1440'],
            'pass_mark' => ['bail', 'required', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'public_access_enabled' => ['boolean'],
            'candidate_fields' => ['array', 'max:2'],
            'candidate_fields.*' => ['string', 'distinct', 'in:phone,stack_name'],
            'policy_text' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
