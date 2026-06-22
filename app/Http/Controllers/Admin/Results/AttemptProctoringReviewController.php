<?php

namespace App\Http\Controllers\Admin\Results;

use App\Actions\Results\UpdateAttemptProctoringReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Results\UpdateProctoringReviewRequest;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AttemptProctoringReviewController extends Controller
{
    public function update(
        UpdateProctoringReviewRequest $request,
        Test $test,
        TestAttempt $attempt,
        UpdateAttemptProctoringReview $action,
    ): RedirectResponse {
        Gate::authorize('view', $test);
        abort_unless((int) $attempt->test_id === (int) $test->id, 404);

        $action->handle($test, $attempt, $request->user(), $request->validated());

        return back()->with('success', 'Proctoring review saved successfully.');
    }
}
