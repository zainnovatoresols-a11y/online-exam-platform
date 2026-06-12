<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Candidates\StoreCandidateRequest;
use App\Http\Requests\Admin\Candidates\UpdateCandidateRequest;
use App\Models\User;
use App\Queries\AdminCandidatePoolQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class CandidateController extends Controller
{
    public function index(Request $request, AdminCandidatePoolQuery $candidatePoolQuery): Response
    {
        Gate::authorize('viewAnyCandidate', User::class);

        $stack = trim((string) $request->query('stack', ''));
        $baseQuery = $candidatePoolQuery->query($request->user());

        $stacks = (clone $baseQuery)
            ->whereNotNull('stack_name')
            ->where('stack_name', '!=', '')
            ->distinct()
            ->orderBy('stack_name')
            ->pluck('stack_name')
            ->values();

        $candidates = $candidatePoolQuery->query($request->user())
            ->when($stack !== '', fn ($query) => $query->where('stack_name', $stack))
            ->latest('id')
            ->paginate(10)
            ->through(fn (User $candidate): array => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'stack_name' => $candidate->stack_name,
                'created_at' => $candidate->created_at?->toISOString(),
            ])
            ->withQueryString();

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $candidates,
            'stacks' => $stacks,
            'filters' => [
                'stack' => $stack !== '' ? $stack : null,
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('createCandidate', User::class);

        return Inertia::render('Admin/Candidates/Create');
    }

    public function store(StoreCandidateRequest $request): RedirectResponse
    {
        $admin = $request->user();
        $validated = $request->validated();

        $candidate = User::create([
            ...$validated,
            'organization_id' => $admin->organization_id,
            'created_by_id' => $admin->id,
            'email_verified_at' => now(),
        ]);

        Role::findOrCreate(UserRole::Candidate->value, 'web');
        $candidate->assignRole(UserRole::Candidate->value);

        return to_route('admin.candidates.index')
            ->with('success', 'Candidate added successfully.');
    }

    public function edit(User $candidate): Response
    {
        Gate::authorize('updateCandidate', $candidate);

        return Inertia::render('Admin/Candidates/Edit', [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'stack_name' => $candidate->stack_name,
            ],
        ]);
    }

    public function update(UpdateCandidateRequest $request, User $candidate): RedirectResponse
    {
        $validated = $request->validated();

        if (blank($validated['password'])) {
            unset($validated['password']);
        }

        $candidate->update($validated);

        return to_route('admin.candidates.index')
            ->with('success', 'Candidate updated successfully.');
    }

    public function destroy(User $candidate): RedirectResponse
    {
        Gate::authorize('deleteCandidate', $candidate);

        $candidate->delete();

        return to_route('admin.candidates.index')
            ->with('success', 'Candidate deleted successfully.');
    }
}
