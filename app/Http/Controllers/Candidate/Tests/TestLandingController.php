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
            ->first([
                'id',
                'test_id',
                'candidate_user_id',
                'status',
                'score',
                'max_score',
                'total_marks',
                'percentage',
                'passed',
                'submitted_at',
                'expires_at',
            ]);

        return Inertia::render('Candidate/Tests/Show', [
            'test' => $test->load(['organization:id,name', 'creator:id,name,email'])
                ->loadCount('questions'),
            'server_now' => now()->toISOString(),
            'attempt' => $attempt ? [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'total_marks' => $attempt->total_marks,
                'percentage' => $attempt->percentage,
                'passed' => $attempt->passed,
                'submitted_at' => $attempt->submitted_at?->toISOString(),
                'expires_at' => $attempt->expires_at?->toISOString(),
            ] : null,
        ]);
    }
}
