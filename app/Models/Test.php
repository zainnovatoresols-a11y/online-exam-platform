<?php

namespace App\Models;

use App\Enums\TestStatus;
use Database\Factories\TestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'organization_id',
    'created_by_id',
    'title',
    'description',
    'duration_minutes',
    'pass_mark',
    'starts_at',
    'public_token',
    'public_access_enabled',
    'candidate_fields',
    'policy_text',
    'status',
    'published_at',
    'closed_at',
])]
class Test extends Model
{
    /** @use HasFactory<TestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'public_access_enabled' => 'boolean',
            'candidate_fields' => 'array',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public const DEFAULT_POLICY_TEXT = <<<'POLICY'
Read all instructions carefully before starting the test.
Do not copy, paste, or use outside help during the test.
Do not switch tabs, open another browser, or use another device for answers.
Answer every question yourself and submit before the timer ends.
Once the test starts, the timer cannot be paused.
POLICY;

    public static function newPublicToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::query()->where('public_token', $token)->exists());

        return $token;
    }

    /**
     * Get the organization that owns the test.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the admin who created the test.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get questions attached to the test.
     *
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get invitations sent for this test.
     *
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get candidate attempts for this test.
     *
     * @return HasMany<TestAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    /**
     * Get public candidate detail records submitted for this test.
     *
     * @return HasMany<CandidateTestDetail, $this>
     */
    public function candidateDetails(): HasMany
    {
        return $this->hasMany(CandidateTestDetail::class);
    }

    public function isOrganizationTest(): bool
    {
        return $this->organization_id !== null;
    }

    public function isSoloTest(): bool
    {
        return $this->organization_id === null;
    }

    public function belongsToAdminScope(User $admin): bool
    {
        if ($this->isOrganizationTest()) {
            return $admin->organization_id !== null
                && $this->organization_id === $admin->organization_id;
        }

        return (int) $this->created_by_id === (int) $admin->id;
    }

    public function isDraft(): bool
    {
        return $this->status === TestStatus::Draft->value;
    }

    public function isPublished(): bool
    {
        return $this->status === TestStatus::Published->value;
    }

    public function hasStarted(): bool
    {
        return $this->starts_at === null || now()->greaterThanOrEqualTo($this->starts_at);
    }

    public function isClosed(): bool
    {
        return $this->status === TestStatus::Closed->value;
    }

    /**
     * @return list<string>
     */
    public function candidateRegistrationFields(): array
    {
        return collect($this->candidate_fields ?? [])
            ->filter(fn (mixed $field): bool => in_array($field, ['phone', 'stack_name'], true))
            ->unique()
            ->values()
            ->all();
    }

    public function policyText(): string
    {
        if (filled($this->policy_text)) {
            return (string) $this->policy_text;
        }

        return self::DEFAULT_POLICY_TEXT;
    }
}
