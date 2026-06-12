<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\TestStatus;
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
        [$candidate, $test, $invitation] = $this->acceptedOrganizationInvitation();
        $this->questionWithOptions($test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.tests.attempts.store', $test));

        $attempt = TestAttempt::firstOrFail();

        $response->assertRedirect(route('candidate.attempts.show', $attempt));
        $this->assertDatabaseHas('test_attempts', [
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'candidate_user_id' => $candidate->id,
            'organization_id' => $test->organization_id,
            'status' => AttemptStatus::InProgress->value,
        ]);
        $this->assertNotNull($attempt->started_at);
        $this->assertNotNull($attempt->expires_at);
        $this->assertEqualsWithDelta(
            $test->duration_minutes * 60,
            $attempt->started_at->diffInSeconds($attempt->expires_at),
            1,
        );
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
            'organization_id' => null,
        ]);
    }

    public function test_candidate_cannot_start_draft_or_closed_test_even_with_accepted_invitation(): void
    {
        [$draftCandidate, $draftTest] = $this->acceptedOrganizationInvitation([
            'status' => TestStatus::Draft->value,
            'published_at' => null,
        ]);
        [$closedCandidate, $closedTest] = $this->acceptedOrganizationInvitation([
            'status' => TestStatus::Closed->value,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
        $this->questionWithOptions($draftTest);
        $this->questionWithOptions($closedTest);

        $draftResponse = $this->actingAs($draftCandidate)
            ->post(route('candidate.tests.attempts.store', $draftTest));
        $closedResponse = $this->actingAs($closedCandidate)
            ->post(route('candidate.tests.attempts.store', $closedTest));

        $draftResponse->assertForbidden();
        $closedResponse->assertForbidden();
        $this->assertDatabaseCount('test_attempts', 0);
    }

    public function test_candidate_cannot_create_multiple_attempts_for_same_test(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        $this->questionWithOptions($test);

        $this->actingAs($candidate)
            ->post(route('candidate.tests.attempts.store', $test));
        $firstAttempt = TestAttempt::firstOrFail();

        $response = $this->actingAs($candidate)
            ->post(route('candidate.tests.attempts.store', $test));

        $response->assertRedirect(route('candidate.attempts.show', $firstAttempt));
        $this->assertDatabaseCount('test_attempts', 1);
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
            ->where('attempt.expires_at', $attempt->expires_at?->toISOString())
            ->missing('questions.0.options.0.is_correct'));
    }

    public function test_candidate_can_save_answers_before_submission(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, $correctOption] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.answers.save', $attempt), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correctOption->id,
            'is_correct' => false,
            'score' => 0,
        ]);
        $this->assertSame(AttemptStatus::InProgress, $attempt->refresh()->status);
    }

    public function test_saved_answers_are_loaded_when_candidate_resumes_attempt(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, $correctOption] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.answers.save', $attempt), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
            ]);

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Show')
            ->where("saved_answers.{$question->id}", $correctOption->id));
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
            'max_score' => 2,
            'total_marks' => 2,
        ]);
        $attempt->refresh();
        $this->assertSame('100.00', (string) $attempt->percentage);
        $this->assertTrue($attempt->passed);
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
            'max_score' => 2,
            'total_marks' => 2,
        ]);
        $attempt->refresh();
        $this->assertSame('0.00', (string) $attempt->percentage);
        $this->assertFalse($attempt->passed);
    }

    public function test_candidate_cannot_save_answers_after_attempt_expires(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, $correctOption] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);
        $attempt->update(['expires_at' => now()->subSecond()]);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.answers.save', $attempt), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
            ]);

        $response->assertSessionHasErrors('attempt');
        $this->assertDatabaseMissing('attempt_answers', [
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_candidate_cannot_submit_after_attempt_expires(): void
    {
        [$candidate, $test] = $this->acceptedOrganizationInvitation();
        [$question, $correctOption] = $this->questionWithOptions($test);
        $attempt = $this->startAttempt($candidate, $test);
        $attempt->update(['expires_at' => now()->subSecond()]);

        $response = $this->actingAs($candidate)
            ->post(route('candidate.attempts.submit', $attempt), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
            ]);

        $response->assertSessionHasErrors('attempt');
        $this->assertDatabaseHas('test_attempts', [
            'id' => $attempt->id,
            'status' => AttemptStatus::InProgress->value,
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
     * @param array<string, mixed> $testOverrides
     *
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
