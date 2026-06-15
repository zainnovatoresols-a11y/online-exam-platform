<?php

namespace App\Http\Requests\Admin\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\User;
use App\Queries\AdminCandidatePoolQuery;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
                'email' => strtolower(trim((string) $this->input('email'))),
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
            'email' => ['nullable', 'required_without_all:candidate_ids,emails', 'email', 'max:255'],
            'candidate_ids' => ['nullable', 'required_without_all:email,emails', 'array'],
            'candidate_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
            'emails' => ['nullable', 'required_without_all:email,candidate_ids', 'string'],
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
                $emails[] = strtolower(trim((string) $this->input('email')));
            }

            $bulkEmails = $this->bulkEmails(unique: false);

            foreach ($bulkEmails as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('emails', "The email {$email} is invalid.");
                }

                $emails[] = $email;
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
                    $validator->errors()->add(
                        $this->fieldForEmail($email, $bulkEmails),
                        "An active invitation already exists for {$email}.",
                    );
                }
            }

            $providedEmails = collect($emails);
            if ($providedEmails->count() !== $providedEmails->unique()->count()) {
                $validator->errors()->add(
                    $this->filled('emails') ? 'emails' : 'email',
                    'The same email is listed more than once.',
                );
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
     * @return EloquentCollection<int, User>
     */
    private function candidateUsers(): EloquentCollection
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

    /**
     * @param  list<string>  $bulkEmails
     */
    private function fieldForEmail(string $email, array $bulkEmails): string
    {
        if (in_array($email, $bulkEmails, true)) {
            return 'emails';
        }

        if ($this->filled('email') && strtolower(trim((string) $this->input('email'))) === $email) {
            return 'email';
        }

        return 'candidate_ids';
    }

    /**
     * @return list<string>
     */
    public function bulkEmails(bool $unique = true): array
    {
        if (blank($this->input('emails'))) {
            return [];
        }

        $emails = collect(preg_split('/[\s,;]+/', (string) $this->input('emails')) ?: [])
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter(fn (string $email): bool => $email !== '');

        if ($unique) {
            $emails = $emails->unique();
        }

        return $emails
            ->values()
            ->all();
    }
}
