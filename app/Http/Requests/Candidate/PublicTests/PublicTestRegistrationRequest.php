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

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'stack_name' => $this->filled('stack_name') ? trim((string) $this->input('stack_name')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiredFields = $this->test()?->candidateRegistrationFields() ?? [];

        return [
            'name' => ['bail', 'required', 'string', 'min:2', 'max:120', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'email' => ['bail', 'required', 'string', 'lowercase', 'email:rfc', 'max:255'],
            'invitation_token' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9]+\z/'],
            'phone' => [
                Rule::requiredIf(in_array('phone', $requiredFields, true)),
                'nullable',
                'string',
                'min:7',
                'max:30',
                'regex:/\A[+0-9().\-\s]+\z/',
            ],
            'stack_name' => [
                Rule::requiredIf(in_array('stack_name', $requiredFields, true)),
                'nullable',
                'string',
                'min:2',
                'max:120',
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
