<?php

namespace App\Actions\Attempts;

use App\Actions\Attempts\Concerns\SanitizesProctoringMetadata;
use App\Models\ProctoringFaceSnapshot;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreFaceProctoringViolation
{
    use SanitizesProctoringMetadata;

    private const EVENT_TYPES = [
        'no_face' => 'face_no_face_detected',
        'multiple_faces' => 'face_multiple_faces_detected',
    ];

    public function __construct(private readonly RecordProctoringEvent $recordProctoringEvent) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        TestAttempt $attempt,
        string $violationType,
        int $faceCount,
        UploadedFile $snapshot,
        ?string $capturedAt,
        array $metadata,
        Request $request,
    ): ProctoringFaceSnapshot {
        $this->ensureRecordable($attempt);

        if (! array_key_exists($violationType, self::EVENT_TYPES)) {
            throw ValidationException::withMessages([
                'violation_type' => 'The selected face violation type is invalid.',
            ]);
        }

        $metadata = $this->sanitizeMetadata($metadata);
        $mimeType = $snapshot->getMimeType();
        $capturedAtValue = $capturedAt ? Carbon::parse($capturedAt) : now();

        $directory = "proctoring/attempts/{$attempt->id}/face-violations";
        $filename = sprintf(
            '%s_%s_%s.%s',
            $violationType,
            now()->format('YmdHisv'),
            Str::lower(Str::random(8)),
            $this->extensionFor($mimeType),
        );
        $path = $snapshot->storeAs($directory, $filename, 'local');

        if (! $path) {
            throw ValidationException::withMessages([
                'snapshot' => 'The face monitoring snapshot could not be stored.',
            ]);
        }

        $event = $this->recordProctoringEvent->handle(
            $attempt,
            self::EVENT_TYPES[$violationType],
            $capturedAtValue->toISOString(),
            array_merge([
                'violation_type' => $violationType,
                'face_count' => $faceCount,
                'snapshot_mime_type' => $mimeType,
                'snapshot_size_bytes' => $snapshot->getSize(),
            ], $metadata),
            $request,
        )['event'];

        return $attempt->proctoringFaceSnapshots()->create([
            'candidate_user_id' => $attempt->candidate_user_id,
            'proctoring_event_id' => $event->id,
            'violation_type' => $violationType,
            'face_count' => $faceCount,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $mimeType,
            'size_bytes' => $snapshot->getSize(),
            'captured_at' => $capturedAtValue,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    private function ensureRecordable(TestAttempt $attempt): void
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'Face monitoring events can only be uploaded for an in-progress attempt.',
            ]);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages([
                'attempt' => 'Face monitoring events can no longer be uploaded after the attempt has expired.',
            ]);
        }
    }

    private function extensionFor(?string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
