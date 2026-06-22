<?php

namespace App\Http\Controllers\Admin\Results;

use App\Actions\Results\BuildAttemptResultExportData;
use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AttemptResultExportController extends Controller
{
    public function pdf(Test $test, TestAttempt $attempt, BuildAttemptResultExportData $buildExportData): Response
    {
        Gate::authorize('view', $test);
        abort_unless((int) $attempt->test_id === (int) $test->id, 404);

        $data = $buildExportData($test, $attempt);
        $candidateName = $data['candidate']['name'] ?? 'candidate';
        $filename = sprintf(
            '%s-%s-result.pdf',
            Str::slug($test->title) ?: 'test',
            Str::slug((string) $candidateName) ?: 'candidate',
        );

        return Pdf::loadView('pdf.admin.attempt-result', $data)
            ->setPaper('a4')
            ->download($filename);
    }
}
