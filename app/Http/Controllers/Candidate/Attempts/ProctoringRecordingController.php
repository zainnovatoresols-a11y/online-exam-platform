<?php

namespace App\Http\Controllers\Candidate\Attempts;

use App\Actions\Attempts\StartProctoringRecording;
use App\Actions\Attempts\StopProctoringRecording;
use App\Actions\Attempts\StoreProctoringRecordingChunk;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Attempts\StartProctoringRecordingRequest;
use App\Http\Requests\Candidate\Attempts\StopProctoringRecordingRequest;
use App\Http\Requests\Candidate\Attempts\StoreProctoringRecordingChunkRequest;
use App\Models\Invitation;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProctoringRecordingController extends Controller
{
    public function start(
        StartProctoringRecordingRequest $request,
        TestAttempt $attempt,
        StartProctoringRecording $action,
    ): JsonResponse {
        Gate::authorize('save', $attempt);

        return $this->startRecording($request, $attempt, $action);
    }

    public function storeChunk(
        StoreProctoringRecordingChunkRequest $request,
        TestAttempt $attempt,
        StoreProctoringRecordingChunk $action,
    ): JsonResponse {
        Gate::authorize('save', $attempt);

        return $this->storeRecordingChunk($request, $attempt, $action);
    }

    public function stop(
        StopProctoringRecordingRequest $request,
        TestAttempt $attempt,
        StopProctoringRecording $action,
    ): JsonResponse {
        Gate::authorize('view', $attempt);

        return $this->stopRecording($request, $attempt, $action);
    }

    public function startPublic(
        StartProctoringRecordingRequest $request,
        string $attemptToken,
        StartProctoringRecording $action,
    ): JsonResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);

        return $this->startRecording($request, $attempt, $action);
    }

    public function storePublicChunk(
        StoreProctoringRecordingChunkRequest $request,
        string $attemptToken,
        StoreProctoringRecordingChunk $action,
    ): JsonResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt && $attempt->isInProgress(), 403);

        return $this->storeRecordingChunk($request, $attempt, $action);
    }

    public function stopPublic(
        StopProctoringRecordingRequest $request,
        string $attemptToken,
        StopProctoringRecording $action,
    ): JsonResponse {
        $attempt = $this->attemptForToken($attemptToken);

        abort_unless($attempt, 403);

        return $this->stopRecording($request, $attempt, $action);
    }

    private function startRecording(
        StartProctoringRecordingRequest $request,
        TestAttempt $attempt,
        StartProctoringRecording $action,
    ): JsonResponse {
        try {
            $recording = $action->handle(
                $attempt,
                $request->validated('recording_type'),
                $request->validated('mime_type'),
                $request->validated('metadata', []),
                $request,
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json([
            'started' => true,
            'recording_id' => $recording->id,
            'status' => $recording->status,
        ], 201);
    }

    private function storeRecordingChunk(
        StoreProctoringRecordingChunkRequest $request,
        TestAttempt $attempt,
        StoreProctoringRecordingChunk $action,
    ): JsonResponse {
        try {
            $chunk = $action->handle(
                $attempt,
                $request->validated('recording_type'),
                $request->file('chunk'),
                (int) $request->validated('sequence'),
                $request->validated('duration_ms'),
                $request->validated('recorded_at'),
                $request->validated('mime_type'),
                $request->validated('metadata', []),
                $request,
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json([
            'stored' => true,
            'chunk_id' => $chunk->id,
        ], 201);
    }

    private function stopRecording(
        StopProctoringRecordingRequest $request,
        TestAttempt $attempt,
        StopProctoringRecording $action,
    ): JsonResponse {
        try {
            $recording = $action->handle(
                $attempt,
                $request->validated('recording_type'),
                $request->validated('reason'),
                $request->validated('metadata', []),
                $request,
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json([
            'stopped' => true,
            'recording_id' => $recording->id,
            'status' => $recording->status,
        ]);
    }

    private function validationError(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => $exception->errors(),
        ], $exception->status);
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
