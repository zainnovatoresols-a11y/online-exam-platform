<?php

namespace App\Http\Requests\Candidate\PublicTests;

use App\Models\Test;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicTestRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }

        if ($this->has('invitation_token')) {
            $this->merge([
                'invitation_token' => trim((string) $this->input('invitation_token')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiredFields = $this->test()?->candidateRegistrationFields() ?? [];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'invitation_token' => ['nullable', 'string', 'max:255'],
            'phone' => [
                Rule::requiredIf(in_array('phone', $requiredFields, true)),
                'nullable',
                'string',
                'max:50',
            ],
            'stack_name' => [
                Rule::requiredIf(in_array('stack_name', $requiredFields, true)),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    private function test(): ?Test
    {
        $token = $this->route('publicToken');

        if (! is_string($token)) {
            return null;
        }

        return Test::query()
            ->where('public_token', $token)
            ->first();
    }
}
