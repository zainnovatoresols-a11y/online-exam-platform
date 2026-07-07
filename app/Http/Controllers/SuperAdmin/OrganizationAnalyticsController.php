<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Analytics\BuildOrganizationAnalytics;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\OrganizationAnalyticsFilterRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationAnalyticsController extends Controller
{
    public function __invoke(
        OrganizationAnalyticsFilterRequest $request,
        Organization $organization,
        BuildOrganizationAnalytics $buildAnalytics,
    ): Response {
        Gate::authorize('view', $organization);

        $filters = $request->validated();

        return Inertia::render('SuperAdmin/Organizations/Analytics', array_merge(
            [
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ],
            ],
            $buildAnalytics->handle($organization, $filters),
        ));
    }
}
