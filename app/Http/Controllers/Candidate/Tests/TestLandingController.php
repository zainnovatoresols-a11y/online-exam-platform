<?php

namespace App\Http\Controllers\Candidate\Tests;

use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
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

        $invitation = Invitation::query()
            ->where('test_id', $test->id)
            ->where('candidate_user_id', request()->user()->id)
            ->where('email', request()->user()->email)
            ->where('status', InvitationStatus::Accepted->value)
            ->firstOrFail();

        $attempt = $invitation->attempt()
            ->first([
                'id',
                'test_id',
                'candidate_user_id',
                'status',
                'submitted_at',
                'expires_at',
            ]);

        return Inertia::render('Candidate/Tests/Show', [
            'test' => $test->load(['organization:id,name', 'creator:id,name,email'])
                ->loadCount([
                    'questions as questions_count' => fn ($query) => $query->where('type', QuestionType::Mcq->value),
                ]),
            'invitation' => [
                'id' => $invitation->id,
                'starts_at' => ($invitation->starts_at ?? $test->starts_at)?->toISOString(),
            ],
            'server_now' => now()->toISOString(),
            'attempt' => $attempt ? [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'submitted_at' => $attempt->submitted_at?->toISOString(),
                'expires_at' => $attempt->expires_at?->toISOString(),
            ] : null,
        ]);
    }
}
