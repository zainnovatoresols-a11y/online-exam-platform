<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['bail', 'required', 'string', 'current_password', 'max:255'],
            'password' => ['bail', 'required', 'confirmed', Password::defaults(), 'max:255'],
        ];
    }
}
