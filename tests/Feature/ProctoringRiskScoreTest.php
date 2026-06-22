<?php

namespace Tests\Feature;

use App\Actions\Results\BuildAttemptResultExportData;
use App\Enums\AttemptStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\AttemptProctoringReview;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\ProctoringEvent;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProctoringRiskScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_results_list_includes_risk_score_and_level(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->attemptWithRiskEvents($admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.index', $test))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Index')
                ->where('results.data.0.proctoring_risk.score', 42)
                ->where('results.data.0.proctoring_risk.level', 'high')
                ->where('results.data.0.proctoring_risk.event_count', 4));
    }

    public function test_admin_attempt_result_page_includes_risk_breakdown(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->attemptWithRiskEvents($admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Show')
                ->where('proctoring_risk.score', 42)
                ->where('proctoring_risk.level', 'high')
                ->where('proctoring_risk.breakdown.0.event_type', 'screen_share_ended')
                ->where('proctoring_risk.breakdown.0.points', 20));
    }

    public function test_candidate_result_page_does_not_expose_risk_score(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        $admin = $this->userWithRole(UserRole::Admin);
        [, $attempt] = $this->attemptWithRiskEvents($admin, null, $candidate);

        $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidate/Attempts/Result')
                ->missing('proctoring_risk'));
    }

    public function test_risk_score_does_not_change_attempt_result_or_review_status(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->attemptWithRiskEvents($admin, null, null, [
            'score' => 80,
            'max_score' => 100,
            'total_marks' => 100,
            'percentage' => 80,
            'passed' => true,
        ]);

        AttemptProctoringReview::create([
            'test_attempt_id' => $attempt->id,
            'test_id' => $test->id,
            'organization_id' => null,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'needs_review',
            'risk_level' => null,
            'reason_codes' => [],
            'notes' => null,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]))
            ->assertOk();

        $attempt->refresh();

        $this->assertSame(80, (int) $attempt->score);
        $this->assertSame(100, (int) $attempt->max_score);
        $this->assertSame(100, (int) $attempt->total_marks);
        $this->assertSame('80.00', (string) $attempt->percentage);
        $this->assertTrue($attempt->passed);
        $this->assertSame('needs_review', $attempt->proctoringReview->status);
    }

    public function test_csv_export_includes_risk_score_and_level(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->attemptWithRiskEvents($admin);

        $content = $this->actingAs($admin)
            ->get(route('admin.tests.results.export.csv', $test))
            ->streamedContent();

        $this->assertStringContainsString('Proctoring Risk Score', $content);
        $this->assertStringContainsString('Proctoring Risk Level', $content);
        $this->assertStringContainsString('42', $content);
        $this->assertStringContainsString('high', $content);
    }

    public function test_pdf_export_data_includes_risk_score_and_level(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->attemptWithRiskEvents($admin);

        $payload = app(BuildAttemptResultExportData::class)($test, $attempt);

        $this->assertSame(42, $payload['proctoring_risk']['score']);
        $this->assertSame('high', $payload['proctoring_risk']['level']);
    }

    /**
     * @return array{0: Test, 1: TestAttempt}
     */
    private function attemptWithRiskEvents(
        User $admin,
        ?Organization $organization = null,
        ?User $candidate = null,
        array $attemptOverrides = [],
    ): array {
        $candidate ??= User::factory()->create();

        $test = Test::factory()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $admin->id,
            'title' => 'Risk Score Test',
            'duration_minutes' => 60,
            'pass_mark' => 60,
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization?->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'name' => $candidate->name,
            'email' => $candidate->email,
        ]);

        $attempt = TestAttempt::factory()->create(array_merge([
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'candidate_user_id' => $candidate->id,
            'organization_id' => $organization?->id,
            'status' => AttemptStatus::Submitted,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(10),
            'score' => 0,
            'max_score' => 0,
            'total_marks' => 0,
            'percentage' => null,
            'passed' => null,
        ], $attemptOverrides));

        foreach ([
            ['event_type' => 'screen_share_ended', 'severity' => 'high'],
            ['event_type' => 'copy_attempt', 'severity' => 'high'],
            ['event_type' => 'fullscreen_exited', 'severity' => 'high'],
            ['event_type' => 'unknown_medium_event', 'severity' => 'medium'],
        ] as $event) {
            ProctoringEvent::create([
                'test_attempt_id' => $attempt->id,
                'candidate_user_id' => $candidate->id,
                'event_type' => $event['event_type'],
                'severity' => $event['severity'],
                'occurred_at' => now()->subMinutes(5),
            ]);
        }

        return [$test, $attempt];
    }

    private function userWithRole(UserRole $role, ?Organization $organization = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate($role->value, 'web');

        $user = User::factory()->create([
            'organization_id' => $organization?->id,
        ]);

        $user->assignRole($role->value);

        return $user;
    }
}
