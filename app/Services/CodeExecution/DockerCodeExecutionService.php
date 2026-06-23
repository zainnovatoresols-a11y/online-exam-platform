<?php

namespace App\Services\CodeExecution;

use App\Data\CodeExecution\CodeRunResult;
use App\Data\CodeExecution\CodeTestCaseResult;
use App\Models\QuestionTestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class DockerCodeExecutionService implements CodeExecutionService
{
    public function runVisibleTestCases(
        string $language,
        string $sourceCode,
        iterable $testCases,
        ?int $timeLimitMs = null,
        ?int $memoryLimitKb = null,
    ): CodeRunResult {
        return $this->runTestCases($language, $sourceCode, $testCases, $timeLimitMs, $memoryLimitKb);
    }

    public function runTestCases(
        string $language,
        string $sourceCode,
        iterable $testCases,
        ?int $timeLimitMs = null,
        ?int $memoryLimitKb = null,
    ): CodeRunResult {
        $profile = $this->profile($language);
        $testCaseCollection = collect($testCases)->values();

        if ($testCaseCollection->isEmpty()) {
            return new CodeRunResult(status: 'completed', testCaseResults: []);
        }

        $runDirectory = $this->makeRunDirectory();

        try {
            $this->writeSourceFile($runDirectory, $profile['source_file'], $sourceCode);

            $compileResult = $this->compileIfNeeded($profile, $runDirectory, $timeLimitMs, $memoryLimitKb);

            if ($compileResult !== null && ! $compileResult['successful']) {
                $results = $this->compileFailedResults($testCaseCollection, $compileResult);

                return new CodeRunResult(
                    status: 'completed_with_failures',
                    testCaseResults: $results,
                    message: 'Compilation failed.',
                );
            }

            $results = $testCaseCollection
                ->map(fn (QuestionTestCase $testCase): CodeTestCaseResult => $this->runSingleTestCase(
                    profile: $profile,
                    runDirectory: $runDirectory,
                    testCase: $testCase,
                    timeLimitMs: $timeLimitMs,
                    memoryLimitKb: $memoryLimitKb,
                ))
                ->values();

            return new CodeRunResult(
                status: $results->every(fn (CodeTestCaseResult $result): bool => $result->passed)
                    ? 'completed'
                    : 'completed_with_failures',
                testCaseResults: $results,
            );
        } finally {
            File::deleteDirectory($runDirectory);
        }
    }

    /**
     * @return array{source_file: string, image: string, run_command: array<int, string>, compile_command?: array<int, string>}
     */
    private function profile(string $language): array
    {
        $image = config("code_execution.docker.images.{$language}");

        if (! is_string($image) || $image === '') {
            throw new CodeExecutionException("Docker image is not configured for [{$language}].");
        }

        return match ($language) {
            'php' => [
                'source_file' => 'solution.php',
                'image' => $image,
                'run_command' => ['php', 'solution.php'],
            ],
            'javascript' => [
                'source_file' => 'solution.js',
                'image' => $image,
                'run_command' => ['node', 'solution.js'],
            ],
            'python' => [
                'source_file' => 'solution.py',
                'image' => $image,
                'run_command' => ['python', 'solution.py'],
            ],
            'java' => [
                'source_file' => 'Main.java',
                'image' => $image,
                'compile_command' => ['javac', 'Main.java'],
                'run_command' => ['java', 'Main'],
            ],
            'cpp' => [
                'source_file' => 'main.cpp',
                'image' => $image,
                'compile_command' => ['g++', '-std=c++17', '-O2', '-pipe', 'main.cpp', '-o', 'main'],
                'run_command' => ['./main'],
            ],
            default => throw new CodeExecutionException("Docker code execution does not support [{$language}]."),
        };
    }

    private function makeRunDirectory(): string
    {
        $workspace = trim((string) config('code_execution.docker.workspace', 'code-execution'), '/\\');
        $directory = storage_path('app/'.$workspace.'/'.Str::uuid()->toString());

        File::ensureDirectoryExists($directory);

        return $directory;
    }

    private function writeSourceFile(string $runDirectory, string $sourceFile, string $sourceCode): void
    {
        File::put($runDirectory.DIRECTORY_SEPARATOR.$sourceFile, $sourceCode);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{successful: bool, stdout: string, stderr: string, exit_code: int|null, timed_out: bool, duration: float}|null
     */
    private function compileIfNeeded(
        array $profile,
        string $runDirectory,
        ?int $timeLimitMs,
        ?int $memoryLimitKb,
    ): ?array {
        if (! isset($profile['compile_command'])) {
            return null;
        }

        return $this->runDockerCommand(
            image: (string) $profile['image'],
            command: $profile['compile_command'],
            runDirectory: $runDirectory,
            input: '',
            timeLimitMs: $this->effectiveTimeLimitMs($timeLimitMs) * 2,
            memoryLimitKb: $memoryLimitKb,
        );
    }

    /**
     * @param  Collection<int, QuestionTestCase>  $testCases
     * @param  array{stdout: string, stderr: string}  $compileResult
     * @return Collection<int, CodeTestCaseResult>
     */
    private function compileFailedResults(Collection $testCases, array $compileResult): Collection
    {
        $compileOutput = trim($compileResult['stderr']."\n".$compileResult['stdout']);

        return $testCases
            ->map(fn (QuestionTestCase $testCase): CodeTestCaseResult => new CodeTestCaseResult(
                questionTestCaseId: $testCase->id,
                isHidden: (bool) $testCase->is_hidden,
                status: 'failed',
                passed: false,
                input: $testCase->input,
                expectedOutput: $testCase->expected_output,
                actualOutput: null,
                stdout: $compileResult['stdout'],
                stderr: $compileResult['stderr'],
                compileOutput: $compileOutput !== '' ? $compileOutput : null,
                message: 'Compilation failed.',
                judge0StatusDescription: 'Compilation Error',
            ))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function runSingleTestCase(
        array $profile,
        string $runDirectory,
        QuestionTestCase $testCase,
        ?int $timeLimitMs,
        ?int $memoryLimitKb,
    ): CodeTestCaseResult {
        $runResult = $this->runDockerCommand(
            image: (string) $profile['image'],
            command: $profile['run_command'],
            runDirectory: $runDirectory,
            input: (string) $testCase->input,
            timeLimitMs: $this->effectiveTimeLimitMs($timeLimitMs),
            memoryLimitKb: $memoryLimitKb,
        );

        $actualOutput = $runResult['stdout'];
        $expectedOutput = (string) $testCase->expected_output;
        $passed = $runResult['successful']
            && $this->normalizeOutput($actualOutput) === $this->normalizeOutput($expectedOutput);

        return new CodeTestCaseResult(
            questionTestCaseId: $testCase->id,
            isHidden: (bool) $testCase->is_hidden,
            status: $passed ? 'passed' : 'failed',
            passed: $passed,
            input: $testCase->input,
            expectedOutput: $testCase->expected_output,
            actualOutput: $actualOutput,
            stdout: $runResult['stdout'],
            stderr: $runResult['stderr'] !== '' ? $runResult['stderr'] : null,
            message: $this->messageForRun($runResult),
            time: number_format($runResult['duration'], 3, '.', ''),
            memory: $this->effectiveMemoryLimitKb($memoryLimitKb),
            judge0StatusDescription: $this->statusDescriptionForRun($runResult, $passed),
        );
    }

    /**
     * @param  array<int, string>  $command
     * @return array{successful: bool, stdout: string, stderr: string, exit_code: int|null, timed_out: bool, duration: float}
     */
    private function runDockerCommand(
        string $image,
        array $command,
        string $runDirectory,
        string $input,
        int $timeLimitMs,
        ?int $memoryLimitKb,
    ): array {
        $process = new Process($this->dockerCommand(
            image: $image,
            command: $command,
            runDirectory: $runDirectory,
            memoryLimitKb: $this->effectiveMemoryLimitKb($memoryLimitKb),
        ));

        $process->setEnv($this->dockerProcessEnvironment());
        $process->setInput($input);
        $process->setTimeout($this->processTimeoutSeconds($timeLimitMs));

        $startedAt = microtime(true);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return [
                'successful' => false,
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
                'exit_code' => null,
                'timed_out' => true,
                'duration' => microtime(true) - $startedAt,
            ];
        } catch (Throwable $exception) {
            throw new CodeExecutionException(
                'Docker code runner is not available. Please check Docker and code execution configuration.',
                previous: $exception,
            );
        }

        return [
            'successful' => $process->isSuccessful(),
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'exit_code' => $process->getExitCode(),
            'timed_out' => false,
            'duration' => microtime(true) - $startedAt,
        ];
    }

    /**
     * @param  array<int, string>  $command
     * @return array<int, string>
     */
    private function dockerCommand(string $image, array $command, string $runDirectory, int $memoryLimitKb): array
    {
        $dockerCommand = [
            $this->dockerBinary(),
            'run',
            '--rm',
            '-i',
            '--memory',
            $memoryLimitKb.'k',
            '--cpus',
            (string) config('code_execution.docker.cpus', '1'),
            '--pids-limit',
            (string) config('code_execution.docker.pids_limit', 128),
            '--read-only',
            '--tmpfs',
            '/tmp:rw,nosuid,size=64m',
            '-v',
            $this->dockerVolumePath($runDirectory).':/workspace',
            '-w',
            '/workspace',
        ];

        if ((bool) config('code_execution.docker.network_disabled', true)) {
            array_push($dockerCommand, '--network', 'none');
        }

        $user = config('code_execution.docker.user');

        if (is_string($user) && $user !== '') {
            array_push($dockerCommand, '--user', $user);
        }

        $dockerCommand[] = $image;

        return [
            ...$dockerCommand,
            ...$command,
        ];
    }

    private function dockerVolumePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * @return array<string, string>
     */
    private function dockerProcessEnvironment(): array
    {
        $dockerDirectory = dirname($this->dockerBinary());
        $path = (string) getenv('PATH');

        return [
            'PATH' => $dockerDirectory.PATH_SEPARATOR.$path,
        ];
    }

    private function dockerBinary(): string
    {
        $configured = (string) config('code_execution.docker.binary', 'docker');

        if ($this->isLocalBinaryPath($configured)) {
            return $configured;
        }

        foreach ([
            'C:/Program Files/Docker/Docker/resources/bin/docker.exe',
            'C:/Program Files/Docker/Docker/resources/bin/docker',
        ] as $candidate) {
            if ($this->isLocalBinaryPath($candidate)) {
                return $candidate;
            }
        }

        return $configured;
    }

    private function isLocalBinaryPath(string $path): bool
    {
        if (! str_contains($path, '/') && ! str_contains($path, '\\')) {
            return false;
        }

        return is_file($path);
    }

    private function effectiveTimeLimitMs(?int $timeLimitMs): int
    {
        return max($timeLimitMs ?? (int) config('code_execution.docker.default_time_limit_ms', 2000), 500);
    }

    private function effectiveMemoryLimitKb(?int $memoryLimitKb): int
    {
        return max($memoryLimitKb ?? (int) config('code_execution.docker.default_memory_limit_kb', 128000), 32000);
    }

    private function processTimeoutSeconds(int $timeLimitMs): int
    {
        $graceSeconds = max((int) config('code_execution.docker.process_grace_seconds', 5), 1);

        return (int) ceil($timeLimitMs / 1000) + $graceSeconds;
    }

    private function normalizeOutput(?string $output): string
    {
        return rtrim(str_replace(["\r\n", "\r"], "\n", (string) $output));
    }

    /**
     * @param  array{successful: bool, timed_out: bool, exit_code: int|null}  $runResult
     */
    private function messageForRun(array $runResult): ?string
    {
        if ($runResult['timed_out']) {
            return 'Time limit exceeded.';
        }

        if (! $runResult['successful']) {
            return 'Runtime error.';
        }

        return null;
    }

    /**
     * @param  array{successful: bool, timed_out: bool}  $runResult
     */
    private function statusDescriptionForRun(array $runResult, bool $passed): string
    {
        if ($passed) {
            return 'Accepted';
        }

        if ($runResult['timed_out']) {
            return 'Time Limit Exceeded';
        }

        if (! $runResult['successful']) {
            return 'Runtime Error';
        }

        return 'Wrong Answer';
    }
}
