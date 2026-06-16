<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code_execution_run_id',
    'question_test_case_id',
    'is_hidden',
    'status',
    'passed',
    'input',
    'expected_output',
    'actual_output',
    'stdout',
    'stderr',
    'compile_output',
    'message',
    'time',
    'memory',
    'judge0_token',
    'judge0_status_id',
    'judge0_status_description',
])]
class CodeExecutionTestCaseResult extends Model
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
            'passed' => 'boolean',
            'time' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<CodeExecutionRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(CodeExecutionRun::class, 'code_execution_run_id');
    }

    /**
     * @return BelongsTo<QuestionTestCase, $this>
     */
    public function questionTestCase(): BelongsTo
    {
        return $this->belongsTo(QuestionTestCase::class);
    }
}
