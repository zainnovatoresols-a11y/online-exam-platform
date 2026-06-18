<?php

namespace App\Actions\Tests;

use App\Models\Test;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteTestWithArtifacts
{
    public function handle(Test $test): void
    {
        $attemptIds = $test->attempts()
            ->pluck('id');

        DB::transaction(function () use ($test): void {
            $test->delete();
        });

        $this->deleteProctoringAttemptDirectories($attemptIds);
    }

    /**
     * @param  Collection<int, int>  $attemptIds
     */
    private function deleteProctoringAttemptDirectories(Collection $attemptIds): void
    {
        $attemptIds
            ->unique()
            ->each(function (int $attemptId): void {
                $directory = "proctoring/attempts/{$attemptId}";

                try {
                    $disk = Storage::disk('local');
                    $files = $disk->allFiles($directory);

                    if ($files !== []) {
                        $disk->delete($files);
                    }

                    $disk->deleteDirectory($directory);
                } catch (Throwable $exception) {
                    Log::warning('Failed to delete proctoring artifacts for deleted test attempt.', [
                        'attempt_id' => $attemptId,
                        'directory' => $directory,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            });
    }
}
