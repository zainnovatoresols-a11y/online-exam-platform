<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\UserRole;
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

class CandidateMcqAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_start_attempt_after_accepting_invitation(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $this->questionWithOptions($test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.tests.attempts.store', $test));

        $attempt = TestAttempt::firstOrFail();

        $response->assertRedirect(route('candidate.attempts.show', $attempt));
        $this->assertDatabaseHas('test_attempts', [
            'test_id' => $test->id,
            'candidate_user_id' => $candidate->id,
            'status' => AttemptStatus::InProgress->value,
        ]);
    }

    public function test_candidate_can_start_solo_admin_test_attempt_after_accepting_invitation(): void
    {
        [$candidate, $test] = $this->acceptedSoloInvitation();
        $this->questionWithOptions($test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.tests.attempts.store', $test));

        $attempt = TestAttempt::firstOrFail();

        $response->assertRedirect(route('candidate.attempts.show', $attempt));
        $this->assertDatabaseHas('test_attempts', [
            'test_id' => $test->id,
            'candidate_user_id' => $candidate->id,
        ]);
    }

    public function test_candidate_cannot_start_attempt_without_accepted_invitation(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = Test::factory()->published()->create();
        $this->questionWithOptions($test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.tests.attempts.store', $test));

        $response->assertForbidden();
        $this->assertDatabaseCount('test_attempts', 0);
    }

    public function test_candidate_can_view_mcq_questions_for_started_attempt(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, $correctOption] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Show')
            ->where('test.id', $test->id)
            ->where('questions.0.id', $question->id)
            ->where('questions.0.options.0.id', $correctOption->id)
            ->missing('questions.0.options.0.is_correct'));
    }

    public function test_candidate_cannot_view_another_candidates_attempt(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($otherCandidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertForbidden();
    }

    public function test_candidate_can_submit_correct_mcq_answers_and_get_score(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, $correctOption] = $this->questionWithOptions($test, marks: 2);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
            ]);

        $response->assertRedirect(route('candidate.attempts.show', $attempt));
        $this->assertDatabaseHas('test_attempts', [
            'id' => $attempt->id,
            'status' => AttemptStatus::Submitted->value,
            'score' => 2,
            'total_marks' => 2,
        ]);
        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correctOption->id,
            'is_correct' => true,
            'score' => 2,
        ]);
    }

    public function test_candidate_gets_zero_for_wrong_mcq_answer(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, , $wrongOption] = $this->questionWithOptions($test, marks: 2);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [
                    $question->id => $wrongOption->id,
                ],
            ]);

        $this->assertDatabaseHas('test_attempts', [
            'id' => $attempt->id,
            'status' => AttemptStatus::Submitted->value,
            'score' => 0,
            'total_marks' => 2,
        ]);
    }

    public function test_candidate_cannot_submit_option_that_belongs_to_another_question(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question] = $this->questionWithOptions($test);
        [, $otherCorrectOption] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [
                    $question->id => $otherCorrectOption->id,
                ],
            ]);

        $response->assertSessionHasErrors("answers.{$question->id}");
        $this->assertDatabaseMissing('test_attempts', [
            'id' => $attempt->id,
            'status' => AttemptStatus::Submitted->value,
        ]);
    }

    public function test_submitted_attempt_cannot_be_submitted_again(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, $correctOption] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
            ]);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt->refresh()), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
            ]);

        $response->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Test}
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

        Invitation::factory()->create([
            'organization_id' => $organization->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        return [$candidate, $test];
    }

    /**
     * @return array{0: User, 1: Test}
     */
    private function acceptedSoloInvitation(): array
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = Test::factory()->published()->create([
            'organization_id' => null,
            'created_by_id' => $admin->id,
        ]);

        Invitation::factory()->create([
            'organization_id' => null,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        return [$candidate, $test];
    }

    /**
     * @return array{0: Question, 1: QuestionOption, 2: QuestionOption}
     */
    private function questionWithOptions(Test $test, int $marks = 1): array
    {
        $question = Question::factory()->create([
            'test_id' => $test->id,
            'body' => 'What is Laravel?',
            'marks' => $marks,
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
