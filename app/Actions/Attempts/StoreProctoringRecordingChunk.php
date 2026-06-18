<?php

namespace App\Actions\Attempts;

use App\Actions\Attempts\Concerns\SanitizesProctoringMetadata;
use App\Models\ProctoringRecording;
use App\Models\ProctoringRecordingChunk;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StoreProctoringRecordingChunk
{
    use SanitizesProctoringMetadata;

    public function __construct(private readonly RecordProctoringEvent $recordProctoringEvent) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        TestAttempt $attempt,
        string $recordingType,
        UploadedFile $chunkFile,
        int $sequence,
        ?int $durationMs,
        ?string $recordedAt,
        ?string $mimeType,
        array $metadata,
        Request $request,
    ): ProctoringRecordingChunk {
        $this->ensureRecordable($attempt);

        $metadata = $this->sanitizeMetadata($metadata);
        $mimeType ??= $chunkFile->getMimeType();

        $recording = ProctoringRecording::query()->firstOrCreate([
            'test_attempt_id' => $attempt->id,
            'recording_type' => $recordingType,
        ], [
            'candidate_user_id' => $attempt->candidate_user_id,
            'status' => 'recording',
            'started_at' => now(),
            'mime_type' => $mimeType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);

        $directory = "proctoring/attempts/{$attempt->id}/recordings/{$recordingType}";
        $filename = sprintf('%s_%06d.webm', $recordingType, $sequence);
        $path = $chunkFile->storeAs($directory, $filename, 'local');

        if (! $path) {
            throw ValidationException::withMessages([
                'chunk' => 'The recording chunk could not be stored.',
            ]);
        }

        $event = $this->recordProctoringEvent->handle(
            $attempt,
            "{$recordingType}_recording_chunk_uploaded",
            null,
            array_merge([
                'recording_type' => $recordingType,
                'sequence' => $sequence,
                'duration_ms' => $durationMs,
                'mime_type' => $mimeType,
            ], $metadata),
            $request,
        )['event'];

        $uploadedAt = now();
        $recordedAtValue = $recordedAt ? Carbon::parse($recordedAt) : $uploadedAt;

        $recordingChunk = $recording->chunks()->create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'proctoring_event_id' => $event->id,
            'recording_type' => $recordingType,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $mimeType,
            'size_bytes' => $chunkFile->getSize(),
            'sequence' => $sequence,
            'duration_ms' => $durationMs,
            'recorded_at' => $recordedAtValue,
            'uploaded_at' => $uploadedAt,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);

        $recording->forceFill([
            'status' => 'recording',
            'last_chunk_at' => $uploadedAt,
            'chunk_count' => $recording->chunks()->count(),
            'total_size_bytes' => $recording->chunks()->sum('size_bytes'),
            'mime_type' => $recording->mime_type ?? $mimeType,
        ])->save();

        return $recordingChunk;
    }

    private function ensureRecordable(TestAttempt $attempt): void
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'Recording chunks can only be uploaded for an in-progress attempt.',
            ]);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'Recording chunks can no longer be uploaded after the attempt has expired.',
            ]);
        }
    }
}
