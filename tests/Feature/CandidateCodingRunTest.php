<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\CodingDifficulty;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
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
use App\Services\CodeExecution\CodeExecutionService;
use App\Services\CodeExecution\FakeCodeExecutionService;
use App\Services\CodeExecution\Judge0CodeExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CandidateCodingRunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(CodeExecutionService::class, FakeCodeExecutionService::class);
    }

    public function test_candidate_can_run_visible_test_cases_for_own_in_progress_attempt(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $response->assertOk()
            ->assertJsonPath('run.status', 'completed')
            ->assertJsonPath('run.summary.total', 1)
            ->assertJsonPath('run.summary.passed', 1)
            ->assertJsonPath('run.results.0.passed', true)
            ->assertJsonPath('run.results.0.input', 'abc')
            ->assertJsonPath('run.results.0.expected_output', 'cba')
            ->assertJsonPath('run.results.0.actual_output', 'cba');
    }

    public function test_run_creates_execution_run_and_test_case_result_rows(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ])
            ->assertOk();

        $run = CodeExecutionRun::firstOrFail();

        $this->assertSame($attempt->id, $run->test_attempt_id);
        $this->assertSame($question->id, $run->question_id);
        $this->assertSame($candidate->id, $run->candidate_user_id);
        $this->assertSame('completed', $run->status);
        $this->assertDatabaseHas('code_execution_test_case_results', [
            'code_execution_run_id' => $run->id,
            'is_hidden' => false,
            'passed' => true,
            'input' => 'abc',
            'expected_output' => 'cba',
            'actual_output' => 'cba',
        ]);
    }

    public function test_candidate_sees_failed_visible_test_case_result(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => '__FAIL__',
            ]);

        $response->assertOk()
            ->assertJsonPath('run.status', 'completed_with_failures')
            ->assertJsonPath('run.summary.failed', 1)
            ->assertJsonPath('run.results.0.passed', false)
            ->assertJsonPath('run.results.0.actual_output', 'wrong output');
    }

    public function test_hidden_test_cases_are_not_executed_or_returned(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $hiddenCase = $question->testCases()->where('is_hidden', true)->firstOrFail();
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $response->assertOk()
            ->assertJsonCount(1, 'run.results')
            ->assertJsonMissing(['input' => $hiddenCase->input])
            ->assertJsonMissing(['expected_output' => $hiddenCase->expected_output]);
        $this->assertDatabaseMissing('code_execution_test_case_results', [
            'question_test_case_id' => $hiddenCase->id,
        ]);
    }

    public function test_candidate_cannot_run_code_for_another_candidates_attempt(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($otherCandidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("no");',
            ])
            ->assertForbidden();
    }

    public function test_candidate_cannot_run_code_after_submission(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);
        $attempt->update(['status' => AttemptStatus::Submitted]);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("late");',
            ])
            ->assertForbidden();
    }

    public function test_candidate_cannot_run_code_after_expiry(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);
        $attempt->update(['expires_at' => now()->subSecond()]);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("late");',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.attempt.0', 'This test has expired and code can no longer be run.');
    }

    public function test_candidate_cannot_run_code_for_mcq_question(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$mcqQuestion] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $mcqQuestion->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("no");',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.question_id.0', 'Question must be a coding question.');
    }

    public function test_candidate_cannot_run_unsupported_language(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'java',
                'submitted_code' => 'class Main {}',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.language.0', 'Selected language is not supported for this question.');
    }

    public function test_missing_judge0_language_config_is_handled_cleanly(): void
    {
        $this->app->bind(CodeExecutionService::class, Judge0CodeExecutionService::class);
        config(['judge0.language_ids.javascript' => null]);

        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.coding-questions.run', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.run.0', 'Judge0 language is not configured.');

        $this->assertDatabaseHas('code_execution_runs', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'status' => 'failed',
        ]);
    }

    public function test_public_candidate_can_run_visible_test_cases_without_authentication(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $question = $this->codingQuestion($test);
        $invitation = Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => null,
            'email' => 'public-run@example.com',
            'name' => 'Public Run',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
            'policy_accepted_at' => now(),
        ]);
        CandidateTestDetail::create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'name' => 'Public Run',
            'email' => 'public-run@example.com',
        ]);

        $this->get(route('candidate.public-attempts.show', $invitation->token))
            ->assertOk();

        $response = $this->postJson(route('candidate.public-attempts.coding-questions.run', $invitation->token), [
            'question_id' => $question->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log("cba");',
        ]);

        $response->assertOk()
            ->assertJsonPath('run.summary.passed', 1);
        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $testOverrides
     * @return array{0: User, 1: Test, 2: Invitation}
     */
    private function acceptedOrganizationInvitation(array $testOverrides = []): array
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            ...$testOverrides,
        ]);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        return [$candidate, $test, $invitation];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: Test}
     */
    private function publishedOrganizationTest(array $overrides = []): array
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            ...$overrides,
        ]);

        return [$admin, $test];
    }

    /**
     * @return array{0: Question, 1: QuestionOption, 2: QuestionOption}
     */
    private function questionWithOptions(Test $test): array
    {
        $question = Question::factory()->create([
            'test_id' => $test->id,
            'type' => QuestionType::Mcq->value,
            'body' => 'What is Laravel?',
            'marks' => 1,
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
            'order' => 2,
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
            'input' => 'hidden-input',
            'expected_output' => 'hidden-output',
            'is_hidden' => true,
            'sort_order' => 2,
        ]);

        return $question;
    }

    private function startAttempt(User $candidate, Test $test): TestAttempt
    {
        $this->actingAs($candidate)
            ->post(route('candidate.tests.attempts.store', $test));

        return TestAttempt::query()
            ->where('test_id', $test->id)
            ->where('candidate_user_id', $candidate->id)
            ->firstOrFail();
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
