<?php

namespace App\Http\Controllers\Candidate\Tests;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Test;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TestLandingController extends Controller
{
    public function __invoke(Test $test): Response
    {
        Gate::authorize('viewTest', [Invitation::class, $test]);

        $attempt = $test->attempts()
            ->where('candidate_user_id', request()->user()->id)
            ->first(['id', 'test_id', 'candidate_user_id', 'status', 'score', 'total_marks', 'submitted_at']);

        return Inertia::render('Candidate/Tests/Show', [
            'test' => $test->load(['organization:id,name', 'creator:id,name,email'])
                ->loadCount('questions'),
            'attempt' => $attempt ? [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'score' => $attempt->score,
                'total_marks' => $attempt->total_marks,
                'submitted_at' => $attempt->submitted_at?->toISOString(),
            ] : null,
        ]);
    }
}
