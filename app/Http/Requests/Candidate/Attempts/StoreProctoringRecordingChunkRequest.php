<?php

namespace App\Http\Requests\Candidate\Attempts;

use App\Http\Requests\Concerns\ValidatesBrowserMediaTypes;
use App\Http\Requests\Concerns\ValidatesSafeMetadata;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProctoringRecordingChunkRequest extends FormRequest
{
    use ValidatesBrowserMediaTypes;
    use ValidatesSafeMetadata;

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
            'recording_type' => ['bail', 'required', 'string', Rule::in(['camera', 'screen'])],
            'chunk' => ['bail', 'required', 'file', 'mimetypes:video/webm,video/mp4,application/octet-stream', 'max:5120'],
            'sequence' => ['bail', 'required', 'integer', 'min:1', 'max:100000'],
            'duration_ms' => ['nullable', 'integer', 'min:1', 'max:60000'],
            'recorded_at' => ['nullable', 'date'],
            'mime_type' => $this->browserMediaTypeRules(),
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator): mixed => $this->validateSafeMetadata($validator));
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()->messages(),
        ], 422));
    }
}
