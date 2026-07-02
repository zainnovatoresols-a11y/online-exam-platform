<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Analytics\BuildOrganizationAnalytics;
use App\Enums\TestStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationAnalyticsController extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        BuildOrganizationAnalytics $buildAnalytics,
    ): Response {
        Gate::authorize('view', $organization);

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'test_status' => ['nullable', Rule::enum(TestStatus::class)],
            'review_status' => ['nullable', Rule::in(['needs_review', 'approved', 'flagged', 'rejected'])],
        ]);

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
