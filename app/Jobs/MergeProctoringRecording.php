<?php

namespace App\Jobs;

use App\Models\ProctoringRecording;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class MergeProctoringRecording implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(public int $recordingId) {}

    public function handle(): void
    {
        $recording = ProctoringRecording::query()
            ->with(['chunks' => fn ($query) => $query->orderBy('sequence')->orderBy('id')])
            ->find($this->recordingId);

        if (! $recording) {
            return;
        }

        if ($recording->chunks->isEmpty()) {
            $this->markFailed($recording, 'No recording chunks were found to merge.');

            return;
        }

        $recording->forceFill([
            'merged_status' => 'processing',
            'merge_error' => null,
        ])->save();

        $disk = Storage::disk('local');
        $missingChunk = $recording->chunks->first(
            fn ($chunk): bool => ! $disk->exists($chunk->path),
        );

        if ($missingChunk) {
            $this->markFailed($recording, "Recording chunk #{$missingChunk->sequence} is missing from storage.");

            return;
        }

        $directory = "proctoring/attempts/{$recording->test_attempt_id}/recordings/{$recording->recording_type}";
        $listPath = "{$directory}/merge_{$recording->id}.txt";
        $outputPath = "{$directory}/merged_{$recording->recording_type}.webm";

        if ($disk->exists($outputPath)) {
            $disk->delete($outputPath);
        }

        $disk->put($listPath, $this->concatFileContents($recording));

        try {
            $this->runFfmpegMerge($disk->path($listPath), $disk->path($outputPath));

            clearstatcache(true, $disk->path($outputPath));

            if (! $disk->exists($outputPath) || filesize($disk->path($outputPath)) <= 0) {
                throw new RuntimeException('FFmpeg finished but did not create a valid merged video.');
            }

            $recording->forceFill([
                'merged_disk' => 'local',
                'merged_path' => $outputPath,
                'merged_status' => 'completed',
                'merged_at' => now(),
                'merged_size_bytes' => filesize($disk->path($outputPath)),
                'merge_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $this->markFailed($recording, $exception->getMessage());

            throw $exception;
        } finally {
            if ($disk->exists($listPath)) {
                $disk->delete($listPath);
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        $recording = ProctoringRecording::query()->find($this->recordingId);

        if ($recording) {
            $this->markFailed($recording, $exception->getMessage());
        }
    }

    private function concatFileContents(ProctoringRecording $recording): string
    {
        $disk = Storage::disk('local');

        return $recording->chunks
            ->map(fn ($chunk): string => "file '".$this->escapeConcatPath($disk->path($chunk->path))."'")
            ->implode(PHP_EOL).PHP_EOL;
    }

    private function escapeConcatPath(string $path): string
    {
        return str_replace("'", "'\\''", str_replace('\\', '/', $path));
    }

    private function runFfmpegMerge(string $listPath, string $outputPath): void
    {
        $copyProcess = $this->process([
            $this->ffmpegBinary(),
            '-y',
            '-f',
            'concat',
            '-safe',
            '0',
            '-i',
            $listPath,
            '-c',
            'copy',
            $outputPath,
        ]);

        $copyProcess->run();

        if ($copyProcess->isSuccessful()) {
            return;
        }

        $fallbackProcess = $this->process([
            $this->ffmpegBinary(),
            '-y',
            '-f',
            'concat',
            '-safe',
            '0',
            '-i',
            $listPath,
            '-c:v',
            'libvpx',
            '-b:v',
            '1500k',
            '-an',
            $outputPath,
        ]);

        $fallbackProcess->run();

        if (! $fallbackProcess->isSuccessful()) {
            throw new RuntimeException(trim(
                'FFmpeg merge failed. Copy output: '.
                $copyProcess->getErrorOutput().
                ' Fallback output: '.
                $fallbackProcess->getErrorOutput(),
            ));
        }
    }

    /**
     * @param  list<string>  $command
     */
    private function process(array $command): Process
    {
        return (new Process($command))->setTimeout((int) config('proctoring.ffmpeg_timeout', 600));
    }

    private function ffmpegBinary(): string
    {
        $configured = (string) config('proctoring.ffmpeg_binary', 'ffmpeg');

        if ($this->isLocalBinaryPath($configured)) {
            return $configured;
        }

        foreach ([
            'E:/ffmpeg/bin/ffmpeg.exe',
            'E:/ffmpeg/bin/ffmpeg',
            'C:/ffmpeg/bin/ffmpeg.exe',
            'C:/ffmpeg/bin/ffmpeg',
        ] as $candidate) {
            if ($this->isLocalBinaryPath($candidate)) {
                return $candidate;
            }
        }

        return $configured;
    }

    private function isLocalBinaryPath(string $path): bool
    {
        if (! str_contains($path, '/') && ! str_contains($path, '\\')) {
            return false;
        }

        return is_file($path);
    }

    private function markFailed(ProctoringRecording $recording, string $message): void
    {
        $recording->forceFill([
            'merged_status' => 'failed',
            'merge_error' => Str::limit($message, 2000, ''),
        ])->save();
    }
}
