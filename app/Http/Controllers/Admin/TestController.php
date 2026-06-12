<?php

namespace App\Http\Controllers\Admin;

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
            'status' => TestStatus::Draft->value,
        ]);

        return to_route('admin.tests.show', $test)
            ->with('success', 'Test created successfully.');
    }

    public function show(Test $test): Response
    {
        Gate::authorize('view', $test);

        return Inertia::render('Admin/Tests/Show', [
            'test' => $test->loadCount('questions'),
        ]);
    }

    public function edit(Test $test): Response
    {
        Gate::authorize('update', $test);

        return Inertia::render('Admin/Tests/Edit', [
            'test' => $test,
        ]);
    }

    public function update(UpdateTestRequest $request, Test $test): RedirectResponse
    {
        Gate::authorize('update', $test);

        $test->update($request->validated());

        return to_route('admin.tests.show', $test)
            ->with('success', 'Test updated successfully.');
    }

    public function destroy(Test $test): RedirectResponse
    {
        Gate::authorize('delete', $test);

        $test->delete();

        return to_route('admin.tests.index')
            ->with('success', 'Test deleted successfully.');
    }
}
