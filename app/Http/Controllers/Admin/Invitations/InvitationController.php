<?php

namespace App\Http\Controllers\Admin\Invitations;

use App\Actions\Invitations\CreateInvitation;
use App\Actions\Invitations\ResendInvitation;
use App\Actions\Invitations\RevokeInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Invitations\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(Test $test): Response
    {
        Gate::authorize('viewAny', [Invitation::class, $test]);

        return Inertia::render('Admin/Invitations/Index', [
            'test' => $test->load(['organization:id,name', 'creator:id,name,email']),
            'public_url' => $this->publicUrl($test),
            'canCreateInvitation' => Gate::allows('create', [Invitation::class, $test]),
            'invitations' => $test->invitations()
                ->with(['candidate:id,name,email', 'candidateDetail'])
                ->whereNotNull('email')
                ->latest('id')
                ->paginate(10)
                ->through(fn (Invitation $invitation): array => [
                    'id' => $invitation->id,
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'status' => $invitation->status->value,
                    'starts_at' => $invitation->starts_at?->toISOString(),
                    'expires_at' => $invitation->expires_at?->toISOString(),
                    'accepted_at' => $invitation->accepted_at?->toISOString(),
                    'candidate' => $invitation->candidate ? [
                        'id' => $invitation->candidate->id,
                        'name' => $invitation->candidate->name,
                        'email' => $invitation->candidate->email,
                    ] : ($invitation->candidateDetail ? [
                        'id' => null,
                        'name' => $invitation->candidateDetail->name,
                        'email' => $invitation->candidateDetail->email,
                    ] : null),
                    'candidate_detail' => $invitation->candidateDetail ? [
                        'id' => $invitation->candidateDetail->id,
                        'name' => $invitation->candidateDetail->name,
                        'email' => $invitation->candidateDetail->email,
                        'phone' => $invitation->candidateDetail->phone,
                        'stack_name' => $invitation->candidateDetail->stack_name,
                    ] : null,
                ])
                ->withQueryString(),
        ]);
    }

    public function create(Test $test): Response
    {
        Gate::authorize('create', [Invitation::class, $test]);

        return Inertia::render('Admin/Invitations/Create', [
            'test' => $test->load(['organization:id,name', 'creator:id,name,email']),
            'public_url' => $this->publicUrl($test),
        ]);
    }

    public function store(
        StoreInvitationRequest $request,
        Test $test,
        CreateInvitation $createInvitation,
    ): RedirectResponse {
        Gate::authorize('create', [Invitation::class, $test]);

        $validated = $request->validated();
        $urlRoot = $request->root();
        $queued = 0;

        collect($request->bulkEmails())
            ->each(function (string $email) use ($createInvitation, $test, $request, $validated, $urlRoot, &$queued): void {
                $createInvitation->handle($test, $request->user(), [
                    'name' => null,
                    'email' => $email,
                    'starts_at' => $validated['starts_at'],
                    'expires_at' => $validated['expires_at'] ?? null,
                    'url_root' => $urlRoot,
                ]);

                $queued++;
            });

        if (filled($validated['email'] ?? null)) {
            $createInvitation->handle($test, $request->user(), [
                ...$validated,
                'url_root' => $urlRoot,
            ]);

            $queued++;
        }

        return to_route('admin.tests.invitations.index', $test)
            ->with('success', $queued.' invitation(s) queued successfully. Share the public URL with allowed candidates.');
    }

    public function resend(Test $test, Invitation $invitation, ResendInvitation $resendInvitation): RedirectResponse
    {
        $this->ensureInvitationBelongsToTest($test, $invitation);
        Gate::authorize('resend', $invitation);

        $resendInvitation->handle($invitation);

        return to_route('admin.tests.invitations.index', $test)
            ->with('success', 'Invitation resent successfully.');
    }

    public function revoke(Test $test, Invitation $invitation, RevokeInvitation $revokeInvitation): RedirectResponse
    {
        $this->ensureInvitationBelongsToTest($test, $invitation);
        Gate::authorize('revoke', $invitation);

        $revokeInvitation->handle($invitation);

        return to_route('admin.tests.invitations.index', $test)
            ->with('success', 'Invitation revoked successfully.');
    }

    private function ensureInvitationBelongsToTest(Test $test, Invitation $invitation): void
    {
        abort_unless($invitation->test_id === $test->id, 404);
    }

    private function publicUrl(Test $test): ?string
    {
        return $test->public_token
            ? route('candidate.public-tests.policy', $test->public_token)
            : null;
    }
}
