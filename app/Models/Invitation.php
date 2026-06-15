<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'organization_id',
    'test_id',
    'invited_by',
    'candidate_user_id',
    'name',
    'email',
    'token',
    'status',
    'starts_at',
    'expires_at',
    'accepted_at',
    'policy_accepted_at',
    'candidate_profile',
    'revoked_at',
])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'policy_accepted_at' => 'datetime',
            'candidate_profile' => 'array',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Get the organization copied from the invited test.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the test this invitation belongs to.
     *
     * @return BelongsTo<Test, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Get the admin who sent the invitation.
     *
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the candidate user who accepted the invitation.
     *
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /**
     * Get the attempt started from this invitation.
     *
     * @return HasOne<TestAttempt, $this>
     */
    public function attempt(): HasOne
    {
        return $this->hasOne(TestAttempt::class);
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::Pending;
    }

    public function isSent(): bool
    {
        return $this->status === InvitationStatus::Sent;
    }

    public function isAcceptable(): bool
    {
        return $this->isPending() || $this->isSent();
    }

    public function isAccepted(): bool
    {
        return $this->status === InvitationStatus::Accepted;
    }

    public function isRevoked(): bool
    {
        return $this->status === InvitationStatus::Revoked;
    }

    public function isExpired(): bool
    {
        return $this->status === InvitationStatus::Expired;
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasStarted(): bool
    {
        return $this->starts_at === null || now()->greaterThanOrEqualTo($this->starts_at);
    }
}
