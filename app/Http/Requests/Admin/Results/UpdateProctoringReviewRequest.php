<?php

namespace App\Http\Requests\Admin\Results;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProctoringReviewRequest extends FormRequest
{
    public const STATUSES = [
        'approved',
        'needs_review',
        'flagged',
        'rejected',
    ];

    public const RISK_LEVELS = [
        'low',
        'medium',
        'high',
        'critical',
    ];

    public const REASON_CODES = [
        'tab_switching',
        'fullscreen_exit',
        'clipboard_attempt',
        'right_click_attempt',
        'shortcut_attempt',
        'camera_stopped',
        'screen_share_stopped',
        'recording_missing',
        'multiple_people',
        'identity_mismatch',
        'suspicious_audio_visual',
        'other',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => trim((string) $this->input('status')),
            'risk_level' => $this->filled('risk_level') ? trim((string) $this->input('risk_level')) : null,
            'reason_codes' => collect($this->input('reason_codes', []))
                ->filter(fn (mixed $code): bool => is_string($code) && trim($code) !== '')
                ->map(fn (string $code): string => trim($code))
                ->unique()
                ->values()
                ->all(),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['bail', 'required', 'string', Rule::in(self::STATUSES)],
            'risk_level' => ['nullable', 'string', Rule::in(self::RISK_LEVELS)],
            'reason_codes' => ['nullable', 'array', 'max:'.count(self::REASON_CODES)],
            'reason_codes.*' => ['string', 'distinct', Rule::in(self::REASON_CODES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            if (! in_array($status, ['flagged', 'rejected'], true)) {
                return;
            }

            $reasonCodes = array_filter((array) $this->input('reason_codes', []));
            $notes = trim((string) $this->input('notes', ''));

            if ($reasonCodes === [] && $notes === '') {
                $validator->errors()->add(
                    'reason_codes',
                    'Please select a reason code or add notes for flagged/rejected reviews.',
                );
            }
        });
    }
}
