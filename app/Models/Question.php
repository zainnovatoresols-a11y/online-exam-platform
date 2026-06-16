<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'test_id',
    'type',
    'body',
    'marks',
    'order',
    'difficulty',
    'time_limit_ms',
    'memory_limit_kb',
    'supported_languages',
    'starter_code',
])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supported_languages' => 'array',
            'starter_code' => 'array',
        ];
    }

    /**
     * Get the test that owns the question.
     *
     * @return BelongsTo<Test, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Get answer options for the question.
     *
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    /**
     * Get candidate answers submitted for this question.
     *
     * @return HasMany<AttemptAnswer, $this>
     */
    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    /**
     * Get coding test cases for the question.
     *
     * @return HasMany<QuestionTestCase, $this>
     */
    public function testCases(): HasMany
    {
        return $this->hasMany(QuestionTestCase::class);
    }
}
