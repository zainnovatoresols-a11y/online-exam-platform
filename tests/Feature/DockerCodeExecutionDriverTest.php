<?php

namespace Tests\Feature;

use App\Services\CodeExecution\CodeExecutionService;
use App\Services\CodeExecution\DockerCodeExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DockerCodeExecutionDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_docker_driver_can_be_resolved_from_code_execution_config(): void
    {
        config(['code_execution.driver' => 'docker']);

        $this->assertInstanceOf(
            DockerCodeExecutionService::class,
            app(CodeExecutionService::class),
        );
    }
}
