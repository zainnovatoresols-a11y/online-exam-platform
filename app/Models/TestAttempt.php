<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Database\Factories\TestAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'test_id',
    'invitation_id',
    'candidate_user_id',
    'organization_id',
    'status',
    'started_at',
    'submitted_at',
    'expires_at',
    'score',
    'max_score',
    'total_marks',
    'percentage',
    'passed',
])]
class TestAttempt extends Model
{
    /** @use HasFactory<TestAttemptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expires_at' => 'datetime',
            'percentage' => 'decimal:2',
            'passed' => 'boolean',
        ];
    }

    /**
     * Get the test being attempted.
     *
     * @return BelongsTo<Test, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Get the accepted invitation used to start the attempt.
     *
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * Get the candidate who owns the attempt.
     *
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /**
     * Get the organization copied from the test when the attempt started.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get submitted answers.
     *
     * @return HasMany<AttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    /**
     * Get code execution runs for this attempt.
     *
     * @return HasMany<CodeExecutionRun, $this>
     */
    public function codeExecutionRuns(): HasMany
    {
        return $this->hasMany(CodeExecutionRun::class);
    }

    /**
     * Get proctoring events recorded during this attempt.
     *
     * @return HasMany<ProctoringEvent, $this>
     */
    public function proctoringEvents(): HasMany
    {
        return $this->hasMany(ProctoringEvent::class);
    }

    /**
     * Get the public candidate details attached to the attempt.
     *
     * @return HasOne<CandidateTestDetail, $this>
     */
    public function candidateDetail(): HasOne
    {
        return $this->hasOne(CandidateTestDetail::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === AttemptStatus::Submitted;
    }

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && now()->greaterThanOrEqualTo($this->expires_at)
            && ! $this->isSubmitted();
    }
}
