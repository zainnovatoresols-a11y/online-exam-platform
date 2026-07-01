<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProctoringFaceSnapshot;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProctoringFaceSnapshotController extends Controller
{
    public function show(ProctoringFaceSnapshot $snapshot): StreamedResponse
    {
        $snapshot->load('attempt.test');

        Gate::authorize('view', $snapshot->attempt->test);

        abort_unless(Storage::disk($snapshot->disk)->exists($snapshot->path), 404);

        return Storage::disk($snapshot->disk)->response(
            $snapshot->path,
            basename($snapshot->path),
            [
                'Content-Type' => $snapshot->mime_type ?: 'image/jpeg',
                'Content-Disposition' => 'inline; filename="'.basename($snapshot->path).'"',
            ],
        );
    }
}
