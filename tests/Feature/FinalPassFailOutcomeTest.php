<?php

namespace Tests\Feature;

use App\Actions\Attempts\SubmitMcqAttempt;
use App\Enums\AttemptStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\ProctoringEvent;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinalPassFailOutcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_pass_with_zero_suspicious_events_is_final_passed(): void
    {
        [$attempt, $question, $correctOption] = $this->attemptReadyForSubmission();

        app(SubmitMcqAttempt::class)->handle($attempt, [
            $question->id => $correctOption->id,
        ]);

        $attempt->refresh();

        $this->assertSame(10, (int) $attempt->score);
        $this->assertTrue($attempt->score_passed);
        $this->assertSame(0, $attempt->suspicious_event_count);
        $this->assertFalse($attempt->proctoring_failed);
        $this->assertTrue($attempt->passed);
        $this->assertNull($attempt->final_failure_reason);
    }

    public function test_score_pass_with_one_suspicious_event_is_still_final_passed(): void
    {
        [$attempt, $question, $correctOption] = $this->attemptReadyForSubmission();
        $this->recordEvent($attempt, 'copy_attempt', 'high');

        app(SubmitMcqAttempt::class)->handle($attempt, [
            $question->id => $correctOption->id,
        ]);

        $attempt->refresh();

        $this->assertTrue($attempt->score_passed);
        $this->assertSame(1, $attempt->suspicious_event_count);
        $this->assertFalse($attempt->proctoring_failed);
        $this->assertTrue($attempt->passed);
    }

    public function test_score_pass_with_two_suspicious_events_is_final_failed(): void
    {
        [$attempt, $question, $correctOption] = $this->attemptReadyForSubmission();
        $this->recordEvent($attempt, 'copy_attempt', 'high');
        $this->recordEvent($attempt, 'right_click_attempt', 'medium');

        app(SubmitMcqAttempt::class)->handle($attempt, [
            $question->id => $correctOption->id,
        ]);

        $attempt->refresh();

        $this->assertSame(10, (int) $attempt->score);
        $this->assertTrue($attempt->score_passed);
        $this->assertSame(2, $attempt->suspicious_event_count);
        $this->assertTrue($attempt->proctoring_failed);
        $this->assertFalse($attempt->passed);
        $this->assertSame('Failed due to proctoring violations', $attempt->final_failure_reason);
    }

    public function test_score_fail_is_final_failed_even_without_suspicious_events(): void
    {
        [$attempt, $question, $correctOption, $wrongOption] = $this->attemptReadyForSubmission();
        unset($correctOption);

        app(SubmitMcqAttempt::class)->handle($attempt, [
            $question->id => $wrongOption->id,
        ]);

        $attempt->refresh();

        $this->assertSame(0, (int) $attempt->score);
        $this->assertFalse($attempt->score_passed);
        $this->assertSame(0, $attempt->suspicious_event_count);
        $this->assertFalse($attempt->proctoring_failed);
        $this->assertFalse($attempt->passed);
    }

    public function test_normal_lifecycle_events_are_not_counted_as_suspicious(): void
    {
        [$attempt, $question, $correctOption] = $this->attemptReadyForSubmission();

        foreach ([
            'camera_recording_permission_granted',
            'screen_recording_permission_granted',
            'camera_recording_started',
            'screen_recording_started',
            'camera_recording_chunk_uploaded',
            'screen_recording_chunk_uploaded',
            'fullscreen_entered',
            'tab_visible',
            'window_focus',
            'proctoring_violation_acknowledged',
        ] as $eventType) {
            $this->recordEvent($attempt, $eventType, 'low');
        }

        app(SubmitMcqAttempt::class)->handle($attempt, [
            $question->id => $correctOption->id,
        ]);

        $attempt->refresh();

        $this->assertSame(0, $attempt->suspicious_event_count);
        $this->assertFalse($attempt->proctoring_failed);
        $this->assertTrue($attempt->passed);
    }

    public function test_admin_result_payload_includes_final_outcome(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = Test::factory()->create([
            'created_by_id' => $admin->id,
            'organization_id' => null,
            'status' => TestStatus::Published->value,
            'published_at' => now(),
            'pass_mark' => 60,
        ]);
        $attempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'candidate_user_id' => $candidate->id,
            'organization_id' => null,
            'status' => AttemptStatus::Submitted,
            'score' => 10,
            'max_score' => 10,
            'total_marks' => 10,
            'percentage' => 100,
            'passed' => false,
            'score_passed' => true,
            'proctoring_failed' => true,
            'suspicious_event_count' => 2,
            'final_failure_reason' => 'Failed due to proctoring violations',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Show')
                ->where('attempt.score_passed', true)
                ->where('attempt.proctoring_failed', true)
                ->where('attempt.suspicious_event_count', 2)
                ->where('attempt.final_failure_reason', 'Failed due to proctoring violations')
                ->where('attempt.passed', false));
    }

    public function test_candidate_result_page_keeps_final_outcome_private(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = Test::factory()->create([
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);
        $attempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'candidate_user_id' => $candidate->id,
            'status' => AttemptStatus::Submitted,
            'passed' => false,
            'score_passed' => true,
            'proctoring_failed' => true,
            'suspicious_event_count' => 2,
            'final_failure_reason' => 'Failed due to proctoring violations',
            'submitted_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidate/Attempts/Result')
                ->missing('attempt.score')
                ->missing('attempt.passed')
                ->missing('attempt.score_passed')
                ->missing('attempt.proctoring_failed')
                ->missing('attempt.suspicious_event_count')
                ->missing('attempt.final_failure_reason'));
    }

    /**
     * @return array{0: TestAttempt, 1: Question, 2: mixed, 3: mixed}
     */
    private function attemptReadyForSubmission(): array
    {
        config(['code_execution.queue_final_grading' => false]);

        $candidate = User::factory()->create();
        $test = Test::factory()->create([
            'status' => TestStatus::Published->value,
            'published_at' => now(),
            'pass_mark' => 60,
        ]);
        $question = Question::factory()->create([
            'test_id' => $test->id,
            'marks' => 10,
            'order' => 1,
        ]);
        $correctOption = $question->options()->create([
            'body' => 'Correct',
            'is_correct' => true,
        ]);
        $wrongOption = $question->options()->create([
            'body' => 'Wrong',
            'is_correct' => false,
        ]);

        $attempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'candidate_user_id' => $candidate->id,
            'organization_id' => $test->organization_id,
            'status' => AttemptStatus::InProgress,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
        ]);

        return [$attempt, $question, $correctOption, $wrongOption];
    }

    private function recordEvent(TestAttempt $attempt, string $eventType, string $severity): void
    {
        ProctoringEvent::create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'event_type' => $eventType,
            'severity' => $severity,
            'occurred_at' => now(),
        ]);
    }

    private function userWithRole(UserRole $role): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate($role->value, 'web');

        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
