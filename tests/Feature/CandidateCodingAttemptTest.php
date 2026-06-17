<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\CodingDifficulty;
use App\Enums\InvitationStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\CodeExecution\CodeExecutionService;
use App\Services\CodeExecution\FakeCodeExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CandidateCodingAttemptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(CodeExecutionService::class, FakeCodeExecutionService::class);
    }

    public function test_candidate_attempt_page_includes_coding_questions(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $this->questionWithOptions($test);
        $codingQuestion = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Show')
            ->where('questions.1.id', $codingQuestion->id)
            ->where('questions.1.type', QuestionType::Coding->value)
            ->where('questions.1.supported_languages.0', 'javascript')
            ->where('questions.1.starter_code.javascript', 'function solve(input) {}')
            ->where('questions.1.visible_test_cases.0.input', 'abc')
            ->where('questions.1.visible_test_cases.0.expected_output', 'cba'));
    }

    public function test_candidate_attempt_page_does_not_expose_hidden_test_cases(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $this->questionWithOptions($test);
        $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Show')
            ->has('questions.1.visible_test_cases', 1)
            ->missing('questions.1.visible_test_cases.1')
            ->missing('questions.1.visible_test_cases.0.is_hidden'));
    }

    public function test_candidate_can_save_coding_answer_for_own_in_progress_attempt(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("cba");',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log("cba");',
            'selected_option_id' => null,
            'is_correct' => false,
            'score' => 0,
        ]);
    }

    public function test_candidate_can_update_coding_answer(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'old code',
            ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'python',
                'submitted_code' => 'print("cba")',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('attempt_answers', 1);
        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'language' => 'python',
            'submitted_code' => 'print("cba")',
        ]);
    }

    public function test_candidate_can_resume_and_see_saved_coding_answer(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $this->questionWithOptions($test);
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'python',
                'submitted_code' => 'print("cba")',
            ]);

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Show')
            ->where('questions.1.saved_answer.language', 'python')
            ->where('questions.1.saved_answer.submitted_code', 'print("cba")'));
    }

    public function test_candidate_cannot_save_coding_answer_for_another_candidates_attempt(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($otherCandidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("no");',
            ])
            ->assertForbidden();
    }

    public function test_candidate_cannot_save_coding_answer_after_submission(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);
        $attempt->update(['status' => AttemptStatus::Submitted]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("late");',
            ])
            ->assertForbidden();
    }

    public function test_candidate_cannot_save_coding_answer_after_expiry(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);
        $attempt->update(['expires_at' => now()->subSecond()]);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("late");',
            ]);

        $response->assertSessionHasErrors('attempt');
    }

    public function test_candidate_cannot_save_mcq_question_through_coding_endpoint(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$mcqQuestion] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $mcqQuestion->id,
                'language' => 'javascript',
                'submitted_code' => 'console.log("no");',
            ]);

        $response->assertSessionHasErrors('question_id');
    }

    public function test_candidate_cannot_save_unsupported_language(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $question = $this->codingQuestion($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.coding-answers.save', $attempt), [
                'question_id' => $question->id,
                'language' => 'java',
                'submitted_code' => 'class Main {}',
            ]);

        $response->assertSessionHasErrors('language');
    }

    public function test_submitting_mcq_attempt_does_not_delete_saved_coding_answers(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$mcqQuestion, $correctOption] = $this->questionWithOptions($test, marks: 2);
        $codingQuestion = $this->codingQuestion($test);
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

        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $codingQuestion->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log("cba");',
            'score' => 5,
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('test_attempts', [
            'id' => $attempt->id,
            'score' => 7,
            'max_score' => 7,
            'total_marks' => 7,
        ]);
    }

    public function test_public_candidate_can_save_coding_answer_without_authentication(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $question = $this->codingQuestion($test);
        $invitation = Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => null,
            'email' => 'public@example.com',
            'name' => 'Public Candidate',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
            'policy_accepted_at' => now(),
        ]);
        CandidateTestDetail::create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'name' => 'Public Candidate',
            'email' => 'public@example.com',
        ]);

        $this->get(route('candidate.public-attempts.show', $invitation->token))
            ->assertOk();

        $attempt = $invitation->refresh()->attempt()->firstOrFail();

        $this->post(route('candidate.public-attempts.coding-answers.save', $invitation->token), [
            'question_id' => $question->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log("cba");',
        ])->assertRedirect();

        $this->assertGuest();
        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'language' => 'javascript',
            'submitted_code' => 'console.log("cba");',
        ]);
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
            'input' => 'hidden',
            'expected_output' => 'neddih',
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
