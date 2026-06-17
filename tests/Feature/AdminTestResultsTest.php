<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\CodingDifficulty;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\CandidateTestDetail;
use App\Models\CodeExecutionRun;
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

    public function test_super_admin_can_view_any_attempt_result_detail(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);
        $test = $this->assessmentForAdmin($admin, $organization);
        [, $attempt] = $this->submittedPublicAttemptFor($test, $admin);

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('test.id', $test->id)
            ->where('attempt.id', $attempt->id));
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

    public function test_solo_admin_can_view_attempt_result_detail_for_their_solo_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->assessmentForAdmin($admin);
        [, $attempt] = $this->submittedPublicAttemptFor($test, $admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('test.organization', null)
            ->where('attempt.id', $attempt->id));
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

        $this->actingAs($candidate)
            ->get(route('admin.tests.results.show', [$test, TestAttempt::factory()->create(['test_id' => $test->id])]))
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

    public function test_admin_can_review_coding_answer_and_hidden_final_test_case_results(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->assessmentForAdmin($admin, $organization);
        [, $attempt] = $this->submittedPublicAttemptFor($test, $admin);
        $this->codingAnswerWithFinalRun($test, $attempt);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('answers.0.type', QuestionType::Coding->value)
            ->where('answers.0.question.body', 'Reverse a string.')
            ->where('answers.0.language', 'javascript')
            ->where('answers.0.submitted_code', 'console.log("cba");')
            ->where('answers.0.score', 5)
            ->where('answers.0.execution_run.run_type', 'final')
            ->where('answers.0.execution_run.status', 'completed')
            ->where('answers.0.execution_run.score_awarded', 5)
            ->where('answers.0.execution_run.hidden_summary.total', 1)
            ->where('answers.0.execution_run.hidden_summary.passed', 1)
            ->where('answers.0.execution_run.test_case_results.1.is_hidden', true)
            ->where('answers.0.execution_run.test_case_results.1.input', 'hidden-admin-input')
            ->where('answers.0.execution_run.test_case_results.1.expected_output', 'hidden-output')
            ->where('answers.0.execution_run.test_case_results.1.stdout', 'hidden-output')
            ->where('answers.0.execution_run.test_case_results.1.stderr', 'debug stderr')
            ->where('answers.0.execution_run.test_case_results.1.compile_output', 'compile note')
            ->where('answers.0.execution_run.test_case_results.1.message', 'Accepted')
            ->where('answers.0.execution_run.test_case_results.1.judge0_status_description', 'Accepted'));
    }

    public function test_candidate_result_page_still_does_not_expose_hidden_coding_result_details(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->assessmentForAdmin($admin, $organization);
        [$invitation, $attempt] = $this->submittedPublicAttemptFor($test, $admin);
        $this->codingAnswerWithFinalRun($test, $attempt);

        $response = $this->get(route('candidate.public-attempts.show', $invitation->token));

        $response->assertOk()
            ->assertDontSee('hidden-admin-input')
            ->assertDontSee('hidden-output');
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Result')
            ->missing('answers')
            ->missing('code_execution_runs'));
    }

    public function test_admin_result_page_handles_coding_answer_without_final_execution_run(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->assessmentForAdmin($admin, $organization);
        [, $attempt] = $this->submittedPublicAttemptFor($test, $admin);
        $codingQuestion = $this->codingQuestion($test);

        $attempt->answers()->create([
            'question_id' => $codingQuestion->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log("pending");',
            'selected_option_id' => null,
            'is_correct' => false,
            'score' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('answers.0.type', QuestionType::Coding->value)
            ->where('answers.0.execution_run', null));
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

    private function codingQuestion(Test $test): Question
    {
        $question = $test->questions()->create([
            'type' => QuestionType::Coding->value,
            'body' => 'Reverse a string.',
            'marks' => 5,
            'order' => 1,
            'difficulty' => CodingDifficulty::Easy->value,
            'time_limit_ms' => 2000,
            'memory_limit_kb' => 128000,
            'supported_languages' => ['javascript', 'python'],
            'starter_code' => [
                'javascript' => 'function solve(input) {}',
                'python' => 'def solve(input): pass',
            ],
        ]);

        $question->testCases()->create([
            'input' => 'abc',
            'expected_output' => 'cba',
            'is_hidden' => false,
            'sort_order' => 1,
        ]);
        $question->testCases()->create([
            'input' => 'hidden-admin-input',
            'expected_output' => 'hidden-output',
            'is_hidden' => true,
            'sort_order' => 2,
        ]);

        return $question;
    }

    private function codingAnswerWithFinalRun(Test $test, TestAttempt $attempt): void
    {
        $question = $this->codingQuestion($test);
        $answer = $attempt->answers()->create([
            'question_id' => $question->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log("cba");',
            'selected_option_id' => null,
            'is_correct' => true,
            'score' => 5,
        ]);
        $run = CodeExecutionRun::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'attempt_answer_id' => $answer->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'language' => 'javascript',
            'status' => 'completed',
            'run_type' => 'final',
            'source_code' => 'console.log("cba");',
            'result_summary' => [
                'status' => 'completed',
                'total' => 2,
                'passed' => 2,
                'failed' => 0,
            ],
            'score_awarded' => 5,
            'max_score' => 5,
            'passed' => true,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
        $visibleCase = $question->testCases()->where('is_hidden', false)->firstOrFail();
        $hiddenCase = $question->testCases()->where('is_hidden', true)->firstOrFail();

        $run->testCaseResults()->create([
            'question_test_case_id' => $visibleCase->id,
            'is_hidden' => false,
            'status' => 'passed',
            'passed' => true,
            'input' => 'abc',
            'expected_output' => 'cba',
            'actual_output' => 'cba',
            'stdout' => 'cba',
            'judge0_status_id' => 3,
            'judge0_status_description' => 'Accepted',
        ]);
        $run->testCaseResults()->create([
            'question_test_case_id' => $hiddenCase->id,
            'is_hidden' => true,
            'status' => 'passed',
            'passed' => true,
            'input' => 'hidden-admin-input',
            'expected_output' => 'hidden-output',
            'actual_output' => 'hidden-output',
            'stdout' => 'hidden-output',
            'stderr' => 'debug stderr',
            'compile_output' => 'compile note',
            'message' => 'Accepted',
            'time' => 0.012,
            'memory' => 12000,
            'judge0_status_id' => 3,
            'judge0_status_description' => 'Accepted',
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
