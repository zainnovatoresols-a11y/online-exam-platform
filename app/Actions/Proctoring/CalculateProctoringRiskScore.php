<?php

namespace App\Actions\Proctoring;

use App\Models\ProctoringEvent;
use App\Models\TestAttempt;
use Illuminate\Support\Carbon;
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
     * These events are useful timeline evidence, but they prove normal
     * proctoring activity rather than suspicious behavior.
     *
     * @var array<int, string>
     */
    private const ZERO_RISK_EVENT_TYPES = [
        'tab_visible',
        'window_focus',
        'fullscreen_entered',
        'proctoring_violation_acknowledged',
        'camera_recording_permission_granted',
        'camera_recording_started',
        'camera_recording_chunk_uploaded',
        'screen_recording_permission_granted',
        'screen_recording_started',
        'screen_recording_chunk_uploaded',
    ];

    /**
     * Normal page lifecycle stops should remain in the evidence timeline but
     * should not make the attempt look suspicious.
     *
     * @var array<int, string>
     */
    private const BENIGN_RECORDING_STOP_REASONS = [
        'attempt_submitted',
        'attempt_completed',
        'attempt_inactive',
        'component_unmounted',
    ];

    /**
     * Browser camera/screen permission dialogs can briefly blur the window or
     * interrupt fullscreen. Those events are not candidate cheating.
     *
     * @var array<int, string>
     */
    private const MEDIA_PERMISSION_EVENT_TYPES = [
        'camera_recording_permission_granted',
        'camera_recording_permission_denied',
        'screen_recording_permission_granted',
        'screen_recording_permission_denied',
    ];

    /**
     * @var array<int, string>
     */
    private const MEDIA_PERMISSION_SIDE_EFFECT_TYPES = [
        'tab_hidden',
        'window_blur',
        'fullscreen_exited',
    ];

    private const MEDIA_PERMISSION_GRACE_SECONDS = 10;

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

        $scoreableEvents = $this->scoreableEvents($events);

        $breakdown = $scoreableEvents
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
            'event_count' => $scoreableEvents->count(),
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
        $attempt->loadMissing('proctoringEvents:id,test_attempt_id,event_type,severity,metadata,occurred_at');

        return $attempt->proctoringEvents;
    }

    /**
     * @param  Collection<int, ProctoringEvent>  $events
     * @return Collection<int, ProctoringEvent>
     */
    private function scoreableEvents(Collection $events): Collection
    {
        return $events
            ->reject(fn (ProctoringEvent $event): bool => $this->isZeroRiskEvent($event, $events))
            ->filter(fn (ProctoringEvent $event): bool => $this->pointsFor($event) > 0)
            ->values();
    }

    /**
     * @param  Collection<int, ProctoringEvent>  $events
     */
    private function isZeroRiskEvent(ProctoringEvent $event, Collection $events): bool
    {
        if (in_array($event->event_type, self::ZERO_RISK_EVENT_TYPES, true)) {
            return true;
        }

        if ($this->isBenignRecordingStop($event)) {
            return true;
        }

        return $this->isMediaPermissionSideEffect($event, $events);
    }

    private function isBenignRecordingStop(ProctoringEvent $event): bool
    {
        if (! in_array($event->event_type, ['camera_recording_stopped', 'screen_recording_stopped'], true)) {
            return false;
        }

        $reason = $event->metadata['reason'] ?? null;

        return is_string($reason)
            && in_array($reason, self::BENIGN_RECORDING_STOP_REASONS, true);
    }

    /**
     * @param  Collection<int, ProctoringEvent>  $events
     */
    private function isMediaPermissionSideEffect(ProctoringEvent $event, Collection $events): bool
    {
        if (! in_array($event->event_type, self::MEDIA_PERMISSION_SIDE_EFFECT_TYPES, true)) {
            return false;
        }

        if (($event->metadata['media_permission_prompt'] ?? false) === true) {
            return true;
        }

        $eventOccurredAt = $this->occurredAt($event);

        if (! $eventOccurredAt) {
            return false;
        }

        return $events->contains(function (ProctoringEvent $other) use ($eventOccurredAt): bool {
            if (! in_array($other->event_type, self::MEDIA_PERMISSION_EVENT_TYPES, true)) {
                return false;
            }

            $otherOccurredAt = $this->occurredAt($other);

            return $otherOccurredAt
                && abs($eventOccurredAt->diffInSeconds($otherOccurredAt, false)) <= self::MEDIA_PERMISSION_GRACE_SECONDS;
        });
    }

    private function occurredAt(ProctoringEvent $event): ?Carbon
    {
        $value = $event->getRawOriginal('occurred_at')
            ?? $event->getAttributes()['occurred_at']
            ?? null;

        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return null;
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
