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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => trim((string) $this->input('body')),
            'supported_languages' => collect($this->input('supported_languages', []))
                ->filter(fn (mixed $language): bool => is_string($language) && trim($language) !== '')
                ->map(fn (string $language): string => trim($language))
                ->unique()
                ->values()
                ->all(),
            'starter_code' => collect($this->input('starter_code', []))
                ->map(fn (mixed $code): ?string => $code === null ? null : (string) $code)
                ->all(),
            'test_cases' => collect($this->input('test_cases', []))
                ->map(fn (mixed $testCase): mixed => is_array($testCase)
                    ? [
                        ...$testCase,
                        'input' => filled($testCase['input'] ?? null) ? (string) $testCase['input'] : null,
                        'expected_output' => trim((string) ($testCase['expected_output'] ?? '')),
                        'is_hidden' => filter_var($testCase['is_hidden'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]
                    : $testCase)
                ->values()
                ->all(),
        ]);
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
            'body' => ['bail', 'required', 'string', 'min:10', 'max:30000'],
            'marks' => ['bail', 'required', 'integer', 'min:1', 'max:100000'],
            'order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'difficulty' => ['required', Rule::in($this->difficultyValues())],
            'time_limit_ms' => ['bail', 'required', 'integer', 'min:498', 'max:3600000'],
            'memory_limit_kb' => ['nullable', 'integer', 'min:32000', 'max:512000'],
            'supported_languages' => ['bail', 'required', 'array', 'min:1', 'max:'.count($languages)],
            'supported_languages.*' => ['bail', 'required', 'string', 'distinct', Rule::in($languages)],
            'starter_code' => ['nullable', 'array:php,javascript,python,java,cpp'],
            'starter_code.php' => ['nullable', 'string', 'max:50000'],
            'starter_code.javascript' => ['nullable', 'string', 'max:50000'],
            'starter_code.python' => ['nullable', 'string', 'max:50000'],
            'starter_code.java' => ['nullable', 'string', 'max:50000'],
            'starter_code.cpp' => ['nullable', 'string', 'max:50000'],
            'test_cases' => ['bail', 'required', 'array', 'min:1', 'max:50'],
            'test_cases.*' => ['required', 'array:input,expected_output,is_hidden,points'],
            'test_cases.*.input' => ['nullable', 'string', 'max:20000'],
            'test_cases.*.expected_output' => ['bail', 'required', 'string', 'max:20000'],
            'test_cases.*.is_hidden' => ['nullable', 'boolean'],
            'test_cases.*.points' => ['nullable', 'integer', 'min:1', 'max:100000'],
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
