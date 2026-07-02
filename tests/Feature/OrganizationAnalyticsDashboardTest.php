<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\AttemptProctoringReview;
use App\Models\CandidateTestDetail;
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

class OrganizationAnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_owner_can_view_own_organization_analytics(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::SuperAdmin, $organization);
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $dataset = $this->organizationAnalyticsDataset($organization, $admin);

        $this->actingAs($owner)
            ->get(route('super-admin.organizations.analytics', $organization))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/Organizations/Analytics')
                ->where('organization.id', $organization->id)
                ->where('overview.total_admins', 1)
                ->where('overview.total_tests', 2)
                ->where('overview.total_invitations', 3)
                ->where('overview.accepted_invitations', 3)
                ->where('overview.unique_candidates', 3)
                ->where('overview.started_attempts', 3)
                ->where('overview.submitted_attempts', 2)
                ->where('overview.pass_count', 1)
                ->where('overview.fail_count', 1)
                ->where('overview.pass_rate', 50)
                ->where('overview.high_risk_attempts', 1)
                ->where('score_summary.average_score', 50)
                ->where('score_summary.average_percentage', 50)
                ->where('risk_breakdown.high_count', 1)
                ->where('review_breakdown.approved', 1)
                ->where('review_breakdown.flagged', 1)
                ->where('review_breakdown.needs_review', 1)
                ->where('admin_activity.0.email', $admin->email)
                ->where('admin_activity.0.tests_count', 2)
                ->where('test_summaries.0.test_id', $dataset['main_test']->id)
                ->where('test_summaries.0.results_url', route('admin.tests.results.index', $dataset['main_test']))
                ->where('test_summaries.0.analytics_url', route('admin.tests.results.analytics', $dataset['main_test']))
                ->where('top_suspicious_attempts.0.candidate_email', 'high-risk@example.com')
                ->where('top_suspicious_attempts.0.risk.score', 30));
    }

    public function test_organization_owner_cannot_view_another_organization_analytics(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::SuperAdmin, $organization);

        $this->actingAs($owner)
            ->get(route('super-admin.organizations.analytics', $otherOrganization))
            ->assertForbidden();
    }

    public function test_platform_super_admin_can_view_any_organization_analytics(): void
    {
        $organization = Organization::factory()->create();
        $platformSuperAdmin = $this->userWithRole(UserRole::SuperAdmin);
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $this->organizationAnalyticsDataset($organization, $admin);

        $this->actingAs($platformSuperAdmin)
            ->get(route('super-admin.organizations.analytics', $organization))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/Organizations/Analytics')
                ->where('organization.id', $organization->id));
    }

    public function test_regular_admin_and_candidate_cannot_access_organization_analytics(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $candidate = $this->userWithRole(UserRole::Candidate);

        $this->actingAs($admin)
            ->get(route('super-admin.organizations.analytics', $organization))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('super-admin.organizations.analytics', $organization))
            ->assertForbidden();
    }

    public function test_organization_analytics_filters_work(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::SuperAdmin, $organization);
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $this->organizationAnalyticsDataset($organization, $admin);
        $today = now()->toDateString();

        $this->actingAs($owner)
            ->get(route('super-admin.organizations.analytics', [
                'organization' => $organization,
                'from' => $today,
                'to' => $today,
                'test_status' => TestStatus::Published->value,
                'review_status' => 'approved',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/Organizations/Analytics')
                ->where('filters.from', $today)
                ->where('filters.to', $today)
                ->where('filters.test_status', TestStatus::Published->value)
                ->where('filters.review_status', 'approved')
                ->where('overview.total_tests', 1)
                ->where('overview.total_invitations', 1)
                ->where('overview.started_attempts', 1)
                ->where('overview.submitted_attempts', 1)
                ->where('review_breakdown.approved', 1)
                ->where('review_breakdown.flagged', 0));
    }

    public function test_organization_show_page_includes_analytics_link(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::SuperAdmin, $organization);

        $this->actingAs($owner)
            ->get(route('super-admin.organizations.show', $organization))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/Organizations/Show')
                ->where('analytics_url', route('super-admin.organizations.analytics', $organization)));
    }

    /**
     * @return array{main_test: Test, secondary_test: Test}
     */
    private function organizationAnalyticsDataset(Organization $organization, User $admin): array
    {
        $secondaryTest = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'title' => 'Draft Hiring Test',
            'status' => TestStatus::Draft->value,
            'created_at' => now()->subDays(3),
        ]);

        $mainTest = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'title' => 'Published Hiring Test',
            'status' => TestStatus::Published->value,
            'published_at' => now(),
            'created_at' => now(),
        ]);

        $highRiskCandidate = User::factory()->create(['email' => 'high-risk@example.com']);
        $lowRiskCandidate = User::factory()->create(['email' => 'low-risk@example.com']);
        $inProgressCandidate = User::factory()->create(['email' => 'progress@example.com']);

        $highRiskInvitation = $this->invitation($organization, $mainTest, $admin, $highRiskCandidate, 'high-risk@example.com');
        $lowRiskInvitation = $this->invitation($organization, $mainTest, $admin, $lowRiskCandidate, 'low-risk@example.com');
        $inProgressInvitation = $this->invitation($organization, $mainTest, $admin, $inProgressCandidate, 'progress@example.com');

        $highRiskAttempt = $this->attempt(
            $organization,
            $mainTest,
            $highRiskInvitation,
            $highRiskCandidate,
            AttemptStatus::Submitted,
            80,
            80,
            true,
        );
        $lowRiskAttempt = $this->attempt(
            $organization,
            $mainTest,
            $lowRiskInvitation,
            $lowRiskCandidate,
            AttemptStatus::Submitted,
            20,
            20,
            false,
        );
        $this->attempt(
            $organization,
            $mainTest,
            $inProgressInvitation,
            $inProgressCandidate,
            AttemptStatus::InProgress,
            0,
            null,
            null,
        );

        $this->candidateDetail($organization, $mainTest, $highRiskInvitation, $highRiskAttempt, 'High Risk', 'high-risk@example.com');
        $this->candidateDetail($organization, $mainTest, $lowRiskInvitation, $lowRiskAttempt, 'Low Risk', 'low-risk@example.com');

        ProctoringEvent::create([
            'test_attempt_id' => $highRiskAttempt->id,
            'candidate_user_id' => $highRiskCandidate->id,
            'event_type' => 'screen_share_ended',
            'severity' => 'high',
            'occurred_at' => now()->subMinutes(30),
        ]);
        ProctoringEvent::create([
            'test_attempt_id' => $highRiskAttempt->id,
            'candidate_user_id' => $highRiskCandidate->id,
            'event_type' => 'copy_attempt',
            'severity' => 'high',
            'occurred_at' => now()->subMinutes(25),
        ]);
        ProctoringEvent::create([
            'test_attempt_id' => $lowRiskAttempt->id,
            'candidate_user_id' => $lowRiskCandidate->id,
            'event_type' => 'right_click_attempt',
            'severity' => 'medium',
            'occurred_at' => now()->subMinutes(20),
        ]);

        AttemptProctoringReview::create([
            'test_attempt_id' => $highRiskAttempt->id,
            'test_id' => $mainTest->id,
            'organization_id' => $organization->id,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'approved',
            'risk_level' => 'high',
            'reason_codes' => ['screen_interruption'],
            'notes' => 'Reviewed.',
            'reviewed_at' => now(),
        ]);
        AttemptProctoringReview::create([
            'test_attempt_id' => $lowRiskAttempt->id,
            'test_id' => $mainTest->id,
            'organization_id' => $organization->id,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'flagged',
            'risk_level' => 'medium',
            'reason_codes' => ['browser_activity'],
            'notes' => 'Flagged.',
            'reviewed_at' => now(),
        ]);

        return [
            'main_test' => $mainTest,
            'secondary_test' => $secondaryTest,
        ];
    }

    private function invitation(
        Organization $organization,
        Test $test,
        User $admin,
        User $candidate,
        string $email,
    ): Invitation {
        return Invitation::factory()->create([
            'organization_id' => $organization->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'name' => $candidate->name,
            'email' => $email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
            'policy_accepted_at' => now(),
        ]);
    }

    private function attempt(
        Organization $organization,
        Test $test,
        Invitation $invitation,
        User $candidate,
        AttemptStatus $status,
        int $score,
        ?int $percentage,
        ?bool $passed,
    ): TestAttempt {
        return TestAttempt::factory()->create([
            'organization_id' => $organization->id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'candidate_user_id' => $candidate->id,
            'status' => $status,
            'started_at' => now()->subHour(),
            'submitted_at' => $status === AttemptStatus::Submitted ? now()->subMinutes(10) : null,
            'expires_at' => now()->addHour(),
            'score' => $score,
            'max_score' => 100,
            'percentage' => $percentage,
            'passed' => $passed,
        ]);
    }

    private function candidateDetail(
        Organization $organization,
        Test $test,
        Invitation $invitation,
        TestAttempt $attempt,
        string $name,
        string $email,
    ): void {
        CandidateTestDetail::create([
            'organization_id' => $organization->id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'test_attempt_id' => $attempt->id,
            'name' => $name,
            'email' => $email,
            'fields' => [
                'name' => $name,
                'email' => $email,
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
