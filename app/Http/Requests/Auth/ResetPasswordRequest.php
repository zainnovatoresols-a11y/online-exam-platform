<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'token' => trim((string) $this->input('token')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['bail', 'required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'string', 'lowercase', 'email:rfc', 'max:255'],
            'password' => ['bail', 'required', 'confirmed', Rules\Password::defaults(), 'max:255'],
        ];
    }
}
