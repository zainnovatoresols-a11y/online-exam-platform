<?php

namespace App\Http\Requests\Admin\Results;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestAnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->normalizeNullableString('from'),
            'to' => $this->normalizeNullableString('to'),
            'status' => $this->normalizeNullableString('status'),
            'review_status' => $this->normalizeNullableString('review_status'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'string', Rule::in(['in_progress', 'submitted', 'expired'])],
            'review_status' => ['nullable', 'string', Rule::in(['needs_review', 'approved', 'flagged', 'rejected'])],
        ];
    }

    private function normalizeNullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
