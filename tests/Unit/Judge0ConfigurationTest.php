<?php

namespace Tests\Unit;

use Tests\TestCase;

class Judge0ConfigurationTest extends TestCase
{
    public function test_rapidapi_config_keys_are_present(): void
    {
        $this->assertArrayHasKey('rapidapi_key', config('judge0'));
        $this->assertArrayHasKey('rapidapi_host', config('judge0'));
    }

    public function test_default_language_ids_are_configured(): void
    {
        $languageIds = config('judge0.language_ids');

        foreach (['php', 'javascript', 'python', 'java', 'cpp'] as $language) {
            $this->assertIsInt($languageIds[$language]);
            $this->assertGreaterThan(0, $languageIds[$language]);
        }
    }
}
