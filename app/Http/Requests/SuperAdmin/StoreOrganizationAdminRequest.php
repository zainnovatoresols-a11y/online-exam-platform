<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class StoreOrganizationAdminRequest extends FormRequest
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
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'min:2', 'max:120', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'email' => ['bail', 'required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:'.User::class],
            'password' => ['bail', 'required', 'confirmed', Rules\Password::defaults(), 'max:255'],
        ];
    }
}
