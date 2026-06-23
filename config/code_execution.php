<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported drivers: fake, judge0, docker
    */
    'driver' => env('CODE_EXECUTION_DRIVER', 'judge0'),

    'queue_final_grading' => (bool) env('CODE_EXECUTION_QUEUE_FINAL_GRADING', false),

    'docker' => [
        'binary' => env('CODE_EXECUTION_DOCKER_BINARY', 'docker'),
        'workspace' => env('CODE_EXECUTION_DOCKER_WORKSPACE', 'code-execution'),
        'network_disabled' => (bool) env('CODE_EXECUTION_DOCKER_NETWORK_DISABLED', true),
        'cpus' => env('CODE_EXECUTION_DOCKER_CPUS', '1'),
        'pids_limit' => (int) env('CODE_EXECUTION_DOCKER_PIDS_LIMIT', 128),
        'default_time_limit_ms' => (int) env('CODE_EXECUTION_DOCKER_DEFAULT_TIME_LIMIT_MS', 2000),
        'default_memory_limit_kb' => (int) env('CODE_EXECUTION_DOCKER_DEFAULT_MEMORY_LIMIT_KB', 128000),
        'process_grace_seconds' => (int) env('CODE_EXECUTION_DOCKER_PROCESS_GRACE_SECONDS', 5),
        'user' => env('CODE_EXECUTION_DOCKER_USER'),
        'images' => [
            'php' => env('CODE_EXECUTION_DOCKER_IMAGE_PHP', 'php:8.3-cli-alpine'),
            'javascript' => env('CODE_EXECUTION_DOCKER_IMAGE_JAVASCRIPT', 'node:22-alpine'),
            'python' => env('CODE_EXECUTION_DOCKER_IMAGE_PYTHON', 'python:3.12-alpine'),
            'java' => env('CODE_EXECUTION_DOCKER_IMAGE_JAVA', 'eclipse-temurin:21-jdk-alpine'),
            'cpp' => env('CODE_EXECUTION_DOCKER_IMAGE_CPP', 'gcc:14'),
        ],
    ],
];
