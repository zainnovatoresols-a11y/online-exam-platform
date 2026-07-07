<?php

namespace App\Http\Requests\Onboarding;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class StoreOrganizationOwnerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization_name' => trim((string) $this->input('organization_name')),
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'organization_name' => ['bail', 'required', 'string', 'min:2', 'max:160', 'regex:/\A[\pL\pM\pN .&\'(),\-\/]+\z/u', 'unique:organizations,name'],
            'name' => ['bail', 'required', 'string', 'min:2', 'max:120', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'email' => ['bail', 'required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:'.User::class],
            'password' => ['bail', 'required', 'confirmed', Rules\Password::defaults(), 'max:255'],
        ];
    }
}
