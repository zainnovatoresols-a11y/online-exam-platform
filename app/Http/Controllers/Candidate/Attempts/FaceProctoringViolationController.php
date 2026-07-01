<?php

namespace App\Http\Controllers\Candidate\Attempts;

use App\Actions\Attempts\StoreFaceProctoringViolation;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Attempts\StoreFaceProctoringViolationRequest;
use App\Models\Invitation;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FaceProctoringViolationController extends Controller
{
    public function store(
        StoreFaceProctoringViolationRequest $request,
        TestAttempt $attempt,
        StoreFaceProctoringViolation $action,
    ): JsonResponse {
        Gate::authorize('save', $attempt);

        return $this->storeViolation($request, $attempt, $action);
    }

    public function storePublic(
        StoreFaceProctoringViolationRequest $request,
        string $attemptToken,
        StoreFaceProctoringViolation $action,
    ): JsonResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);

        return $this->storeViolation($request, $attempt, $action);
    }

    private function storeViolation(
        StoreFaceProctoringViolationRequest $request,
        TestAttempt $attempt,
        StoreFaceProctoringViolation $action,
    ): JsonResponse {
        try {
            $snapshot = $action->handle(
                $attempt,
                $request->validated('violation_type'),
                (int) $request->validated('face_count'),
                $request->file('snapshot'),
                $request->validated('captured_at'),
                $request->validated('metadata', []),
                $request,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status);
        }

        return response()->json([
            'stored' => true,
            'snapshot_id' => $snapshot->id,
            'event_id' => $snapshot->proctoring_event_id,
        ], 201);
    }

    private function attemptForToken(string $attemptToken): ?TestAttempt
    {
        $invitation = Invitation::query()
            ->with(['test', 'candidateDetail', 'attempt'])
            ->where('token', $attemptToken)
            ->first();

        if (! $invitation || $this->isBlocked($invitation) || ! $this->hasSubmittedDetails($invitation)) {
            return null;
        }

        return $invitation->attempt;
    }

    private function isBlocked(Invitation $invitation): bool
    {
        if (! $invitation->test->isPublished() || $invitation->isRevoked()) {
            return true;
        }

        if ($invitation->isAcceptable() && $invitation->hasExpired()) {
            $invitation->update(['status' => InvitationStatus::Expired]);
        }

        return $invitation->isExpired() || $invitation->hasExpired();
    }

    private function hasSubmittedDetails(Invitation $invitation): bool
    {
        return $invitation->isAccepted()
            && $invitation->policy_accepted_at !== null
            && $invitation->candidateDetail !== null;
    }
}
