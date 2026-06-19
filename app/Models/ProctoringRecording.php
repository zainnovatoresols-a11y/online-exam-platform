<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'test_attempt_id',
    'candidate_user_id',
    'recording_type',
    'status',
    'started_at',
    'stopped_at',
    'denied_at',
    'last_chunk_at',
    'chunk_count',
    'total_size_bytes',
    'merged_disk',
    'merged_path',
    'merged_status',
    'merged_at',
    'merged_size_bytes',
    'merge_error',
    'mime_type',
    'ip_address',
    'user_agent',
    'metadata',
])]
class ProctoringRecording extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
            'denied_at' => 'datetime',
            'last_chunk_at' => 'datetime',
            'merged_at' => 'datetime',
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
     * @return HasMany<ProctoringRecordingChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(ProctoringRecordingChunk::class);
    }
}
