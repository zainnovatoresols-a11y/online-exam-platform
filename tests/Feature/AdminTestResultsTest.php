<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminTestResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_organization_test_result_rows(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->assessmentForAdmin($admin, $organization);
        [$invitation, $attempt] = $this->submittedPublicAttemptFor($test, $admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.index', $test));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Index')
            ->where('test.id', $test->id)
            ->where('test.invitations_count', 1)
            ->where('test.attempts_count', 1)
            ->where('results.data.0.invitation.id', $invitation->id)
            ->where('results.data.0.invitation.status', InvitationStatus::Accepted->value)
            ->where('results.data.0.candidate.name', 'Ayesha Khan')
            ->where('results.data.0.candidate.email', 'ayesha@example.com')
            ->where('results.data.0.candidate.phone', '03001234567')
            ->where('results.data.0.candidate.stack_name', 'Laravel')
            ->where('results.data.0.attempt.id', $attempt->id)
            ->where('results.data.0.attempt.score', 8)
            ->where('results.data.0.attempt.percentage', 80)
            ->where('results.data.0.attempt.passed', true));
    }

    public function test_admin_can_view_attempt_mcq_answer_details(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->assessmentForAdmin($admin, $organization);
        [, $attempt] = $this->submittedPublicAttemptFor($test, $admin);
        [$question, $correctOption, $wrongOption] = $this->questionWithOptions($test);

        $attempt->answers()->create([
            'question_id' => $question->id,
            'selected_option_id' => $wrongOption->id,
            'is_correct' => false,
            'score' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('test.id', $test->id)
            ->where('attempt.id', $attempt->id)
            ->where('candidate.email', 'ayesha@example.com')
            ->where('answers.0.question.body', 'What is Laravel?')
            ->where('answers.0.selected_option.body', $wrongOption->body)
            ->where('answers.0.selected_option.is_correct', false)
            ->where('answers.0.correct_options.0.id', $correctOption->id)
            ->where('answers.0.correct_options.0.body', $correctOption->body)
            ->where('answers.0.is_correct', false)
            ->where('answers.0.score', 0));
    }

    public function test_solo_admin_can_view_results_for_their_solo_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->assessmentForAdmin($admin);
        $this->submittedPublicAttemptFor($test, $admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.index', $test));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Index')
            ->where('test.id', $test->id)
            ->where('test.organization', null)
            ->where('results.data.0.candidate.email', 'ayesha@example.com'));
    }

    public function test_admin_cannot_view_results_outside_their_scope(): void
    {
        $adminOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $adminOrganization);
        $otherAdmin = $this->userWithRole(UserRole::Admin, $otherOrganization);
        $otherOrganizationTest = $this->assessmentForAdmin($otherAdmin, $otherOrganization);

        $soloOwner = $this->userWithRole(UserRole::Admin);
        $otherSoloAdmin = $this->userWithRole(UserRole::Admin);
        $soloTest = $this->assessmentForAdmin($soloOwner);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.index', $otherOrganizationTest))
            ->assertForbidden();

        $this->actingAs($otherSoloAdmin)
            ->get(route('admin.tests.results.index', $soloTest))
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_admin_result_routes(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = $this->assessmentForAdmin($admin, $organization);

        $this->actingAs($candidate)
            ->get(route('admin.tests.results.index', $test))
            ->assertForbidden();
    }

    public function test_admin_cannot_view_attempt_from_another_test_result_detail(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->assessmentForAdmin($admin, $organization);
        $otherTest = $this->assessmentForAdmin($admin, $organization);
        [, $otherAttempt] = $this->submittedPublicAttemptFor($otherTest, $admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $otherAttempt]))
            ->assertNotFound();
    }

    private function assessmentForAdmin(User $admin, ?Organization $organization = null): Test
    {
        return Test::factory()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $admin->id,
            'title' => 'Backend Developer Assessment',
            'pass_mark' => 60,
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    /**
     * @return array{0: Invitation, 1: TestAttempt}
     */
    private function submittedPublicAttemptFor(Test $test, User $admin): array
    {
        $startedAt = now()->subMinutes(30);
        $submittedAt = now()->subMinutes(5);

        $invitation = Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => null,
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => $startedAt,
            'policy_accepted_at' => $startedAt,
        ]);

        $attempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'candidate_user_id' => null,
            'organization_id' => $test->organization_id,
            'status' => AttemptStatus::Submitted,
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'expires_at' => $startedAt->copy()->addMinutes((int) $test->duration_minutes),
            'score' => 8,
            'max_score' => 10,
            'total_marks' => 10,
            'percentage' => 80,
            'passed' => true,
        ]);

        CandidateTestDetail::create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'test_attempt_id' => $attempt->id,
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'phone' => '03001234567',
            'stack_name' => 'Laravel',
            'fields' => [
                'name' => 'Ayesha Khan',
                'email' => 'ayesha@example.com',
                'phone' => '03001234567',
                'stack_name' => 'Laravel',
            ],
            'submitted_at' => $startedAt,
        ]);

        return [$invitation, $attempt];
    }

    /**
     * @return array{0: Question, 1: QuestionOption, 2: QuestionOption}
     */
    private function questionWithOptions(Test $test): array
    {
        $question = Question::factory()->create([
            'test_id' => $test->id,
            'body' => 'What is Laravel?',
            'marks' => 2,
            'order' => 1,
        ]);
        $correctOption = $question->options()->create([
            'body' => 'A PHP framework',
            'is_correct' => true,
        ]);
        $wrongOption = $question->options()->create([
            'body' => 'A database server',
            'is_correct' => false,
        ]);

        return [$question, $correctOption, $wrongOption];
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
