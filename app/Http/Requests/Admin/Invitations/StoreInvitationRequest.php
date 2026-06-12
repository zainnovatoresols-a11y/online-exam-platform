<?php

namespace App\Http\Requests\Admin\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvitationRequest extends FormRequest
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
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower((string) $this->input('email')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $test = $this->route('test');

            if (! $test || ! $this->filled('email')) {
                return;
            }

            $duplicatePendingInvite = Invitation::query()
                ->where('test_id', $test->id)
                ->where('email', strtolower((string) $this->input('email')))
                ->where('status', InvitationStatus::Pending->value)
                ->exists();

            if ($duplicatePendingInvite) {
                $validator->errors()->add('email', 'A pending invitation already exists for this candidate.');
            }
        });
    }
}
