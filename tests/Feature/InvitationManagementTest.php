<?php

namespace Tests\Feature;

use App\Enums\InvitationStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvitationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_invitation_for_a_published_organization_test_in_their_organization(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'name' => 'Candidate One',
            'email' => 'candidate-one@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'organization_id' => $test->organization_id,
            'invited_by' => $admin->id,
            'email' => 'candidate-one@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
    }

    public function test_admin_cannot_invite_candidate_to_another_organizations_test(): void
    {
        Notification::fake();
        $admin = $this->userWithRole(UserRole::Admin, Organization::factory()->create());
        $otherOrganization = Organization::factory()->create();
        $test = Test::factory()->published()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'blocked@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('invitations', [
            'email' => 'blocked@example.com',
        ]);
    }

    public function test_admin_can_create_invitation_for_their_own_published_solo_test(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedSoloTest();

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'solo-candidate@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'organization_id' => null,
            'invited_by' => $admin->id,
            'email' => 'solo-candidate@example.com',
        ]);
    }

    public function test_admin_cannot_invite_candidate_to_another_admins_solo_test(): void
    {
        Notification::fake();
        $owner = $this->userWithRole(UserRole::Admin);
        $otherAdmin = $this->userWithRole(UserRole::Admin);
        $test = Test::factory()->published()->create([
            'organization_id' => null,
            'created_by_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherAdmin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'blocked-solo@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('invitations', [
            'email' => 'blocked-solo@example.com',
        ]);
    }

    public function test_admin_can_invite_candidate_to_draft_test(): void
    {
        Notification::fake();
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Draft->value,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'draft@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'organization_id' => $organization->id,
            'email' => 'draft@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
    }

    public function test_admin_can_invite_candidate_to_closed_test(): void
    {
        Notification::fake();
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Closed->value,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'closed@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'organization_id' => $organization->id,
            'email' => 'closed@example.com',
            'status' => InvitationStatus::Sent->value,
        ]);
    }

    public function test_duplicate_pending_invite_for_same_test_and_email_is_rejected(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'duplicate@example.com',
            'status' => InvitationStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'duplicate@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_bulk_invite_from_csv_file_without_trusting_browser_mime_type(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();
        $csv = UploadedFile::fake()->createWithContent(
            'candidates.csv',
            "email\nfirst@example.com\nsecond@example.com\n",
        );

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email_csv' => $csv,
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'first@example.com',
        ]);
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => 'second@example.com',
        ]);
    }

    public function test_invitation_organization_id_is_copied_from_organization_test(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'org-copy@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $invitation = Invitation::where('email', 'org-copy@example.com')->firstOrFail();

        $this->assertSame($test->organization_id, $invitation->organization_id);
    }

    public function test_invitation_organization_id_is_null_for_solo_admin_test(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedSoloTest();

        $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'solo-null@example.com',
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $invitation = Invitation::where('email', 'solo-null@example.com')->firstOrFail();

        $this->assertNull($invitation->organization_id);
    }

    public function test_admin_cannot_override_invitation_organization_id_through_request_data(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedSoloTest();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'email' => 'override@example.com',
            'organization_id' => $organization->id,
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $invitation = Invitation::where('email', 'override@example.com')->firstOrFail();

        $this->assertNull($invitation->organization_id);
    }

    public function test_invitation_token_page_loads_for_valid_pending_invitation(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $invitation = $this->pendingInvitation($test, $admin);

        $response = $this->get(route('candidate.invitations.show', $invitation->token));

        $response->assertRedirect(route('candidate.public-tests.policy', [
            'publicToken' => $test->public_token,
            'email' => $invitation->email,
            'invite' => $invitation->token,
        ]));
    }

    public function test_invalid_token_shows_not_found_or_invalid_page(): void
    {
        $response = $this->get(route('candidate.invitations.show', 'not-a-real-token'));

        $response->assertNotFound();
    }

    public function test_expired_invite_cannot_be_accepted(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $invitation = $this->pendingInvitation($test, $admin, [
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->post(route('candidate.invitations.accept', $invitation->token));

        $response->assertForbidden();
        $this->assertSame(InvitationStatus::Expired, $invitation->refresh()->status);
    }

    public function test_revoked_invite_cannot_be_accepted(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $invitation = $this->pendingInvitation($test, $admin, [
            'status' => InvitationStatus::Revoked,
            'revoked_at' => now(),
        ]);

        $response = $this->post(route('candidate.invitations.accept', $invitation->token));

        $response->assertForbidden();
    }

    public function test_legacy_invitation_accept_redirects_to_public_policy_flow(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $invitation = $this->pendingInvitation($test, $admin, [
            'email' => 'new-candidate@example.com',
        ]);

        $response = $this->post(route('candidate.invitations.accept', $invitation->token));

        $response->assertRedirect(route('candidate.public-tests.policy', [
            'publicToken' => $test->public_token,
            'email' => $invitation->email,
            'invite' => $invitation->token,
        ]));
    }

    public function test_public_invitation_acceptance_stores_candidate_details_without_user_account(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $invitation = $this->pendingInvitation($test, $admin, [
            'email' => 'accepted-candidate@example.com',
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token), [
            'email' => $invitation->email,
            'invitation_token' => $invitation->token,
        ]);
        $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Accepted Candidate',
            'email' => $invitation->email,
            'invitation_token' => $invitation->token,
        ]);

        $invitation->refresh();

        $this->assertSame(InvitationStatus::Accepted, $invitation->status);
        $this->assertNull($invitation->candidate_user_id);
        $this->assertNotNull($invitation->accepted_at);
        $this->assertDatabaseMissing('users', [
            'email' => 'accepted-candidate@example.com',
        ]);
        $this->assertDatabaseHas('candidate_test_details', [
            'invitation_id' => $invitation->id,
            'name' => 'Accepted Candidate',
            'email' => 'accepted-candidate@example.com',
        ]);
        $this->assertNotNull(CandidateTestDetail::where('invitation_id', $invitation->id)->first()?->test_attempt_id);
    }

    public function test_candidate_can_view_organization_test_landing_page_after_accepted_invitation(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $candidate = $this->userWithRole(UserRole::Candidate);
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($candidate)->get(route('candidate.tests.show', $test));

        $response->assertOk();
    }

    public function test_candidate_can_view_solo_admin_test_landing_page_after_accepted_invitation(): void
    {
        [$admin, $test] = $this->publishedSoloTest();
        $candidate = $this->userWithRole(UserRole::Candidate);
        Invitation::factory()->create([
            'organization_id' => null,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($candidate)->get(route('candidate.tests.show', $test));

        $response->assertOk();
    }

    public function test_candidate_cannot_view_test_landing_page_without_accepted_invitation(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $candidate = $this->userWithRole(UserRole::Candidate);
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => InvitationStatus::Pending,
        ]);

        $response = $this->actingAs($candidate)->get(route('candidate.tests.show', $test));

        $response->assertForbidden();
    }

    public function test_candidate_cannot_view_another_candidates_invited_test(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        $invitedCandidate = $this->userWithRole(UserRole::Candidate);
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $invitedCandidate->id,
            'email' => $invitedCandidate->email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($otherCandidate)->get(route('candidate.tests.show', $test));

        $response->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Test}
     */
    private function publishedOrganizationTest(): array
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
        ]);

        return [$admin, $test];
    }

    /**
     * @return array{0: User, 1: Test}
     */
    private function publishedSoloTest(): array
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = Test::factory()->published()->create([
            'organization_id' => null,
            'created_by_id' => $admin->id,
        ]);

        return [$admin, $test];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function pendingInvitation(Test $test, User $admin, array $overrides = []): Invitation
    {
        return Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'status' => InvitationStatus::Pending,
            ...$overrides,
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
