<?php

namespace App\Http\Controllers\Candidate\Attempts;

use App\Actions\Attempts\RunVisibleCodingTestCases;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\Attempts\RunCodingQuestionRequest;
use App\Models\Invitation;
use App\Models\Question;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RunCodingQuestionController extends Controller
{
    public function store(
        RunCodingQuestionRequest $request,
        TestAttempt $attempt,
        RunVisibleCodingTestCases $runVisibleCodingTestCases,
    ): JsonResponse {
        Gate::authorize('save', $attempt);

        $validated = $request->validated();
        $question = Question::query()->findOrFail($validated['question_id']);

        return response()->json([
            'run' => $runVisibleCodingTestCases->handle(
                $attempt,
                $question,
                $validated['language'],
                $validated['submitted_code'],
            ),
        ]);
    }

    public function storePublic(
        RunCodingQuestionRequest $request,
        string $attemptToken,
        RunVisibleCodingTestCases $runVisibleCodingTestCases,
    ): JsonResponse {
        $attempt = $this->attemptForToken($attemptToken);
        abort_unless($attempt && $attempt->isInProgress(), 403);

        $validated = $request->validated();
        $question = Question::query()->findOrFail($validated['question_id']);

        return response()->json([
            'run' => $runVisibleCodingTestCases->handle(
                $attempt,
                $question,
                $validated['language'],
                $validated['submitted_code'],
            ),
        ]);
    }

    private function attemptForToken(string $attemptToken): ?TestAttempt
    {
        $invitation = Invitation::query()
            ->with(['test', 'candidateDetail', 'attempt'])
            ->where('token', $attemptToken)
            ->first();

        if (! $invitation || ! $invitation->test?->isPublished()) {
            return null;
        }

        if ($invitation->isRevoked() || $invitation->isExpired() || $invitation->hasExpired()) {
            return null;
        }

        return $invitation->status === InvitationStatus::Accepted
            && $invitation->policy_accepted_at !== null
            && $invitation->candidateDetail !== null
            ? $invitation->attempt
            : null;
    }
}
