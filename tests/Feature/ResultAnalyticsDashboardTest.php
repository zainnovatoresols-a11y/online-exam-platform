<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\CodingDifficulty;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\AttemptProctoringReview;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\ProctoringEvent;
use App\Models\ProctoringRecording;
use App\Models\ProctoringRecordingChunk;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResultAnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_analytics_for_any_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);
        [$test] = $this->analyticsDataset($admin, $organization);

        $this->actingAs($superAdmin)
            ->get(route('admin.tests.results.analytics', $test))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Analytics')
                ->where('test.id', $test->id));
    }

    public function test_organization_admin_can_view_analytics_for_own_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        [$test] = $this->analyticsDataset($admin, $organization);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.analytics', $test))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Analytics')
                ->where('test.organization.name', $organization->name));
    }

    public function test_solo_admin_can_view_analytics_for_own_solo_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->analyticsDataset($admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.analytics', $test))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Analytics')
                ->where('test.organization', null));
    }

    public function test_admin_cannot_view_analytics_outside_scope(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::Admin, $organization);
        $outsider = $this->userWithRole(UserRole::Admin, $otherOrganization);
        [$test] = $this->analyticsDataset($owner, $organization);

        $this->actingAs($outsider)
            ->get(route('admin.tests.results.analytics', $test))
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_analytics_page(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $candidate = $this->userWithRole(UserRole::Candidate);
        [$test] = $this->analyticsDataset($admin);

        $this->actingAs($candidate)
            ->get(route('admin.tests.results.analytics', $test))
            ->assertForbidden();
    }

    public function test_analytics_page_includes_overview_score_risk_review_question_and_trend_data(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->analyticsDataset($admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.analytics', $test))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Analytics')
                ->where('overview.total_invitations', 4)
                ->where('overview.accepted_invitations', 3)
                ->where('overview.started_attempts', 3)
                ->where('overview.submitted_attempts', 2)
                ->where('overview.in_progress_attempts', 1)
                ->where('overview.pass_count', 1)
                ->where('overview.fail_count', 1)
                ->where('overview.pass_rate', 50)
                ->where('score_summary.average_score', 5)
                ->where('score_summary.highest_score', 8)
                ->where('score_summary.lowest_score', 2)
                ->where('score_summary.average_percentage', 50)
                ->where('score_summary.pass_percentage', 50)
                ->where('score_summary.mcq_average_score', 1)
                ->where('score_summary.coding_average_score', 4)
                ->where('risk_breakdown.high_count', 1)
                ->where('risk_breakdown.low_count', 2)
                ->where('review_breakdown.approved', 1)
                ->where('review_breakdown.flagged', 1)
                ->where('review_breakdown.needs_review', 1)
                ->where('question_analytics.0.type', QuestionType::Mcq->value)
                ->where('question_analytics.0.success_rate', 50)
                ->where('question_analytics.1.type', QuestionType::Coding->value)
                ->where('question_analytics.1.average_awarded_score', 4)
                ->where('top_suspicious_attempts.0.candidate_email', 'ayesha@example.com')
                ->where('top_suspicious_attempts.0.risk.score', 30)
                ->where('submission_trend.0.submitted_count', 1)
                ->where('submission_trend.1.submitted_count', 1));
    }

    public function test_analytics_filters_apply_to_attempts(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->analyticsDataset($admin);
        $today = now()->toDateString();

        $this->actingAs($admin)
            ->get(route('admin.tests.results.analytics', [
                'test' => $test,
                'from' => $today,
                'to' => $today,
                'status' => 'submitted',
                'review_status' => 'flagged',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Analytics')
                ->where('filters.from', $today)
                ->where('filters.to', $today)
                ->where('filters.status', 'submitted')
                ->where('filters.review_status', 'flagged')
                ->where('overview.started_attempts', 1)
                ->where('overview.submitted_attempts', 1)
                ->where('review_breakdown.flagged', 1)
                ->where('review_breakdown.approved', 0)
                ->where('top_suspicious_attempts.0.candidate_email', 'bilal@example.com'));
    }

    public function test_analytics_page_renders_safely_when_no_attempts_exist(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = Test::factory()->create([
            'organization_id' => null,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.analytics', $test))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Analytics')
                ->where('overview.started_attempts', 0)
                ->where('overview.submitted_attempts', 0)
                ->where('score_summary.average_score', null)
                ->where('top_suspicious_attempts', [])
                ->where('submission_trend', []));
    }

    public function test_analytics_does_not_expose_hidden_test_case_data_or_recording_paths(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempts, $questions] = $this->analyticsDataset($admin);

        $questions['coding']->testCases()->create([
            'input' => 'hidden-secret-input',
            'expected_output' => 'hidden-secret-output',
            'is_hidden' => true,
            'sort_order' => 99,
        ]);

        $recording = ProctoringRecording::create([
            'test_attempt_id' => $attempts['high_risk']->id,
            'candidate_user_id' => $attempts['high_risk']->candidate_user_id,
            'recording_type' => 'screen',
            'status' => 'completed',
            'chunk_count' => 1,
            'total_size_bytes' => 1024,
        ]);

        ProctoringRecordingChunk::create([
            'proctoring_recording_id' => $recording->id,
            'test_attempt_id' => $attempts['high_risk']->id,
            'candidate_user_id' => $attempts['high_risk']->candidate_user_id,
            'recording_type' => 'screen',
            'disk' => 'local',
            'path' => 'proctoring/attempts/123/recordings/screen/screen_000001.webm',
            'mime_type' => 'video/webm',
            'size_bytes' => 1024,
            'sequence' => 1,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.analytics', $test));

        $response->assertOk()
            ->assertDontSee('hidden-secret-input')
            ->assertDontSee('hidden-secret-output')
            ->assertDontSee('proctoring/attempts/123/recordings/screen/screen_000001.webm');
    }

    public function test_results_index_includes_analytics_link(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->analyticsDataset($admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.index', $test));

        $response->assertOk();
        $this->assertStringContainsString(
            'results\\/analytics',
            (string) $response->getContent(),
        );
    }

    /**
     * @return array{0: Test, 1: array<string, TestAttempt>, 2: array<string, Question>}
     */
    private function analyticsDataset(User $admin, ?Organization $organization = null): array
    {
        $test = Test::factory()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $admin->id,
            'title' => 'Analytics Test',
            'duration_minutes' => 60,
            'pass_mark' => 60,
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);

        $mcqQuestion = Question::factory()->create([
            'test_id' => $test->id,
            'type' => QuestionType::Mcq->value,
            'body' => 'What is Laravel?',
            'marks' => 2,
            'order' => 1,
        ]);
        $mcqQuestion->options()->create([
            'body' => 'A PHP framework',
            'is_correct' => true,
        ]);
        $mcqQuestion->options()->create([
            'body' => 'A queue worker',
            'is_correct' => false,
        ]);

        $codingQuestion = Question::factory()->create([
            'test_id' => $test->id,
            'type' => QuestionType::Coding->value,
            'body' => 'Reverse a string.',
            'marks' => 8,
            'order' => 2,
            'difficulty' => CodingDifficulty::Easy->value,
            'time_limit_ms' => 2000,
            'memory_limit_kb' => 128000,
            'supported_languages' => ['php', 'javascript'],
            'starter_code' => [
                'php' => '<?php',
                'javascript' => 'function solve(input) {}',
            ],
        ]);

        $highRiskCandidate = User::factory()->create();
        $lowRiskCandidate = User::factory()->create();
        $inProgressCandidate = User::factory()->create();

        $highRiskInvitation = Invitation::factory()->create([
            'organization_id' => $organization?->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $highRiskCandidate->id,
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now()->subDays(2),
            'policy_accepted_at' => now()->subDays(2),
        ]);

        $lowRiskInvitation = Invitation::factory()->create([
            'organization_id' => $organization?->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $lowRiskCandidate->id,
            'name' => 'Bilal Ahmed',
            'email' => 'bilal@example.com',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now()->subHours(4),
            'policy_accepted_at' => now()->subHours(4),
        ]);

        $inProgressInvitation = Invitation::factory()->create([
            'organization_id' => $organization?->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $inProgressCandidate->id,
            'name' => 'Sara Noor',
            'email' => 'sara@example.com',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now()->subHour(),
            'policy_accepted_at' => now()->subHour(),
        ]);

        Invitation::factory()->create([
            'organization_id' => $organization?->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => null,
            'name' => 'Pending Invite',
            'email' => 'pending@example.com',
            'status' => InvitationStatus::Sent,
        ]);

        $highRiskAttempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'invitation_id' => $highRiskInvitation->id,
            'candidate_user_id' => $highRiskCandidate->id,
            'organization_id' => $organization?->id,
            'status' => AttemptStatus::Submitted,
            'started_at' => now()->subDays(2),
            'submitted_at' => now()->subDays(2)->addMinutes(30),
            'expires_at' => now()->subDays(2)->addHour(),
            'score' => 8,
            'max_score' => 10,
            'total_marks' => 10,
            'percentage' => 80,
            'passed' => true,
        ]);

        $lowRiskAttempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'invitation_id' => $lowRiskInvitation->id,
            'candidate_user_id' => $lowRiskCandidate->id,
            'organization_id' => $organization?->id,
            'status' => AttemptStatus::Submitted,
            'started_at' => now()->subHours(3),
            'submitted_at' => now()->subHours(2)->addMinutes(15),
            'expires_at' => now()->addHours(2),
            'score' => 2,
            'max_score' => 10,
            'total_marks' => 10,
            'percentage' => 20,
            'passed' => false,
        ]);

        $inProgressAttempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'invitation_id' => $inProgressInvitation->id,
            'candidate_user_id' => $inProgressCandidate->id,
            'organization_id' => $organization?->id,
            'status' => AttemptStatus::InProgress,
            'started_at' => now()->subMinutes(45),
            'submitted_at' => null,
            'expires_at' => now()->addMinutes(15),
            'score' => 0,
            'max_score' => 10,
            'total_marks' => 10,
            'percentage' => null,
            'passed' => null,
        ]);

        $this->createCandidateDetail($test, $highRiskInvitation, $highRiskAttempt, 'Ayesha Khan', 'ayesha@example.com');
        $this->createCandidateDetail($test, $lowRiskInvitation, $lowRiskAttempt, 'Bilal Ahmed', 'bilal@example.com');
        $this->createCandidateDetail($test, $inProgressInvitation, $inProgressAttempt, 'Sara Noor', 'sara@example.com');

        $highRiskAttempt->answers()->create([
            'question_id' => $mcqQuestion->id,
            'selected_option_id' => $mcqQuestion->options()->where('is_correct', true)->value('id'),
            'is_correct' => true,
            'score' => 2,
        ]);
        $highRiskAttempt->answers()->create([
            'question_id' => $codingQuestion->id,
            'language' => 'php',
            'submitted_code' => '<?php echo strrev($input);',
            'is_correct' => true,
            'score' => 6,
        ]);

        $lowRiskAttempt->answers()->create([
            'question_id' => $mcqQuestion->id,
            'selected_option_id' => $mcqQuestion->options()->where('is_correct', false)->value('id'),
            'is_correct' => false,
            'score' => 0,
        ]);
        $lowRiskAttempt->answers()->create([
            'question_id' => $codingQuestion->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log(input);',
            'is_correct' => false,
            'score' => 2,
        ]);

        ProctoringEvent::create([
            'test_attempt_id' => $highRiskAttempt->id,
            'candidate_user_id' => $highRiskCandidate->id,
            'event_type' => 'screen_share_ended',
            'severity' => 'high',
            'occurred_at' => now()->subDays(2)->addMinutes(10),
        ]);
        ProctoringEvent::create([
            'test_attempt_id' => $highRiskAttempt->id,
            'candidate_user_id' => $highRiskCandidate->id,
            'event_type' => 'copy_attempt',
            'severity' => 'high',
            'occurred_at' => now()->subDays(2)->addMinutes(15),
        ]);
        ProctoringEvent::create([
            'test_attempt_id' => $lowRiskAttempt->id,
            'candidate_user_id' => $lowRiskCandidate->id,
            'event_type' => 'right_click_attempt',
            'severity' => 'medium',
            'occurred_at' => now()->subHours(3)->addMinutes(10),
        ]);
        ProctoringEvent::create([
            'test_attempt_id' => $lowRiskAttempt->id,
            'candidate_user_id' => $lowRiskCandidate->id,
            'event_type' => 'window_blur',
            'severity' => 'medium',
            'occurred_at' => now()->subHours(3)->addMinutes(20),
        ]);

        AttemptProctoringReview::create([
            'test_attempt_id' => $highRiskAttempt->id,
            'test_id' => $test->id,
            'organization_id' => $organization?->id,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'approved',
            'risk_level' => 'high',
            'reason_codes' => ['screen_interruption'],
            'notes' => 'Reviewed and approved.',
            'reviewed_at' => now()->subDay(),
        ]);
        AttemptProctoringReview::create([
            'test_attempt_id' => $lowRiskAttempt->id,
            'test_id' => $test->id,
            'organization_id' => $organization?->id,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'flagged',
            'risk_level' => 'low',
            'reason_codes' => ['browser_activity'],
            'notes' => 'Needs further review.',
            'reviewed_at' => now()->subHour(),
        ]);

        return [
            $test,
            [
                'high_risk' => $highRiskAttempt,
                'low_risk' => $lowRiskAttempt,
                'in_progress' => $inProgressAttempt,
            ],
            [
                'mcq' => $mcqQuestion,
                'coding' => $codingQuestion,
            ],
        ];
    }

    private function createCandidateDetail(
        Test $test,
        Invitation $invitation,
        TestAttempt $attempt,
        string $name,
        string $email,
    ): void {
        CandidateTestDetail::create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'test_attempt_id' => $attempt->id,
            'name' => $name,
            'email' => $email,
            'phone' => '03001234567',
            'stack_name' => 'Laravel',
            'fields' => [
                'name' => $name,
                'email' => $email,
                'phone' => '03001234567',
                'stack_name' => 'Laravel',
            ],
            'submitted_at' => $attempt->started_at,
        ]);
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
