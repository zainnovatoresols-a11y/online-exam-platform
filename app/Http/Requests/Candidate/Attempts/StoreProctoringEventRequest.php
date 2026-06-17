<?php

namespace App\Http\Requests\Candidate\Attempts;

use App\Actions\Attempts\RecordProctoringEvent;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProctoringEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_type' => [
                'required',
                'string',
                Rule::in(RecordProctoringEvent::eventTypes()),
            ],
            'occurred_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $metadata = $this->input('metadata', []);

            if ($metadata === null) {
                return;
            }

            if (! is_array($metadata)) {
                return;
            }

            $encoded = json_encode($metadata);

            if ($encoded === false || strlen($encoded) > 4096) {
                $validator->errors()->add('metadata', 'Metadata must be smaller than 4 KB.');

                return;
            }

            foreach ($metadata as $key => $value) {
                if (! is_string($key) || strlen($key) > 80) {
                    $validator->errors()->add('metadata', 'Metadata keys must be short strings.');

                    return;
                }

                if (! is_string($value)
                    && ! is_int($value)
                    && ! is_float($value)
                    && ! is_bool($value)
                    && $value !== null) {
                    $validator->errors()->add("metadata.$key", 'Metadata values must be simple scalar values.');

                    return;
                }

                if (is_string($value) && strlen($value) > 500) {
                    $validator->errors()->add("metadata.$key", 'Metadata values must be 500 characters or fewer.');
                }
            }
        });
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()->messages(),
        ], 422));
    }
}
