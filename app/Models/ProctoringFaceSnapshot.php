<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'test_attempt_id',
    'candidate_user_id',
    'proctoring_event_id',
    'violation_type',
    'face_count',
    'disk',
    'path',
    'mime_type',
    'size_bytes',
    'captured_at',
    'started_at',
    'ended_at',
    'duration_seconds',
    'ip_address',
    'user_agent',
    'metadata',
])]
class ProctoringFaceSnapshot extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
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
