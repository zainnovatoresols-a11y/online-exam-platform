<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tests\DeleteTestWithArtifacts;
use App\Enums\TestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTestRequest;
use App\Http\Requests\Admin\UpdateTestRequest;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Test::class);

        $user = $request->user();

        return Inertia::render('Admin/Tests/Index', [
            'tests' => Test::query()
                ->where(function ($query) use ($user): void {
                    if ($user->organization_id !== null) {
                        $query->where('organization_id', $user->organization_id)
                            ->orWhere(function ($query) use ($user): void {
                                $query->whereNull('organization_id')
                                    ->where('created_by_id', $user->id);
                            });

                        return;
                    }

                    $query->whereNull('organization_id')
                        ->where('created_by_id', $user->id);
                })
                ->withCount('questions')
                ->latest('id')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Test::class);

        return Inertia::render('Admin/Tests/Create');
    }

    public function store(StoreTestRequest $request): RedirectResponse
    {
        Gate::authorize('create', Test::class);

        $test = Test::create([
            ...$request->validated(),
            'organization_id' => $request->user()->organization_id,
            'created_by_id' => $request->user()->id,
            'public_token' => Test::newPublicToken(),
            'status' => TestStatus::Draft->value,
        ]);

        return to_route('admin.tests.show', $test)
            ->with('success', 'Test created successfully.');
    }

    public function show(Test $test): Response
    {
        Gate::authorize('view', $test);

        return Inertia::render('Admin/Tests/Show', [
            'test' => $this->testPayload($test->loadCount('questions')),
        ]);
    }

    public function edit(Test $test): Response
    {
        Gate::authorize('update', $test);

        return Inertia::render('Admin/Tests/Edit', [
            'test' => $this->testPayload($test),
        ]);
    }

    public function update(UpdateTestRequest $request, Test $test): RedirectResponse
    {
        Gate::authorize('update', $test);

        $test->update($request->validated());

        return to_route('admin.tests.show', $test)
            ->with('success', 'Test updated successfully.');
    }

    public function destroy(Test $test, DeleteTestWithArtifacts $deleteTest): RedirectResponse
    {
        Gate::authorize('delete', $test);

        $deleteTest->handle($test);

        return to_route('admin.tests.index')
            ->with('success', 'Test deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function testPayload(Test $test): array
    {
        return [
            'id' => $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'duration_minutes' => $test->duration_minutes,
            'pass_mark' => $test->pass_mark,
            'starts_at' => $test->starts_at?->toISOString(),
            'status' => $test->status,
            'questions_count' => $test->questions_count ?? null,
            'public_token' => $test->public_token,
            'public_url' => $test->public_token
                ? route('candidate.public-tests.policy', $test->public_token)
                : null,
            'public_access_enabled' => $test->public_access_enabled,
            'candidate_fields' => $test->candidateRegistrationFields(),
            'policy_text' => $test->policyText(),
        ];
    }
}
