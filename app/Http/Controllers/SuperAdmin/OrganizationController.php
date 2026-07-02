<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreOrganizationRequest;
use App\Http\Requests\SuperAdmin\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Organization::class);

        $user = $request->user();

        $organizations = Organization::query()
            ->when($user->isOrganizationSuperAdmin(), fn ($query) => $query->whereKey($user->organization_id))
            ->withCount([
                'users as admins_count' => fn ($query) => $query->adminAccounts(),
                'tests',
            ])
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Organizations/Index', [
            'organizations' => $organizations,
            'can_create_organizations' => $user->isPlatformSuperAdmin(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Organization::class);

        return Inertia::render('SuperAdmin/Organizations/Create');
    }

    public function store(StoreOrganizationRequest $request)
    {
        Gate::authorize('create', Organization::class);

        $organization = Organization::create($request->validated());

        return to_route('super-admin.organizations.show', $organization)
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        return Inertia::render('SuperAdmin/Organizations/Show', [
            'organization' => $organization->loadCount('tests'),
            'admins' => User::query()
                ->adminAccounts()
                ->where('organization_id', $organization->id)
                ->latest('id')
                ->get(['id', 'name', 'email', 'created_at']),
            'tests' => $organization->tests()
                ->with(['creator:id,name,email'])
                ->withCount(['questions', 'attempts', 'invitations'])
                ->latest('id')
                ->get([
                    'id',
                    'organization_id',
                    'created_by_id',
                    'title',
                    'status',
                    'published_at',
                    'closed_at',
                    'created_at',
                ])
                ->map(fn (Test $test): array => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'status' => $test->status,
                    'creator' => $test->creator ? [
                        'id' => $test->creator->id,
                        'name' => $test->creator->name,
                        'email' => $test->creator->email,
                    ] : null,
                    'questions_count' => $test->questions_count,
                    'attempts_count' => $test->attempts_count,
                    'invitations_count' => $test->invitations_count,
                    'published_at' => $test->published_at?->toISOString(),
                    'closed_at' => $test->closed_at?->toISOString(),
                    'created_at' => $test->created_at?->toISOString(),
                    'results_url' => route('admin.tests.results.index', $test),
                    'analytics_url' => route('admin.tests.results.analytics', $test),
                ])
                ->values(),
        ]);
    }

    public function edit(Organization $organization): Response
    {
        Gate::authorize('update', $organization);

        return Inertia::render('SuperAdmin/Organizations/Edit', [
            'organization' => $organization,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        Gate::authorize('update', $organization);

        $organization->update($request->validated());

        return to_route('super-admin.organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }
}
