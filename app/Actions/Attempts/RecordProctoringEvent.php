<?php

namespace App\Actions\Attempts;

use App\Models\ProctoringEvent;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RecordProctoringEvent
{
    private const DUPLICATE_WINDOW_SECONDS = 3;

    private const SEVERITY_BY_EVENT = [
        'tab_hidden' => 'medium',
        'tab_visible' => 'low',
        'window_blur' => 'medium',
        'window_focus' => 'low',
        'fullscreen_entered' => 'low',
        'fullscreen_exited' => 'high',
        'copy_attempt' => 'high',
        'paste_attempt' => 'high',
        'cut_attempt' => 'high',
        'right_click_attempt' => 'medium',
        'shortcut_attempt' => 'medium',
        'drag_attempt' => 'medium',
        'drop_attempt' => 'medium',
        'proctoring_violation_acknowledged' => 'low',
    ];

    /**
     * @return array<int, string>
     */
    public static function eventTypes(): array
    {
        return array_keys(self::SEVERITY_BY_EVENT);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{recorded: bool, duplicate: bool, event: ProctoringEvent}
     */
    public function handle(
        TestAttempt $attempt,
        string $eventType,
        ?string $occurredAt,
        array $metadata,
        Request $request,
    ): array {
        if (! array_key_exists($eventType, self::SEVERITY_BY_EVENT)) {
            throw ValidationException::withMessages([
                'event_type' => 'The selected proctoring event type is invalid.',
            ]);
        }

        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'Proctoring events can only be recorded for an in-progress attempt.',
            ]);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'Proctoring events can no longer be recorded after the attempt has expired.',
            ]);
        }

        $metadata = $this->sanitizeMetadata($metadata);
        $signature = $this->metadataSignature($metadata);

        $duplicate = $attempt->proctoringEvents()
            ->where('event_type', $eventType)
            ->where('created_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
            ->latest('id')
            ->get()
            ->first(fn (ProctoringEvent $event): bool => $this->metadataSignature($event->metadata ?? []) === $signature);

        if ($duplicate) {
            return [
                'recorded' => false,
                'duplicate' => true,
                'event' => $duplicate,
            ];
        }

        $event = $attempt->proctoringEvents()->create([
            'candidate_user_id' => $attempt->candidate_user_id,
            'event_type' => $eventType,
            'severity' => self::SEVERITY_BY_EVENT[$eventType],
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);

        return [
            'recorded' => true,
            'duplicate' => false,
            'event' => $event,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, string|int|float|bool|null>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $safe = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || strlen($key) > 80) {
                continue;
            }

            if (is_string($value)) {
                $safe[$key] = substr($value, 0, 500);

                continue;
            }

            if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function metadataSignature(array $metadata): string
    {
        ksort($metadata);

        return hash('sha256', (string) json_encode($metadata));
    }
}
