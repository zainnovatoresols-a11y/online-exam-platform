<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'question_id',
    'input',
    'expected_output',
    'is_hidden',
    'sort_order',
    'points',
])]
class QuestionTestCase extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return HasMany<CodeExecutionTestCaseResult, $this>
     */
    public function codeExecutionResults(): HasMany
    {
        return $this->hasMany(CodeExecutionTestCaseResult::class);
    }
}
