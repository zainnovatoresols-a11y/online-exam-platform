<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProctoringRecordingChunk;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProctoringRecordingChunkController extends Controller
{
    public function show(ProctoringRecordingChunk $chunk): StreamedResponse
    {
        $chunk->load('attempt.test');

        Gate::authorize('view', $chunk->attempt->test);

        abort_unless(Storage::disk($chunk->disk)->exists($chunk->path), 404);

        return Storage::disk($chunk->disk)->response(
            $chunk->path,
            basename($chunk->path),
            [
                'Content-Type' => $chunk->mime_type ?: 'video/webm',
                'Content-Disposition' => 'inline; filename="'.basename($chunk->path).'"',
            ],
        );
    }
}
