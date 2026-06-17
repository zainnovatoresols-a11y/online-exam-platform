<?php

namespace App\Http\Controllers\Candidate\Attempts;

use App\Actions\Attempts\RecordProctoringEvent;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Attempts\StoreProctoringEventRequest;
use App\Models\Invitation;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProctoringEventController extends Controller
{
    public function store(
        StoreProctoringEventRequest $request,
        TestAttempt $attempt,
        RecordProctoringEvent $recordProctoringEvent,
    ): JsonResponse {
        Gate::authorize('save', $attempt);

        return $this->record($request, $attempt, $recordProctoringEvent);
    }

    public function storePublic(
        StoreProctoringEventRequest $request,
        string $attemptToken,
        RecordProctoringEvent $recordProctoringEvent,
    ): JsonResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);

        return $this->record($request, $attempt, $recordProctoringEvent);
    }

    private function record(
        StoreProctoringEventRequest $request,
        TestAttempt $attempt,
        RecordProctoringEvent $recordProctoringEvent,
    ): JsonResponse {
        try {
            $result = $recordProctoringEvent->handle(
                $attempt,
                $request->validated('event_type'),
                $request->validated('occurred_at'),
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
            'recorded' => $result['recorded'],
            'duplicate' => $result['duplicate'],
        ], $result['recorded'] ? 201 : 200);
    }

    private function attemptForToken(string $attemptToken): ?TestAttempt
    {
        $invitation = Invitation::query()
            ->with([
                'test',
                'candidateDetail',
                'attempt',
            ])
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
            $invitation->update([
                'status' => InvitationStatus::Expired,
            ]);
        }

        if ($invitation->isExpired() || $invitation->hasExpired()) {
            return true;
        }

        $startsAt = $invitation->starts_at ?? $invitation->test->starts_at;

        return $this->hasSubmittedDetails($invitation)
            && $startsAt !== null
            && now()->lessThan($startsAt);
    }

    private function hasSubmittedDetails(Invitation $invitation): bool
    {
        return $invitation->isAccepted()
            && $invitation->policy_accepted_at !== null
            && $invitation->candidateDetail !== null;
    }
}
