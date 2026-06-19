<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PublicCandidateRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_policy_page_loads_for_published_test(): void
    {
        [, $test] = $this->publishedOrganizationTest();

        $response = $this->get(route('candidate.public-tests.policy', $test->public_token));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/PublicTests/Policy')
            ->where('test.title', $test->title));
    }

    public function test_public_policy_page_shows_status_for_unpublished_test(): void
    {
        $test = Test::factory()->create();

        $response = $this->get(route('candidate.public-tests.policy', $test->public_token));

        $response->assertForbidden();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/PublicTests/Status')
            ->where('status', 'not_published')
            ->where('message', 'This test has not been published yet.')
            ->where('test.title', $test->title));
    }

    public function test_admin_can_queue_bulk_email_invitations_that_use_public_test_link(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.invitations.store', $test), [
                'emails' => "first@example.com\nsecond@example.com",
                'starts_at' => now()->subMinute()->toDateTimeString(),
            ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'first@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'second@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
    }

    public function test_bulk_email_invitations_skip_invalid_addresses_and_queue_valid_ones(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.invitations.store', $test), [
                'emails' => "valid@example.com\nnot-an-email",
                'starts_at' => now()->subMinute()->toDateTimeString(),
            ]);

        $response
            ->assertRedirect(route('admin.tests.invitations.index', $test))
            ->assertSessionHas('warning');
        $this->assertStringContainsString('not-an-email', session('warning'));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'valid@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
        $this->assertDatabaseMissing('invitations', [
            'test_id' => $test->id,
            'email' => 'not-an-email',
        ]);
    }

    public function test_bulk_email_invitations_skip_duplicate_addresses(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.invitations.store', $test), [
                'emails' => "duplicate@example.com\nduplicate@example.com",
                'starts_at' => now()->subMinute()->toDateTimeString(),
            ]);

        $response
            ->assertRedirect(route('admin.tests.invitations.index', $test))
            ->assertSessionHas('warning');
        $this->assertSame(1, Invitation::query()
            ->where('test_id', $test->id)
            ->where('email', 'duplicate@example.com')
            ->count());
        $this->assertStringContainsString('duplicate@example.com', session('warning'));
    }

    public function test_admin_can_queue_valid_invitations_from_csv_and_skip_invalid_rows(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.invitations.store', $test), [
                'email_csv' => UploadedFile::fake()->createWithContent(
                    'candidates.csv',
                    "name,email\nCandidate One,csv-one@example.com\nCandidate Bad,not-an-email\nCandidate Two,csv-two@example.com\n",
                ),
                'starts_at' => now()->subMinute()->toDateTimeString(),
            ]);

        $response
            ->assertRedirect(route('admin.tests.invitations.index', $test))
            ->assertSessionHas('warning');
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'csv-one@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'csv-two@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
        $this->assertDatabaseMissing('invitations', [
            'test_id' => $test->id,
            'email' => 'not-an-email',
        ]);
        $this->assertStringContainsString('not-an-email', session('warning'));
    }

    public function test_csv_invitation_upload_uses_file_extension_not_browser_mime_guess(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();
        $path = tempnam(sys_get_temp_dir(), 'candidate-emails-');

        file_put_contents($path, "email\nmime-test@example.com\n");

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.invitations.store', $test), [
                'email_csv' => new UploadedFile(
                    $path,
                    'candidate-emails.csv',
                    'application/octet-stream',
                    null,
                    true,
                ),
                'starts_at' => now()->subMinute()->toDateTimeString(),
            ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'mime-test@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
    }

    public function test_invited_email_can_accept_policy_submit_details_and_complete_test_without_authentication(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest([
            'candidate_fields' => ['phone', 'stack_name'],
        ]);
        [, $correctOption] = $this->questionWithOptions($test);
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'invited@example.com',
            'status' => InvitationStatus::Sent,
            'starts_at' => now()->subMinute(),
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token), [
            'email' => 'invited@example.com',
        ])->assertRedirect(route('candidate.public-tests.register', [
            'publicToken' => $test->public_token,
            'email' => 'invited@example.com',
        ]));

        $response = $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Invited Candidate',
            'email' => 'invited@example.com',
            'phone' => '123456789',
            'stack_name' => 'Laravel',
        ]);

        $invitation = Invitation::where('email', 'invited@example.com')->firstOrFail();
        $attempt = TestAttempt::where('invitation_id', $invitation->id)->firstOrFail();

        $response->assertRedirect(route('candidate.public-attempts.show', $invitation->token));
        $this->assertGuest();
        $this->assertSame(InvitationStatus::Accepted, $invitation->status);
        $this->assertNull($invitation->candidate_user_id);
        $this->assertNotNull($invitation->policy_accepted_at);
        $this->assertDatabaseMissing('users', [
            'email' => 'invited@example.com',
        ]);
        $this->assertDatabaseHas('candidate_test_details', [
            'invitation_id' => $invitation->id,
            'test_attempt_id' => $attempt->id,
            'name' => 'Invited Candidate',
            'email' => 'invited@example.com',
            'phone' => '123456789',
            'stack_name' => 'Laravel',
        ]);
        $this->assertDatabaseHas('test_attempts', [
            'id' => $attempt->id,
            'test_id' => $test->id,
            'candidate_user_id' => null,
            'status' => AttemptStatus::InProgress->value,
        ]);

        $this->get(route('candidate.public-attempts.show', $invitation->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidate/Attempts/Show')
                ->where('attempt.is_public', true)
                ->where('attempt.access_token', $invitation->token));

        $this->post(route('candidate.public-attempts.submit', $invitation->token), [
            'answers' => [
                $correctOption->question_id => $correctOption->id,
            ],
        ])->assertRedirect(route('candidate.public-attempts.show', $invitation->token));

        $this->assertSame(AttemptStatus::Submitted, $attempt->refresh()->status);

        $this->get(route('candidate.public-attempts.show', $invitation->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidate/Attempts/Result')
                ->where('attempt.is_public', true)
                ->where('attempt.status', AttemptStatus::Submitted->value)
                ->missing('attempt.score')
                ->missing('attempt.max_score')
                ->missing('attempt.percentage')
                ->missing('attempt.passed')
                ->missing('answers'));
    }

    public function test_public_access_enabled_allows_any_email_to_register(): void
    {
        [, $test] = $this->publishedSoloTest([
            'public_access_enabled' => true,
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token));

        $response = $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Open Candidate',
            'email' => 'open@example.com',
            'phone' => null,
            'stack_name' => null,
        ]);

        $invitation = Invitation::where('email', 'open@example.com')->firstOrFail();

        $response->assertRedirect(route('candidate.public-attempts.show', $invitation->token));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'open@example.com',
        ]);
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'organization_id' => null,
            'email' => 'open@example.com',
            'candidate_user_id' => null,
            'status' => InvitationStatus::Accepted->value,
        ]);
        $this->assertDatabaseHas('candidate_test_details', [
            'invitation_id' => $invitation->id,
            'name' => 'Open Candidate',
            'email' => 'open@example.com',
        ]);
    }

    public function test_candidate_sees_countdown_before_details_form_until_invitation_start_time(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $startsAt = now()->addMinutes(10)->setMicrosecond(0);
        $invitation = Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'future@example.com',
            'status' => InvitationStatus::Sent,
            'starts_at' => $startsAt,
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token), [
            'email' => 'future@example.com',
        ])->assertRedirect(route('candidate.public-tests.register', [
            'publicToken' => $test->public_token,
            'email' => 'future@example.com',
        ]));

        $this->get(route('candidate.public-tests.register', [
            'publicToken' => $test->public_token,
            'email' => 'future@example.com',
        ]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidate/PublicTests/Status')
                ->where('status', 'not_started')
                ->where('message', 'This invitation has not started yet.')
                ->where('available_at', $startsAt->toISOString())
                ->where('action_url', route('candidate.public-tests.register', [
                    'publicToken' => $test->public_token,
                    'email' => 'future@example.com',
                    'invite' => $invitation->token,
                ])));

        $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Future Candidate',
            'email' => 'future@example.com',
        ])->assertRedirect(route('candidate.public-tests.register', [
            'publicToken' => $test->public_token,
            'email' => 'future@example.com',
            'invite' => $invitation->token,
        ]));

        $this->assertDatabaseMissing('candidate_test_details', [
            'email' => 'future@example.com',
        ]);
        $this->assertDatabaseMissing('test_attempts', [
            'invitation_id' => $invitation->id,
        ]);
    }

    public function test_candidate_can_reopen_public_link_without_login_form(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $invitation = Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => null,
            'email' => 'resume@example.com',
            'name' => 'Resume Candidate',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
            'policy_accepted_at' => now(),
        ]);
        CandidateTestDetail::create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'name' => 'Resume Candidate',
            'email' => 'resume@example.com',
        ]);

        $response = $this->get(route('candidate.public-tests.policy', [
            'publicToken' => $test->public_token,
            'email' => 'resume@example.com',
            'invite' => $invitation->token,
        ]));

        $response->assertRedirect(route('candidate.public-attempts.show', $invitation->token));
        $this->assertGuest();
    }

    public function test_revoked_invitation_blocks_public_registration_even_when_public_access_is_open(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest([
            'public_access_enabled' => true,
        ]);
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'revoked@example.com',
            'status' => InvitationStatus::Revoked,
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token), [
            'email' => 'revoked@example.com',
        ]);

        $response = $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Revoked Candidate',
            'email' => 'revoked@example.com',
        ]);

        $response->assertForbidden();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/PublicTests/Status')
            ->where('status', 'revoked')
            ->where('message', 'This invitation has been revoked.'));
        $this->assertDatabaseMissing('users', [
            'email' => 'revoked@example.com',
        ]);
        $this->assertDatabaseMissing('candidate_test_details', [
            'email' => 'revoked@example.com',
        ]);
    }

    public function test_public_access_disabled_blocks_uninvited_email(): void
    {
        [, $test] = $this->publishedOrganizationTest([
            'public_access_enabled' => false,
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token));

        $response = $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Blocked Candidate',
            'email' => 'blocked@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.com',
        ]);
    }

    public function test_required_candidate_fields_are_enforced_per_test(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest([
            'candidate_fields' => ['phone'],
        ]);
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'needs-phone@example.com',
            'status' => InvitationStatus::Sent,
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token));

        $response = $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Needs Phone',
            'email' => 'needs-phone@example.com',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_public_registration_does_not_require_candidate_password(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'passwordless@example.com',
            'status' => InvitationStatus::Sent,
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token));

        $response = $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Passwordless Candidate',
            'email' => 'passwordless@example.com',
        ]);

        $invitation = Invitation::where('email', 'passwordless@example.com')->firstOrFail();

        $response->assertRedirect(route('candidate.public-attempts.show', $invitation->token));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'passwordless@example.com',
        ]);
        $this->assertDatabaseHas('candidate_test_details', [
            'invitation_id' => $invitation->id,
            'name' => 'Passwordless Candidate',
            'email' => 'passwordless@example.com',
        ]);
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
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: Test}
     */
    private function publishedSoloTest(array $overrides = []): array
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = Test::factory()->published()->create([
            'organization_id' => null,
            'created_by_id' => $admin->id,
            ...$overrides,
        ]);

        return [$admin, $test];
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
