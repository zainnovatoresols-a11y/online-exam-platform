<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestRequest extends FormRequest
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
            'public_access_enabled' => $this->boolean('public_access_enabled'),
            'candidate_fields' => collect($this->input('candidate_fields', []))
                ->filter()
                ->unique()
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'pass_mark' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'public_access_enabled' => ['boolean'],
            'candidate_fields' => ['array'],
            'candidate_fields.*' => ['string', 'in:phone,stack_name'],
            'policy_text' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
