<?php

namespace App\Actions\Attempts;

use App\Actions\Attempts\Concerns\SanitizesProctoringMetadata;
use App\Models\ProctoringRecording;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StartProctoringRecording
{
    use SanitizesProctoringMetadata;

    public function __construct(private readonly RecordProctoringEvent $recordProctoringEvent) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        TestAttempt $attempt,
        string $recordingType,
        ?string $mimeType,
        array $metadata,
        Request $request,
    ): ProctoringRecording {
        $this->ensureRecordable($attempt);

        $metadata = $this->sanitizeMetadata($metadata);

        $recording = ProctoringRecording::query()->firstOrNew([
            'test_attempt_id' => $attempt->id,
            'recording_type' => $recordingType,
        ]);

        $recording->fill([
            'candidate_user_id' => $attempt->candidate_user_id,
            'status' => 'recording',
            'started_at' => $recording->started_at ?? now(),
            'stopped_at' => null,
            'denied_at' => null,
            'mime_type' => $mimeType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
        $recording->save();

        $this->recordProctoringEvent->handle(
            $attempt,
            "{$recordingType}_recording_started",
            null,
            array_merge([
                'recording_type' => $recordingType,
                'mime_type' => $mimeType,
            ], $metadata),
            $request,
        );

        return $recording->refresh();
    }

    private function ensureRecordable(TestAttempt $attempt): void
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'Recording can only be started for an in-progress attempt.',
            ]);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'Recording can no longer be started after the attempt has expired.',
            ]);
        }
    }
}
