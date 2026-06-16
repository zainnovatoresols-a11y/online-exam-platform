<?php

namespace App\Services\CodeExecution;

use App\Data\CodeExecution\CodeRunResult;

interface CodeExecutionService
{
    public function runVisibleTestCases(
        string $language,
        string $sourceCode,
        iterable $testCases,
        ?int $timeLimitMs = null,
        ?int $memoryLimitKb = null,
    ): CodeRunResult;
}
