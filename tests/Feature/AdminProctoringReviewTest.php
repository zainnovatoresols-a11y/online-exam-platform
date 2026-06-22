<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\AttemptProctoringReview;
use App\Models\Organization;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminProctoringReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_save_proctoring_review_for_any_attempt(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin);

        $this->actingAs($superAdmin)
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'approved',
                'risk_level' => 'low',
                'reason_codes' => [],
                'notes' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attempt_proctoring_reviews', [
            'test_attempt_id' => $attempt->id,
            'test_id' => $test->id,
            'reviewed_by_user_id' => $superAdmin->id,
            'status' => 'approved',
            'risk_level' => 'low',
        ]);
    }

    public function test_organization_admin_can_save_review_for_organization_test_attempt(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        [$test, $attempt] = $this->submittedAttemptFor($admin, $organization);

        $this->actingAs($admin)
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'flagged',
                'risk_level' => 'high',
                'reason_codes' => ['tab_switching'],
                'notes' => 'Candidate left the test tab repeatedly.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attempt_proctoring_reviews', [
            'test_attempt_id' => $attempt->id,
            'organization_id' => $organization->id,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'flagged',
            'risk_level' => 'high',
        ]);
    }

    public function test_solo_admin_can_save_review_for_own_solo_test_attempt(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin);

        $this->actingAs($admin)
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'needs_review',
                'risk_level' => 'medium',
                'reason_codes' => ['screen_share_stopped'],
                'notes' => 'Screen share stopped once.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attempt_proctoring_reviews', [
            'test_attempt_id' => $attempt->id,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'needs_review',
            'risk_level' => 'medium',
        ]);
    }

    public function test_admin_cannot_save_review_outside_scope(): void
    {
        $owner = $this->userWithRole(UserRole::Admin);
        $otherAdmin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($owner);

        $this->actingAs($otherAdmin)
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'approved',
                'risk_level' => null,
                'reason_codes' => [],
                'notes' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attempt_proctoring_reviews', 0);
    }

    public function test_candidate_cannot_save_review(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $candidate = $this->userWithRole(UserRole::Candidate);
        [$test, $attempt] = $this->submittedAttemptFor($admin, null, $candidate);

        $this->actingAs($candidate)
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'approved',
                'risk_level' => null,
                'reason_codes' => [],
                'notes' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attempt_proctoring_reviews', 0);
    }

    public function test_review_update_creates_row_if_missing(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin);

        $this->actingAs($admin)
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'approved',
                'risk_level' => null,
                'reason_codes' => [],
                'notes' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('attempt_proctoring_reviews', 1);
    }

    public function test_review_update_updates_existing_row_instead_of_creating_duplicate(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin);

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
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'rejected',
                'risk_level' => 'critical',
                'reason_codes' => ['identity_mismatch'],
                'notes' => 'Identity did not match.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('attempt_proctoring_reviews', 1);
        $this->assertDatabaseHas('attempt_proctoring_reviews', [
            'test_attempt_id' => $attempt->id,
            'status' => 'rejected',
            'risk_level' => 'critical',
            'notes' => 'Identity did not match.',
        ]);
    }

    public function test_rejected_status_requires_reason_code_or_notes(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin);

        $this->actingAs($admin)
            ->from(route('admin.tests.results.show', [$test, $attempt]))
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'rejected',
                'risk_level' => 'high',
                'reason_codes' => [],
                'notes' => null,
            ])
            ->assertRedirect(route('admin.tests.results.show', [$test, $attempt]))
            ->assertSessionHasErrors('reason_codes');

        $this->assertDatabaseCount('attempt_proctoring_reviews', 0);
    }

    public function test_flagged_status_requires_reason_code_or_notes(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin);

        $this->actingAs($admin)
            ->from(route('admin.tests.results.show', [$test, $attempt]))
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'flagged',
                'risk_level' => 'medium',
                'reason_codes' => [],
                'notes' => '',
            ])
            ->assertRedirect(route('admin.tests.results.show', [$test, $attempt]))
            ->assertSessionHasErrors('reason_codes');

        $this->assertDatabaseCount('attempt_proctoring_reviews', 0);
    }

    public function test_admin_result_page_includes_proctoring_review_payload(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin);

        AttemptProctoringReview::create([
            'test_attempt_id' => $attempt->id,
            'test_id' => $test->id,
            'organization_id' => null,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'flagged',
            'risk_level' => 'high',
            'reason_codes' => ['tab_switching'],
            'notes' => 'Repeated tab switching.',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Show')
                ->where('proctoring_review.status', 'flagged')
                ->where('proctoring_review.risk_level', 'high')
                ->where('proctoring_review.reason_codes.0', 'tab_switching')
                ->where('proctoring_review.notes', 'Repeated tab switching.')
                ->where('proctoring_review.reviewed_by.id', $admin->id));
    }

    public function test_candidate_result_page_does_not_expose_proctoring_review_payload(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin, null, $candidate);

        AttemptProctoringReview::create([
            'test_attempt_id' => $attempt->id,
            'test_id' => $test->id,
            'organization_id' => null,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'rejected',
            'risk_level' => 'critical',
            'reason_codes' => ['other'],
            'notes' => 'Private admin note.',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt))
            ->assertOk()
            ->assertDontSee('Private admin note')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidate/Attempts/Result')
                ->missing('proctoring_review'));
    }

    public function test_review_does_not_change_attempt_score_pass_or_fail(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->submittedAttemptFor($admin, null, null, [
            'score' => 80,
            'max_score' => 100,
            'total_marks' => 100,
            'percentage' => 80,
            'passed' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.tests.results.proctoring-review.update', [$test, $attempt]), [
                'status' => 'rejected',
                'risk_level' => 'critical',
                'reason_codes' => ['other'],
                'notes' => 'Manual rejection for proctoring only.',
            ])
            ->assertRedirect();

        $attempt->refresh();

        $this->assertSame(80, (int) $attempt->score);
        $this->assertSame(100, (int) $attempt->max_score);
        $this->assertSame(100, (int) $attempt->total_marks);
        $this->assertSame('80.00', (string) $attempt->percentage);
        $this->assertTrue($attempt->passed);
    }

    /**
     * @return array{0: Test, 1: TestAttempt}
     */
    private function submittedAttemptFor(
        User $admin,
        ?Organization $organization = null,
        ?User $candidate = null,
        array $attemptOverrides = [],
    ): array {
        $test = Test::factory()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $admin->id,
            'title' => 'Proctoring Review Test',
            'duration_minutes' => 60,
            'pass_mark' => 60,
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);

        $attempt = TestAttempt::factory()->create(array_merge([
            'test_id' => $test->id,
            'candidate_user_id' => $candidate?->id,
            'organization_id' => $organization?->id,
            'status' => AttemptStatus::Submitted,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(5),
            'score' => 0,
            'max_score' => 0,
            'total_marks' => 0,
            'percentage' => null,
            'passed' => null,
        ], $attemptOverrides));

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
