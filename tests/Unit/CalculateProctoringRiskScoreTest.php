<?php

namespace Tests\Unit;

use App\Actions\Proctoring\CalculateProctoringRiskScore;
use App\Models\ProctoringEvent;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CalculateProctoringRiskScoreTest extends TestCase
{
    public function test_it_returns_low_for_no_events(): void
    {
        $result = $this->calculator()->handle(collect());

        $this->assertSame(0, $result['score']);
        $this->assertSame('low', $result['level']);
        $this->assertSame(0, $result['event_count']);
        $this->assertSame([], $result['breakdown']);
    }

    public function test_it_uses_explicit_event_weights(): void
    {
        $result = $this->calculator()->handle($this->events([
            ['fullscreen_exited', 'high'],
            ['fullscreen_exited', 'high'],
            ['copy_attempt', 'high'],
        ]));

        $this->assertSame(26, $result['score']);
        $this->assertSame('medium', $result['level']);
        $this->assertSame([
            [
                'event_type' => 'fullscreen_exited',
                'label' => 'Fullscreen Exited',
                'count' => 2,
                'points_each' => 8,
                'points' => 16,
            ],
            [
                'event_type' => 'copy_attempt',
                'label' => 'Copy Attempt',
                'count' => 1,
                'points_each' => 10,
                'points' => 10,
            ],
        ], $result['breakdown']);
    }

    public function test_it_uses_severity_fallback_for_unknown_events(): void
    {
        $result = $this->calculator()->handle($this->events([
            ['unknown_event', 'high'],
            ['unknown_event', 'medium'],
            ['another_unknown_event', 'low'],
        ]));

        $this->assertSame(15, $result['score']);
        $this->assertSame('medium', $result['level']);
    }

    public function test_risk_level_thresholds_work(): void
    {
        $calculator = $this->calculator();

        $this->assertSame('low', $calculator->handle($this->repeatedEvents('unknown_event', 'low', 9))['level']);
        $this->assertSame('medium', $calculator->handle($this->repeatedEvents('copy_attempt', 'high', 1))['level']);
        $this->assertSame('high', $calculator->handle($this->repeatedEvents('fullscreen_exited', 'high', 4))['level']);
        $this->assertSame('critical', $calculator->handle($this->repeatedEvents('screen_share_ended', 'high', 3))['level']);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $events
     * @return Collection<int, ProctoringEvent>
     */
    private function events(array $events): Collection
    {
        return collect($events)
            ->map(fn (array $event): ProctoringEvent => new ProctoringEvent([
                'event_type' => $event[0],
                'severity' => $event[1],
            ]));
    }

    /**
     * @return Collection<int, ProctoringEvent>
     */
    private function repeatedEvents(string $eventType, string $severity, int $count): Collection
    {
        return Collection::times($count)
            ->map(fn (): ProctoringEvent => new ProctoringEvent([
                'event_type' => $eventType,
                'severity' => $severity,
            ]));
    }

    private function calculator(): CalculateProctoringRiskScore
    {
        return new CalculateProctoringRiskScore;
    }
}
