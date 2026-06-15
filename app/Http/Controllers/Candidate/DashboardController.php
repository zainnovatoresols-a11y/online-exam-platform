<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the candidate dashboard.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $invitations = Invitation::query()
            ->where('candidate_user_id', $user->id)
            ->where('email', $user->email)
            ->where('status', InvitationStatus::Accepted->value)
            ->with([
                'attempt:id,test_id,invitation_id,status,submitted_at',
                'test' => fn ($query) => $query
                    ->with(['organization:id,name', 'creator:id,name,email'])
                    ->withCount('questions'),
            ])
            ->latest('accepted_at')
            ->get()
            ->map(fn (Invitation $invitation): array => [
                'id' => $invitation->id,
                'starts_at' => ($invitation->starts_at ?? $invitation->test?->starts_at)?->toISOString(),
                'accepted_at' => $invitation->accepted_at?->toISOString(),
                'test' => $invitation->test ? [
                    'id' => $invitation->test->id,
                    'title' => $invitation->test->title,
                    'status' => $invitation->test->status,
                    'duration_minutes' => $invitation->test->duration_minutes,
                    'questions_count' => $invitation->test->questions_count,
                    'organization' => $invitation->test->organization ? [
                        'id' => $invitation->test->organization->id,
                        'name' => $invitation->test->organization->name,
                    ] : null,
                    'creator' => $invitation->test->creator ? [
                        'id' => $invitation->test->creator->id,
                        'name' => $invitation->test->creator->name,
                        'email' => $invitation->test->creator->email,
                    ] : null,
                ] : null,
                'attempt' => $invitation->attempt ? [
                    'id' => $invitation->attempt->id,
                    'status' => $invitation->attempt->status->value,
                    'submitted_at' => $invitation->attempt->submitted_at?->toISOString(),
                ] : null,
            ]);

        return Inertia::render('Candidate/Dashboard', [
            'invitations' => $invitations,
        ]);
    }
}
