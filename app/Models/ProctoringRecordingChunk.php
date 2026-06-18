<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'proctoring_recording_id',
    'test_attempt_id',
    'candidate_user_id',
    'proctoring_event_id',
    'recording_type',
    'disk',
    'path',
    'mime_type',
    'size_bytes',
    'sequence',
    'duration_ms',
    'recorded_at',
    'uploaded_at',
    'ip_address',
    'user_agent',
    'metadata',
])]
class ProctoringRecordingChunk extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ProctoringRecording, $this>
     */
    public function recording(): BelongsTo
    {
        return $this->belongsTo(ProctoringRecording::class, 'proctoring_recording_id');
    }

    /**
     * @return BelongsTo<TestAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /**
     * @return BelongsTo<ProctoringEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ProctoringEvent::class, 'proctoring_event_id');
    }
}
