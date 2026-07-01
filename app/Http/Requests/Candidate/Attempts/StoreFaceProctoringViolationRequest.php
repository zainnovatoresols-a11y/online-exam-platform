<?php

namespace App\Http\Requests\Candidate\Attempts;

use App\Http\Requests\Concerns\ValidatesSafeMetadata;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFaceProctoringViolationRequest extends FormRequest
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
            'violation_type' => ['required', 'string', Rule::in(['no_face', 'multiple_faces'])],
            'face_count' => ['required', 'integer', 'min:0', 'max:20'],
            'snapshot' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'captured_at' => ['nullable', 'date'],
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
