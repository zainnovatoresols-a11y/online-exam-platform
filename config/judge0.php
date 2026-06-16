<?php

$languageId = fn (string $key, int $default): int => filled(env($key))
    ? (int) env($key)
    : $default;

return [
    'base_url' => env('JUDGE0_BASE_URL', 'https://judge0-ce.p.rapidapi.com'),
    'api_key' => env('JUDGE0_API_KEY'),
    'rapidapi_key' => env('JUDGE0_RAPIDAPI_KEY'),
    'rapidapi_host' => env('JUDGE0_RAPIDAPI_HOST', 'judge0-ce.p.rapidapi.com'),
    'submission_wait' => filter_var(env('JUDGE0_SUBMISSION_WAIT', false), FILTER_VALIDATE_BOOLEAN),
    'poll_attempts' => (int) env('JUDGE0_POLL_ATTEMPTS', 10),
    'poll_sleep_ms' => (int) env('JUDGE0_POLL_SLEEP_MS', 500),
    'request_timeout' => (int) env('JUDGE0_REQUEST_TIMEOUT', 15),
    'language_ids' => [
        'php' => $languageId('JUDGE0_LANGUAGE_PHP', 68),
        'javascript' => $languageId('JUDGE0_LANGUAGE_JAVASCRIPT', 63),
        'python' => $languageId('JUDGE0_LANGUAGE_PYTHON', 71),
        'java' => $languageId('JUDGE0_LANGUAGE_JAVA', 62),
        'cpp' => $languageId('JUDGE0_LANGUAGE_CPP', 54),
    ],
];
