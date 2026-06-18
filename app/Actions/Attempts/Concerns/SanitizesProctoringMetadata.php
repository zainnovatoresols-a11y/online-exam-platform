<?php

namespace App\Actions\Attempts\Concerns;

trait SanitizesProctoringMetadata
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, string|int|float|bool|null>
     */
    protected function sanitizeMetadata(array $metadata): array
    {
        $safe = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || strlen($key) > 80) {
                continue;
            }

            if (is_string($value)) {
                $safe[$key] = substr($value, 0, 500);

                continue;
            }

            if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }
}
