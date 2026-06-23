<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\McqQuestionRequest;
use App\Http\Requests\Admin\ReorderQuestionsRequest;
use App\Models\Question;
use App\Models\Test;
use App\Actions\Questions\ReorderQuestions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function index(Test $test): Response
    {
        Gate::authorize('viewAny', [Question::class, $test]);

        return Inertia::render('Admin/Questions/Index', [
            'test' => $test,
            'canManageQuestions' => Gate::allows('create', [Question::class, $test]),
            'canManageCodingQuestions' => Gate::allows('create', [Question::class, $test]) && ! $test->isPublished(),
            'canReorderQuestions' => Gate::allows('reorder', [Question::class, $test]),
            'questions' => $test->questions()
                ->with('options:id,question_id,body,is_correct')
                ->withCount(['options', 'testCases'])
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->map(fn (Question $question): array => [
                    'id' => $question->id,
                    'type' => $question->type,
                    'body' => $question->body,
                    'marks' => $question->marks,
                    'order' => $question->order,
                    'difficulty' => $question->difficulty,
                    'supported_languages' => $question->supported_languages ?? [],
                    'options_count' => $question->options_count,
                    'test_cases_count' => $question->test_cases_count,
                    'options' => $question->options->map(fn ($option): array => [
                        'id' => $option->id,
                        'body' => $option->body,
                        'is_correct' => $option->is_correct,
                    ])->values(),
                ]),
        ]);
    }

    public function reorder(
        ReorderQuestionsRequest $request,
        Test $test,
        ReorderQuestions $reorderQuestions,
    ): RedirectResponse {
        Gate::authorize('reorder', [Question::class, $test]);

        $validated = $request->validated();

        $reorderQuestions->handle($test, $validated['question_ids']);

        return to_route('admin.tests.questions.index', $test)
            ->with('success', 'Question order updated successfully.');
    }

    public function create(Test $test): Response
    {
        Gate::authorize('create', [Question::class, $test]);

        return Inertia::render('Admin/Questions/Create', [
            'test' => $test,
        ]);
    }

    public function store(McqQuestionRequest $request, Test $test): RedirectResponse
    {
        Gate::authorize('create', [Question::class, $test]);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $test): void {
            $question = $test->questions()->create([
                'type' => QuestionType::Mcq->value,
                'body' => $validated['body'],
                'marks' => $validated['marks'],
                'order' => ((int) $test->questions()->max('order')) + 1,
            ]);

            $this->syncOptions($question, $validated['options']);
        });

        return to_route('admin.tests.questions.index', $test)
            ->with('success', 'MCQ question created successfully.');
    }

    public function edit(Test $test, Question $question): Response
    {
        $this->ensureMcqQuestionBelongsToTest($test, $question);
        Gate::authorize('update', $question);

        return Inertia::render('Admin/Questions/Edit', [
            'test' => $test,
            'question' => $question->load('options:id,question_id,body,is_correct'),
        ]);
    }

    public function update(McqQuestionRequest $request, Test $test, Question $question): RedirectResponse
    {
        $this->ensureMcqQuestionBelongsToTest($test, $question);
        Gate::authorize('update', $question);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $question): void {
            $question->update([
                'body' => $validated['body'],
                'marks' => $validated['marks'],
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $validated['options']);
        });

        return to_route('admin.tests.questions.index', $test)
            ->with('success', 'MCQ question updated successfully.');
    }

    public function destroy(Test $test, Question $question): RedirectResponse
    {
        $this->ensureMcqQuestionBelongsToTest($test, $question);
        Gate::authorize('delete', $question);

        $question->delete();

        return to_route('admin.tests.questions.index', $test)
            ->with('success', 'Question deleted successfully.');
    }

    /**
     * @param  array<int, array{body: string, is_correct: bool}>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        foreach ($options as $option) {
            $question->options()->create([
                'body' => $option['body'],
                'is_correct' => $option['is_correct'],
            ]);
        }
    }

    private function ensureMcqQuestionBelongsToTest(Test $test, Question $question): void
    {
        abort_unless($question->test_id === $test->id, 404);
        abort_unless($question->type === QuestionType::Mcq->value, 404);
    }
}
