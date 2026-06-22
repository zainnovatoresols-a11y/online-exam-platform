<?php

namespace App\Http\Controllers\Admin\Results;

use App\Actions\Results\BuildTestResultsCsvRows;
use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestResultExportController extends Controller
{
    public function csv(Test $test, BuildTestResultsCsvRows $rows): StreamedResponse
    {
        Gate::authorize('view', $test);

        $test->loadMissing(['organization:id,name']);

        $filename = sprintf(
            '%s-results-%s.csv',
            Str::slug($test->title) ?: 'test',
            now()->format('Ymd-His'),
        );

        return response()->streamDownload(function () use ($rows, $test): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $rows->headers());
            $rows->writeRows($test, $handle);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
