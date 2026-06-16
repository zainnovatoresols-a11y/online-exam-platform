<?php

namespace App\Data\CodeExecution;

use Illuminate\Support\Collection;

class CodeRunResult
{
    /**
     * @param  iterable<CodeTestCaseResult>  $testCaseResults
     */
    public function __construct(
        public readonly string $status,
        public readonly iterable $testCaseResults,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return Collection<int, CodeTestCaseResult>
     */
    public function results(): Collection
    {
        return collect($this->testCaseResults)->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $results = $this->results();

        return [
            'status' => $this->status,
            'message' => $this->message,
            'total' => $results->count(),
            'passed' => $results->where('passed', true)->count(),
            'failed' => $results->where('passed', false)->count(),
        ];
    }
}
