<?php

namespace App\Actions\Attempts;

use App\Actions\Attempts\Concerns\SanitizesProctoringMetadata;
use App\Models\ProctoringRecording;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StopProctoringRecording
{
    use SanitizesProctoringMetadata;

    public function __construct(private readonly RecordProctoringEvent $recordProctoringEvent) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        TestAttempt $attempt,
        string $recordingType,
        ?string $reason,
        array $metadata,
        Request $request,
    ): ProctoringRecording {
        if (! $attempt->isInProgress() && ! $attempt->isSubmitted()) {
            throw ValidationException::withMessages([
                'attempt' => 'Recording can only be stopped for an active attempt.',
            ]);
        }

        $metadata = $this->sanitizeMetadata($metadata);
        $status = $reason === 'attempt_completed' ? 'completed' : 'stopped';

        $recording = ProctoringRecording::query()->firstOrNew([
            'test_attempt_id' => $attempt->id,
            'recording_type' => $recordingType,
        ]);

        $recording->fill([
            'candidate_user_id' => $attempt->candidate_user_id,
            'status' => $status,
            'started_at' => $recording->started_at,
            'stopped_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
        $recording->save();

        $eventType = $reason === 'screen_share_ended' && $recordingType === 'screen'
            ? 'screen_share_ended'
            : "{$recordingType}_recording_stopped";

        $this->recordProctoringEvent->handle(
            $attempt,
            $eventType,
            null,
            array_merge([
                'recording_type' => $recordingType,
                'reason' => $reason,
            ], $metadata),
            $request,
        );

        return $recording->refresh();
    }
}
