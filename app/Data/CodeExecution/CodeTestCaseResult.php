<?php

namespace App\Data\CodeExecution;

class CodeTestCaseResult
{
    public function __construct(
        public readonly int $questionTestCaseId,
        public readonly bool $isHidden,
        public readonly string $status,
        public readonly bool $passed,
        public readonly ?string $input,
        public readonly ?string $expectedOutput,
        public readonly ?string $actualOutput,
        public readonly ?string $stdout = null,
        public readonly ?string $stderr = null,
        public readonly ?string $compileOutput = null,
        public readonly ?string $message = null,
        public readonly ?string $time = null,
        public readonly ?int $memory = null,
        public readonly ?string $judge0Token = null,
        public readonly ?int $judge0StatusId = null,
        public readonly ?string $judge0StatusDescription = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'question_test_case_id' => $this->questionTestCaseId,
            'status' => $this->status,
            'passed' => $this->passed,
            'input' => $this->input,
            'expected_output' => $this->expectedOutput,
            'actual_output' => $this->actualOutput,
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
            'compile_output' => $this->compileOutput,
            'message' => $this->message,
            'time' => $this->time,
            'memory' => $this->memory,
            'judge0_status_description' => $this->judge0StatusDescription,
        ];
    }
}
