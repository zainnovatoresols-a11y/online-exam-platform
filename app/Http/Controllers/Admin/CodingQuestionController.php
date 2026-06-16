<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Questions\CreateCodingQuestion;
use App\Actions\Questions\UpdateCodingQuestion;
use App\Enums\CodingDifficulty;
use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCodingQuestionRequest;
use App\Http\Requests\Admin\UpdateCodingQuestionRequest;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CodingQuestionController extends Controller
{
    public function create(Test $test): Response
    {
        Gate::authorize('create', [Question::class, $test]);
        $this->ensureTestAllowsCodingChanges($test);

        return Inertia::render('Admin/CodingQuestions/Create', [
            'test' => $test,
            'difficulties' => $this->difficultyOptions(),
            'languages' => $this->languageOptions(),
            'next_order' => ((int) $test->questions()->max('order')) + 1,
        ]);
    }

    public function store(
        StoreCodingQuestionRequest $request,
        Test $test,
        CreateCodingQuestion $createCodingQuestion,
    ): RedirectResponse {
        Gate::authorize('create', [Question::class, $test]);
        $this->ensureTestAllowsCodingChanges($test);

        $createCodingQuestion->handle($test, $request->validated());

        return to_route('admin.tests.questions.index', $test)
            ->with('success', 'Coding question created successfully.');
    }

    public function edit(Test $test, Question $question): Response
    {
        $this->ensureCodingQuestionBelongsToTest($test, $question);
        Gate::authorize('update', $question);
        $this->ensureTestAllowsCodingChanges($test);

        return Inertia::render('Admin/CodingQuestions/Edit', [
            'test' => $test,
            'question' => $this->questionPayload($question->load('testCases')),
            'difficulties' => $this->difficultyOptions(),
            'languages' => $this->languageOptions(),
        ]);
    }

    public function update(
        UpdateCodingQuestionRequest $request,
        Test $test,
        Question $question,
        UpdateCodingQuestion $updateCodingQuestion,
    ): RedirectResponse {
        $this->ensureCodingQuestionBelongsToTest($test, $question);
        Gate::authorize('update', $question);
        $this->ensureTestAllowsCodingChanges($test);

        $updateCodingQuestion->handle($question, $request->validated());

        return to_route('admin.tests.questions.index', $test)
            ->with('success', 'Coding question updated successfully.');
    }

    public function destroy(Test $test, Question $question): RedirectResponse
    {
        $this->ensureCodingQuestionBelongsToTest($test, $question);
        Gate::authorize('delete', $question);
        $this->ensureTestAllowsCodingChanges($test);

        $question->delete();

        return to_route('admin.tests.questions.index', $test)
            ->with('success', 'Coding question deleted successfully.');
    }

    private function ensureCodingQuestionBelongsToTest(Test $test, Question $question): void
    {
        abort_unless((int) $question->test_id === (int) $test->id, 404);
        abort_unless($question->type === QuestionType::Coding->value, 404);
    }

    private function ensureTestAllowsCodingChanges(Test $test): void
    {
        abort_if($test->isPublished(), 403);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function difficultyOptions(): array
    {
        return collect(CodingDifficulty::cases())
            ->map(fn (CodingDifficulty $difficulty): array => [
                'value' => $difficulty->value,
                'label' => Str::headline($difficulty->value),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function languageOptions(): array
    {
        return collect(ProgrammingLanguage::cases())
            ->map(fn (ProgrammingLanguage $language): array => [
                'value' => $language->value,
                'label' => match ($language) {
                    ProgrammingLanguage::Cpp => 'C++',
                    ProgrammingLanguage::Php => 'PHP',
                    default => Str::headline($language->value),
                },
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(Question $question): array
    {
        return [
            'id' => $question->id,
            'body' => $question->body,
            'marks' => $question->marks,
            'order' => $question->order,
            'difficulty' => $question->difficulty,
            'time_limit_ms' => $question->time_limit_ms,
            'memory_limit_kb' => $question->memory_limit_kb,
            'supported_languages' => $question->supported_languages ?? [],
            'starter_code' => $question->starter_code ?? [],
            'test_cases' => $question->testCases
                ->sortBy('sort_order')
                ->map(fn ($testCase): array => [
                    'id' => $testCase->id,
                    'input' => $testCase->input,
                    'expected_output' => $testCase->expected_output,
                    'is_hidden' => $testCase->is_hidden,
                    'points' => $testCase->points,
                ])
                ->values(),
        ];
    }
}
