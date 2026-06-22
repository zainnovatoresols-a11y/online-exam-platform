<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the super admin dashboard.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $managedOrganization = null;

        if ($user->organization_id !== null) {
            $organization = Organization::query()
                ->withCount([
                    'users as admins_count' => fn ($query) => $query->adminAccounts(),
                    'tests',
                ])
                ->find($user->organization_id);

            if ($organization) {
                $managedOrganization = [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'admins_count' => $organization->admins_count,
                    'tests_count' => $organization->tests_count,
                ];
            }
        }

        return Inertia::render('SuperAdmin/Dashboard', [
            'managedOrganization' => $managedOrganization,
            'canCreateOrganizations' => $user->isPlatformSuperAdmin(),
        ]);
    }
}
