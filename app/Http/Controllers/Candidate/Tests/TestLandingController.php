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

        return Inertia::render('Candidate/Tests/Show', [
            'test' => $test->load(['organization:id,name', 'creator:id,name,email'])
                ->loadCount('questions'),
        ]);
    }
}
