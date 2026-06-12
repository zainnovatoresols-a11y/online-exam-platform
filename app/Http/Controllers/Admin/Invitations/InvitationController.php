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
            'canCreateInvitation' => Gate::allows('create', [Invitation::class, $test]),
            'invitations' => $test->invitations()
                ->with(['candidate:id,name,email'])
                ->latest('id')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(Test $test): Response
    {
        Gate::authorize('create', [Invitation::class, $test]);

        return Inertia::render('Admin/Invitations/Create', [
            'test' => $test->load(['organization:id,name', 'creator:id,name,email']),
        ]);
    }

    public function store(StoreInvitationRequest $request, Test $test, CreateInvitation $createInvitation): RedirectResponse
    {
        Gate::authorize('create', [Invitation::class, $test]);

        $createInvitation->handle($test, $request->user(), $request->validated());

        return to_route('admin.tests.invitations.index', $test)
            ->with('success', 'Invitation sent successfully.');
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
}
