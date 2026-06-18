<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesSafeMetadata
{
    protected function validateSafeMetadata(Validator $validator, string $field = 'metadata'): void
    {
        $metadata = $this->input($field, []);

        if ($metadata === null || ! is_array($metadata)) {
            return;
        }

        $encoded = json_encode($metadata);

        if ($encoded === false || strlen($encoded) > 4096) {
            $validator->errors()->add($field, 'Metadata must be smaller than 4 KB.');

            return;
        }

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || strlen($key) > 80) {
                $validator->errors()->add($field, 'Metadata keys must be short strings.');

                return;
            }

            if (! is_string($value)
                && ! is_int($value)
                && ! is_float($value)
                && ! is_bool($value)
                && $value !== null) {
                $validator->errors()->add("{$field}.{$key}", 'Metadata values must be simple scalar values.');

                return;
            }

            if (is_string($value) && strlen($value) > 500) {
                $validator->errors()->add("{$field}.{$key}", 'Metadata values must be 500 characters or fewer.');
            }
        }
    }
}
