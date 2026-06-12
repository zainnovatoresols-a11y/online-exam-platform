<?php

namespace App\Models;

use App\Enums\TestStatus;
use Database\Factories\TestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'created_by_id',
    'title',
    'description',
    'duration_minutes',
    'pass_mark',
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
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
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
}
