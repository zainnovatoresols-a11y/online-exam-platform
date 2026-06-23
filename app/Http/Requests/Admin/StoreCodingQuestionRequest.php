<?php

namespace App\Http\Requests\Admin;

use App\Enums\CodingDifficulty;
use App\Enums\ProgrammingLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCodingQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $languages = $this->languageValues();

        return [
            'body' => ['required', 'string'],
            'marks' => ['required', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:0'],
            'difficulty' => ['required', Rule::in($this->difficultyValues())],
            'time_limit_ms' => ['required', 'integer', 'min:500', 'max:10000'],
            'memory_limit_kb' => ['nullable', 'integer', 'min:32000', 'max:512000'],
            'supported_languages' => ['required', 'array', 'min:1'],
            'supported_languages.*' => ['required', Rule::in($languages)],
            'starter_code' => ['nullable', 'array'],
            'starter_code.php' => ['nullable', 'string'],
            'starter_code.javascript' => ['nullable', 'string'],
            'starter_code.python' => ['nullable', 'string'],
            'starter_code.java' => ['nullable', 'string'],
            'starter_code.cpp' => ['nullable', 'string'],
            'test_cases' => ['required', 'array', 'min:1'],
            'test_cases.*.input' => ['nullable', 'string'],
            'test_cases.*.expected_output' => ['required', 'string'],
            'test_cases.*.is_hidden' => ['nullable', 'boolean'],
            'test_cases.*.points' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateVisibleTestCase($validator);
            $this->validateStarterCodeLanguages($validator);
        });
    }

    private function validateVisibleTestCase(Validator $validator): void
    {
        $visibleCount = collect($this->input('test_cases', []))
            ->filter(fn (mixed $testCase): bool => ! filter_var(
                data_get($testCase, 'is_hidden', false),
                FILTER_VALIDATE_BOOLEAN,
            ))
            ->count();

        if ($visibleCount === 0) {
            $validator->errors()->add('test_cases', 'At least one visible test case is required.');
        }
    }

    private function validateStarterCodeLanguages(Validator $validator): void
    {
        $selectedLanguages = collect($this->input('supported_languages', []))
            ->filter(fn (mixed $language): bool => is_string($language))
            ->values();

        collect($this->input('starter_code', []))
            ->keys()
            ->each(function (mixed $language) use ($selectedLanguages, $validator): void {
                if (! is_string($language)) {
                    return;
                }

                if (! in_array($language, $this->languageValues(), true) || ! $selectedLanguages->contains($language)) {
                    $validator->errors()->add(
                        "starter_code.{$language}",
                        'Starter code may only be added for selected supported languages.',
                    );
                }
            });
    }

    /**
     * @return array<int, string>
     */
    private function difficultyValues(): array
    {
        return array_column(CodingDifficulty::cases(), 'value');
    }

    /**
     * @return array<int, string>
     */
    private function languageValues(): array
    {
        return array_column(ProgrammingLanguage::cases(), 'value');
    }
}
