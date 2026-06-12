<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TestStatus;
use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TestLifecycleController extends Controller
{
    public function publish(Test $test): RedirectResponse
    {
        Gate::authorize('publish', $test);

        $test->update([
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);

        return to_route('admin.tests.show', $test)
            ->with('success', 'Test published successfully.');
    }

    public function close(Test $test): RedirectResponse
    {
        Gate::authorize('close', $test);

        $test->update([
            'status' => TestStatus::Closed->value,
            'closed_at' => now(),
        ]);

        return to_route('admin.tests.show', $test)
            ->with('success', 'Test closed successfully.');
    }
}
