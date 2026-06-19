<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProctoringRecording;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProctoringRecordingController extends Controller
{
    public function showMerged(ProctoringRecording $recording): StreamedResponse
    {
        $recording->load('attempt.test');

        Gate::authorize('view', $recording->attempt->test);

        abort_unless($recording->merged_status === 'completed', 404);
        abort_unless($recording->merged_disk && $recording->merged_path, 404);
        abort_unless(Storage::disk($recording->merged_disk)->exists($recording->merged_path), 404);

        return Storage::disk($recording->merged_disk)->response(
            $recording->merged_path,
            basename($recording->merged_path),
            [
                'Content-Type' => 'video/webm',
                'Content-Disposition' => 'inline; filename="'.basename($recording->merged_path).'"',
            ],
        );
    }
}
