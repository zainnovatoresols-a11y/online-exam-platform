<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreOrganizationAdminRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class OrganizationAdminController extends Controller
{
    public function create(Organization $organization): Response
    {
        Gate::authorize('createAdmin', $organization);

        return Inertia::render('SuperAdmin/OrganizationAdmins/Create', [
            'organization' => $organization,
        ]);
    }

    public function store(StoreOrganizationAdminRequest $request, Organization $organization)
    {
        Gate::authorize('createAdmin', $organization);

        $validated = $request->validated();

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'organization_id' => $organization->id,
            'created_by_id' => $request->user()->id,
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
        ]);

        Role::findOrCreate(UserRole::Admin->value, 'web');
        $admin->assignRole(UserRole::Admin->value);

        return to_route('super-admin.organizations.show', $organization)
            ->with('success', 'Organization admin created successfully.');
    }
}
