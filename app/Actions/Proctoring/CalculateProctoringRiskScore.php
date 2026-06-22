<?php

namespace App\Actions\Proctoring;

use App\Models\ProctoringEvent;
use App\Models\TestAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CalculateProctoringRiskScore
{
    /**
     * @var array<string, int>
     */
    private const EVENT_WEIGHTS = [
        'tab_hidden' => 3,
        'window_blur' => 2,
        'fullscreen_exited' => 8,
        'copy_attempt' => 10,
        'paste_attempt' => 10,
        'cut_attempt' => 10,
        'right_click_attempt' => 4,
        'shortcut_attempt' => 5,
        'drag_attempt' => 3,
        'drop_attempt' => 3,
        'camera_recording_permission_denied' => 15,
        'screen_recording_permission_denied' => 20,
        'camera_recording_error' => 8,
        'screen_recording_error' => 10,
        'camera_recording_stopped' => 10,
        'screen_recording_stopped' => 12,
        'screen_share_ended' => 20,
        'camera_recording_chunk_failed' => 4,
        'screen_recording_chunk_failed' => 5,
    ];

    /**
     * @var array<string, int>
     */
    private const SEVERITY_WEIGHTS = [
        'low' => 1,
        'medium' => 4,
        'high' => 10,
    ];

    /**
     * @param  TestAttempt|Collection<int, ProctoringEvent>  $source
     * @return array{score: int, level: string, event_count: int, breakdown: list<array{event_type: string, label: string, count: int, points_each: int, points: int}>}
     */
    public function handle(TestAttempt|Collection $source): array
    {
        $events = $source instanceof TestAttempt
            ? $this->eventsForAttempt($source)
            : $source;

        $breakdown = $events
            ->map(fn (ProctoringEvent $event): array => [
                'event_type' => $event->event_type,
                'label' => $this->label($event->event_type),
                'points_each' => $this->pointsFor($event),
            ])
            ->groupBy(fn (array $event): string => $event['event_type'].'|'.$event['points_each'])
            ->map(function (Collection $group): array {
                $first = $group->first();
                $count = $group->count();
                $pointsEach = (int) $first['points_each'];

                return [
                    'event_type' => $first['event_type'],
                    'label' => $first['label'],
                    'count' => $count,
                    'points_each' => $pointsEach,
                    'points' => $count * $pointsEach,
                ];
            })
            ->sortByDesc('points')
            ->values();

        $score = (int) $breakdown->sum('points');

        return [
            'score' => $score,
            'level' => $this->levelFor($score),
            'event_count' => $events->count(),
            'breakdown' => $breakdown->all(),
        ];
    }

    /**
     * @param  TestAttempt|Collection<int, ProctoringEvent>  $source
     * @return array{score: int, level: string, event_count: int, breakdown: list<array{event_type: string, label: string, count: int, points_each: int, points: int}>}
     */
    public function __invoke(TestAttempt|Collection $source): array
    {
        return $this->handle($source);
    }

    /**
     * @return Collection<int, ProctoringEvent>
     */
    private function eventsForAttempt(TestAttempt $attempt): Collection
    {
        $attempt->loadMissing('proctoringEvents:id,test_attempt_id,event_type,severity');

        return $attempt->proctoringEvents;
    }

    private function pointsFor(ProctoringEvent $event): int
    {
        return self::EVENT_WEIGHTS[$event->event_type]
            ?? self::SEVERITY_WEIGHTS[$event->severity]
            ?? 0;
    }

    private function levelFor(int $score): string
    {
        return match (true) {
            $score >= 60 => 'critical',
            $score >= 30 => 'high',
            $score >= 10 => 'medium',
            default => 'low',
        };
    }

    private function label(string $eventType): string
    {
        return Str::of($eventType)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
