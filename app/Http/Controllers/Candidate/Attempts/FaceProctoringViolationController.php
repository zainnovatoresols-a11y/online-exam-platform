<?php

namespace App\Http\Controllers\Candidate\Attempts;

use App\Actions\Attempts\StoreFaceProctoringViolation;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Attempts\StoreFaceProctoringViolationRequest;
use App\Http\Requests\Candidate\Attempts\UpdateFaceProctoringDurationRequest;
use App\Models\Invitation;
use App\Models\ProctoringFaceSnapshot;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
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
                $request->validated('started_at'),
                $request->validated('ended_at'),
                $request->validated('duration_seconds'),
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

    public function updateDuration(
        UpdateFaceProctoringDurationRequest $request,
        TestAttempt $attempt,
        ProctoringFaceSnapshot $snapshot,
    ): JsonResponse {
        Gate::authorize('save', $attempt);

        abort_unless((int) $snapshot->test_attempt_id === (int) $attempt->id, 404);

        return $this->updateSnapshotDuration($request, $attempt, $snapshot);
    }

    public function updatePublicDuration(
        UpdateFaceProctoringDurationRequest $request,
        string $attemptToken,
        ProctoringFaceSnapshot $snapshot,
    ): JsonResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);
        abort_unless((int) $snapshot->test_attempt_id === (int) $attempt->id, 404);

        return $this->updateSnapshotDuration($request, $attempt, $snapshot);
    }

    private function updateSnapshotDuration(
        UpdateFaceProctoringDurationRequest $request,
        TestAttempt $attempt,
        ProctoringFaceSnapshot $snapshot,
    ): JsonResponse {
        if (! $attempt->isInProgress()) {
            return response()->json([
                'message' => 'Face monitoring duration can only be updated for an in-progress attempt.',
            ], 422);
        }

        if ($attempt->isExpired()) {
            return response()->json([
                'message' => 'Face monitoring duration can no longer be updated after the attempt has expired.',
            ], 422);
        }

        $metadata = $snapshot->metadata ?? [];
        $newMetadata = $request->validated('metadata', []);

        foreach ($newMetadata as $key => $value) {
            if (is_string($key)) {
                $metadata[$key] = $value;
            }
        }

        $endedAt = Carbon::parse($request->validated('ended_at'));
        $durationSeconds = max((int) $request->validated('duration_seconds'), 0);

        $snapshot->forceFill([
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'metadata' => $metadata,
        ])->save();

        $snapshot->event?->update([
            'metadata' => array_merge($snapshot->event->metadata ?? [], [
                'ended_at' => $endedAt->toISOString(),
                'duration_seconds' => $durationSeconds,
            ]),
        ]);

        return response()->json([
            'updated' => true,
            'snapshot_id' => $snapshot->id,
            'duration_seconds' => $snapshot->duration_seconds,
        ]);
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
