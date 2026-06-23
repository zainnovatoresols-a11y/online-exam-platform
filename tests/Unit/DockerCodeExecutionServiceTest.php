<?php

namespace Tests\Unit;

use App\Services\CodeExecution\DockerCodeExecutionService;
use ReflectionClass;
use Tests\TestCase;

class DockerCodeExecutionServiceTest extends TestCase
{
    public function test_docker_command_keeps_stdin_attached_for_candidate_code(): void
    {
        $service = app(DockerCodeExecutionService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('dockerCommand');

        $command = $method->invoke(
            $service,
            'python:3.12-alpine',
            ['python', 'solution.py'],
            'C:/code-execution/run-123',
            128000,
        );

        $imageIndex = array_search('python:3.12-alpine', $command, true);

        $this->assertNotFalse($imageIndex);
        $this->assertContains('-i', array_slice($command, 0, $imageIndex));
    }
}
