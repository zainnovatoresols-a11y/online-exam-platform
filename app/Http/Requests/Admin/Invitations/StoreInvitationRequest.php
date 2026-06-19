<?php

namespace App\Http\Requests\Admin\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvitationRequest extends FormRequest
{
    /**
     * @var array{valid: list<string>, valid_all: list<string>, invalid: list<string>, duplicates: list<string>}|null
     */
    private ?array $bulkEmailAnalysis = null;

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
            'email' => ['nullable', 'required_without_all:emails,email_csv', 'email', 'max:255'],
            'emails' => ['nullable', 'required_without_all:email,email_csv', 'string'],
            'email_csv' => ['nullable', 'required_without_all:email,emails', 'file', 'mimes:csv,txt', 'max:2048'],
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

            if ($this->filled('email')) {
                $email = strtolower(trim((string) $this->input('email')));
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
                        'email',
                        "An active invitation already exists for {$email}.",
                    );
                }
            }

            if (! $this->filled('email') && $this->hasBulkEmailInput() && $this->bulkEmails() === []) {
                $validator->errors()->add('emails', 'No valid email addresses were found. Fix the invalid rows or upload a CSV with an email column.');
            }
        });
    }

    /**
     * @return list<string>
     */
    public function bulkEmails(bool $unique = true): array
    {
        $analysis = $this->analyzeBulkEmails();

        return $unique
            ? $analysis['valid']
            : $analysis['valid_all'];
    }

    /**
     * @return list<string>
     */
    public function invalidBulkEmails(): array
    {
        return $this->analyzeBulkEmails()['invalid'];
    }

    /**
     * @return list<string>
     */
    public function duplicateBulkEmails(): array
    {
        return $this->analyzeBulkEmails()['duplicates'];
    }

    private function hasBulkEmailInput(): bool
    {
        return filled($this->input('emails')) || $this->hasFile('email_csv');
    }

    /**
     * @return array{valid: list<string>, valid_all: list<string>, invalid: list<string>, duplicates: list<string>}
     */
    private function analyzeBulkEmails(): array
    {
        if ($this->bulkEmailAnalysis !== null) {
            return $this->bulkEmailAnalysis;
        }

        $valid = [];
        $validAll = [];
        $invalid = [];
        $duplicates = [];
        $seen = [];

        foreach ($this->bulkEmailTokens() as $token) {
            $email = strtolower(trim($token));

            if ($email === '') {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid[] = $email;

                continue;
            }

            $validAll[] = $email;

            if (isset($seen[$email])) {
                $duplicates[] = $email;

                continue;
            }

            $seen[$email] = true;
            $valid[] = $email;
        }

        return $this->bulkEmailAnalysis = [
            'valid' => array_values($valid),
            'valid_all' => array_values($validAll),
            'invalid' => array_values(array_unique($invalid)),
            'duplicates' => array_values(array_unique($duplicates)),
        ];
    }

    /**
     * @return list<string>
     */
    private function bulkEmailTokens(): array
    {
        return [
            ...$this->textEmailTokens(),
            ...$this->csvEmailTokens(),
        ];
    }

    /**
     * @return list<string>
     */
    private function textEmailTokens(): array
    {
        if (blank($this->input('emails'))) {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', (string) $this->input('emails')) ?: [])
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => $email !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function csvEmailTokens(): array
    {
        $file = $this->file('email_csv');

        if (! $file || ! $file->isValid() || ! $file->getRealPath()) {
            return [];
        }

        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $cells = array_map(
                fn (mixed $cell): string => $this->cleanCsvCell((string) $cell),
                $row,
            );

            if (array_filter($cells, fn (string $cell): bool => $cell !== '') !== []) {
                $rows[] = $cells;
            }
        }

        fclose($handle);

        if ($rows === []) {
            return [];
        }

        $emailColumn = $this->emailColumnIndex($rows[0]);

        if ($emailColumn !== null) {
            return collect(array_slice($rows, 1))
                ->map(fn (array $row): string => $row[$emailColumn] ?? '')
                ->filter(fn (string $email): bool => $email !== '')
                ->values()
                ->all();
        }

        $tokens = [];

        foreach ($rows as $row) {
            $cells = array_values(array_filter($row, fn (string $cell): bool => $cell !== ''));

            if (count($cells) === 1) {
                $tokens[] = $cells[0];

                continue;
            }

            foreach ($cells as $cell) {
                if (str_contains($cell, '@')) {
                    $tokens[] = $cell;
                }
            }
        }

        return $tokens;
    }

    /**
     * @param  list<string>  $row
     */
    private function emailColumnIndex(array $row): ?int
    {
        foreach ($row as $index => $cell) {
            $header = strtolower(str_replace(['-', '_'], ' ', $cell));
            $header = preg_replace('/\s+/', ' ', $header) ?: $header;

            if (in_array(trim($header), ['email', 'e mail', 'email address', 'candidate email'], true)) {
                return $index;
            }
        }

        return null;
    }

    private function cleanCsvCell(string $cell): string
    {
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $cell) ?? $cell);
    }
}
