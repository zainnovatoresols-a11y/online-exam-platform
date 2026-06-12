<?php

namespace App\Http\Requests\Admin\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\User;
use App\Queries\AdminCandidatePoolQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:candidate_ids', 'email', 'max:255'],
            'candidate_ids' => ['nullable', 'required_without:email', 'array'],
            'candidate_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $test = $this->route('test');

            if (! $test) {
                return;
            }

            $emails = $this->candidateEmails();

            if ($this->filled('email')) {
                $emails[] = strtolower((string) $this->input('email'));
            }

            foreach (array_unique($emails) as $email) {
                $duplicateInvite = Invitation::query()
                    ->where('test_id', $test->id)
                    ->where('email', $email)
                    ->whereIn('status', [
                        InvitationStatus::Pending->value,
                        InvitationStatus::Sent->value,
                        InvitationStatus::Accepted->value,
                    ])
                    ->exists();

                if ($duplicateInvite) {
                    $field = $this->filled('candidate_ids') ? 'candidate_ids' : 'email';
                    $validator->errors()->add($field, "An active invitation already exists for {$email}.");
                }
            }

            foreach ($this->candidateUsers() as $candidate) {
                if (! $this->candidateBelongsToAdminScope($candidate)) {
                    $validator->errors()->add('candidate_ids', 'One or more selected candidates are outside your candidate pool.');
                    return;
                }
            }
        });
    }

    /**
     * @return list<string>
     */
    private function candidateEmails(): array
    {
        return $this->candidateUsers()
            ->pluck('email')
            ->map(fn (string $email): string => strtolower($email))
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function candidateUsers()
    {
        $ids = collect($this->input('candidate_ids', []))
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return User::query()->whereRaw('1 = 0')->get();
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get();
    }

    private function candidateBelongsToAdminScope(User $candidate): bool
    {
        $admin = $this->user();

        if (! $admin) {
            return false;
        }

        return app(AdminCandidatePoolQuery::class)
            ->query($admin)
            ->whereKey($candidate->id)
            ->exists();
    }
}
