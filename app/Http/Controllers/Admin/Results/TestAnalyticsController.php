<?php

namespace App\Http\Controllers\Admin\Results;

use App\Actions\Analytics\BuildTestResultAnalytics;
use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TestAnalyticsController extends Controller
{
    public function show(Request $request, Test $test, BuildTestResultAnalytics $buildAnalytics): Response
    {
        Gate::authorize('view', $test);

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(['in_progress', 'submitted', 'expired'])],
            'review_status' => ['nullable', Rule::in(['needs_review', 'approved', 'flagged', 'rejected'])],
        ]);

        $test->load(['organization:id,name', 'creator:id,name,email'])
            ->loadCount(['invitations', 'attempts']);

        return Inertia::render('Admin/Results/Analytics', array_merge(
            [
                'test' => $this->testPayload($test),
            ],
            $buildAnalytics->handle($test, $filters),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function testPayload(Test $test): array
    {
        return [
            'id' => $test->id,
            'title' => $test->title,
            'status' => $test->status,
            'duration_minutes' => $test->duration_minutes,
            'pass_mark' => $test->pass_mark,
            'starts_at' => $test->starts_at?->toISOString(),
            'organization' => $test->organization ? [
                'id' => $test->organization->id,
                'name' => $test->organization->name,
            ] : null,
            'creator' => $test->creator ? [
                'id' => $test->creator->id,
                'name' => $test->creator->name,
                'email' => $test->creator->email,
            ] : null,
            'invitations_count' => $test->invitations_count ?? null,
            'attempts_count' => $test->attempts_count ?? null,
        ];
    }
}
