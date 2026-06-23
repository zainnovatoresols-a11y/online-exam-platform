<?php

namespace Tests\Feature;

use App\Actions\Attempts\GradeCodingQuestion;
use App\Enums\AttemptStatus;
use App\Enums\CodingDifficulty;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Jobs\GradeAttemptCodingAnswers;
use App\Models\CodeExecutionRun;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\CodeExecution\CodeExecutionService;
use App\Services\CodeExecution\FakeCodeExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QueuedFinalCodingGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['code_execution.queue_final_grading' => true]);
        $this->app->bind(CodeExecutionService::class, FakeCodeExecutionService::class);
    }

    public function test_final_submit_queues_coding_grading_and_returns_with_provisional_score(): void
    {
        Queue::fake();

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
            ])
            ->assertRedirect(route('candidate.attempts.show', $attempt));

        $attempt->refresh();
        $this->assertSame(AttemptStatus::Submitted, $attempt->status);
        $this->assertSame(0, $attempt->score);
        $this->assertSame(5, $attempt->max_score);

        $this->assertDatabaseHas('code_execution_runs', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'run_type' => 'final',
            'status' => 'queued',
        ]);

        Queue::assertPushed(
            GradeAttemptCodingAnswers::class,
            fn (GradeAttemptCodingAnswers $job): bool => $job->attemptId === $attempt->id,
        );
    }

    public function test_queued_job_grades_coding_answers_and_refreshes_attempt_score(): void
    {
        Queue::fake();

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

        (new GradeAttemptCodingAnswers($attempt->id))->handle(app(GradeCodingQuestion::class));

        $attempt->refresh();
        $this->assertSame(5, $attempt->score);
        $this->assertSame(5, $attempt->max_score);
        $this->assertSame('100.00', (string) $attempt->percentage);
        $this->assertTrue($attempt->passed);

        $run = CodeExecutionRun::query()
            ->where('test_attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->where('run_type', 'final')
            ->firstOrFail();

        $this->assertSame('completed', $run->status);
        $this->assertSame('5.00', (string) $run->score_awarded);
        $this->assertSame(2, $run->testCaseResults()->count());
    }

    /**
     * @return array{0: User, 1: Test, 2: Invitation}
     */
    private function acceptedOrganizationInvitation(): array
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
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

    private function codingQuestion(Test $test, int $marks = 5): Question
    {
        $question = $test->questions()->create([
            'type' => QuestionType::Coding->value,
            'body' => 'Reverse a string.',
            'marks' => $marks,
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
