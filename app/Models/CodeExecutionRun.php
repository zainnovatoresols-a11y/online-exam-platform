<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'test_attempt_id',
    'question_id',
    'attempt_answer_id',
    'candidate_user_id',
    'language',
    'status',
    'source_code',
    'result_summary',
    'started_at',
    'finished_at',
])]
class CodeExecutionRun extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<AttemptAnswer, $this>
     */
    public function attemptAnswer(): BelongsTo
    {
        return $this->belongsTo(AttemptAnswer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /**
     * @return HasMany<CodeExecutionTestCaseResult, $this>
     */
    public function testCaseResults(): HasMany
    {
        return $this->hasMany(CodeExecutionTestCaseResult::class);
    }
}
