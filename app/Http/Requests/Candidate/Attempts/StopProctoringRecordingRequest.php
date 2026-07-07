<?php

namespace App\Http\Requests\Candidate\Attempts;

use App\Http\Requests\Concerns\ValidatesSafeMetadata;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StopProctoringRecordingRequest extends FormRequest
{
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
            'reason' => ['nullable', 'string', 'max:100', 'regex:/\A[a-z0-9_. -]+\z/i'],
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
