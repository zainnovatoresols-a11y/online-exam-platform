<?php

namespace Tests\Feature;

use App\Data\CodeExecution\CodeRunResult;
use App\Enums\AttemptStatus;
use App\Enums\CodingDifficulty;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\CodeExecutionRun;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\CodeExecution\CodeExecutionException;
use App\Services\CodeExecution\CodeExecutionService;
use App\Services\CodeExecution\FakeCodeExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinalCodingSubmitGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(CodeExecutionService::class, FakeCodeExecutionService::class);
    }

    public function test_coding_only_attempt_is_scored_against_visible_and_hidden_cases(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test, marks: 5);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [],
            ]);

        $response->assertRedirect(route('candidate.attempts.show', $attempt));

        $attempt->refresh();
        $this->assertSame(AttemptStatus::Submitted, $attempt->status);
        $this->assertSame(5, $attempt->score);
        $this->assertSame(5, $attempt->max_score);
        $this->assertSame(5, $attempt->total_marks);
        $this->assertSame('100.00', (string) $attempt->percentage);
        $this->assertTrue($attempt->passed);

        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'language' => 'javascript',
            'score' => 5,
            'is_correct' => true,
        ]);

        $run = CodeExecutionRun::query()->where('run_type', 'final')->firstOrFail();
        $this->assertSame('completed', $run->status);
        $this->assertSame('5.00', (string) $run->score_awarded);
        $this->assertSame('5.00', (string) $run->max_score);
        $this->assertTrue($run->passed);
        $this->assertSame(2, $run->testCaseResults()->count());
        $this->assertDatabaseHas('code_execution_test_case_results', [
            'code_execution_run_id' => $run->id,
            'is_hidden' => true,
            'input' => 'hidden-input',
            'expected_output' => 'hidden-output',
            'passed' => true,
        ]);
    }

    public function test_mixed_mcq_and_coding_attempt_final_score_includes_both_question_types(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$mcqQuestion, $correctOption] = $this->questionWithOptions($test, marks: 2);
        $codingQuestion = $this->codingQuestion($test, marks: 5);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $codingQuestion->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [
                    $mcqQuestion->id => $correctOption->id,
                ],
            ])
            ->assertRedirect(route('candidate.attempts.show', $attempt));

        $attempt->refresh();
        $this->assertSame(7, $attempt->score);
        $this->assertSame(7, $attempt->max_score);
        $this->assertSame('100.00', (string) $attempt->percentage);
        $this->assertTrue($attempt->passed);
    }

    public function test_coding_question_receives_partial_score_when_hidden_case_fails(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation([
            'pass_mark' => 80,
        ]);
        $question = $this->codingQuestion($test, marks: 5);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => '__FAIL_HIDDEN__',
            ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [],
            ])
            ->assertRedirect(route('candidate.attempts.show', $attempt));

        $attempt->refresh();
        $this->assertSame(3, $attempt->score);
        $this->assertSame(5, $attempt->max_score);
        $this->assertFalse($attempt->passed);
        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'score' => 3,
            'is_correct' => false,
        ]);
        $this->assertDatabaseHas('code_execution_runs', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'run_type' => 'final',
            'status' => 'completed_with_failures',
            'passed' => false,
        ]);
    }

    public function test_coding_question_without_saved_code_is_rejected_before_submit(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test, marks: 5);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [],
            ])
            ->assertSessionHasErrors([
                "coding_answers.{$question->id}" => 'Please write and save code before submitting this question.',
            ]);

        $attempt->refresh();
        $this->assertSame(AttemptStatus::InProgress, $attempt->status);
        $this->assertSame(0, $attempt->score);
        $this->assertSame(0, $attempt->max_score);
        $this->assertNull($attempt->passed);
        $this->assertDatabaseMissing('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);
        $this->assertDatabaseMissing('code_execution_runs', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'run_type' => 'final',
        ]);
    }

    public function test_provider_unavailable_prevents_submit_and_keeps_attempt_in_progress(): void
    {
        $this->app->bind(CodeExecutionService::class, UnavailableCodeExecutionService::class);

        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test, marks: 5);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $response = $this->actingAs($candidate)
            ->from(route('candidate.attempts.show', $attempt))
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [],
            ]);

        $response->assertRedirect(route('candidate.attempts.show', $attempt))
            ->assertSessionHasErrors([
                'attempt' => 'Code execution is temporarily unavailable. Please try submitting again.',
            ]);

        $attempt->refresh();
        $this->assertSame(AttemptStatus::InProgress, $attempt->status);
        $this->assertNull($attempt->submitted_at);
        $this->assertSame(0, $attempt->score);
    }

    public function test_candidate_code_failure_scores_zero_but_still_submits(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test, marks: 5);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => '__FAIL__',
            ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [],
            ])
            ->assertRedirect(route('candidate.attempts.show', $attempt));

        $attempt->refresh();
        $this->assertSame(AttemptStatus::Submitted, $attempt->status);
        $this->assertSame(0, $attempt->score);
        $this->assertDatabaseHas('code_execution_runs', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'run_type' => 'final',
            'status' => 'completed_with_failures',
        ]);
    }

    public function test_candidate_result_does_not_expose_hidden_case_details(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test, marks: 5);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [],
            ]);

        $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt->refresh()))
            ->assertOk()
            ->assertDontSee('hidden-input')
            ->assertDontSee('hidden-output')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidate/Attempts/Result')
                ->missing('answers')
                ->missing('code_execution_runs'));
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
     * @return array{0: Question, 1: QuestionOption, 2: QuestionOption}
     */
    private function questionWithOptions(Test $test, int $marks = 1): array
    {
        $question = Question::factory()->create([
            'test_id' => $test->id,
            'type' => QuestionType::Mcq->value,
            'body' => 'What is Laravel?',
            'marks' => $marks,
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

    private function codingQuestion(Test $test, int $marks = 5): Question
    {
        $question = $test->questions()->create([
            'type' => QuestionType::Coding->value,
            'body' => 'Reverse a string.',
            'marks' => $marks,
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

class UnavailableCodeExecutionService implements CodeExecutionService
{
    public function runTestCases(
        string $language,
        string $sourceCode,
        iterable $testCases,
        ?int $timeLimitMs = null,
        ?int $memoryLimitKb = null,
    ): CodeRunResult {
        throw new CodeExecutionException('Runner unavailable.');
    }

    public function runVisibleTestCases(
        string $language,
        string $sourceCode,
        iterable $testCases,
        ?int $timeLimitMs = null,
        ?int $memoryLimitKb = null,
    ): CodeRunResult {
        return $this->runTestCases($language, $sourceCode, $testCases, $timeLimitMs, $memoryLimitKb);
    }
}
