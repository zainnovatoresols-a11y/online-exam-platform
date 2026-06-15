<?php

namespace Tests\Feature;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_public_policy_page_is_not_available_for_unpublished_test(): void
    {
        $test = Test::factory()->create();

        $response = $this->get(route('candidate.public-tests.policy', $test->public_token));

        $response->assertNotFound();
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

    public function test_bulk_email_invitations_validate_each_email_address(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.invitations.store', $test), [
                'emails' => "valid@example.com\nnot-an-email",
                'starts_at' => now()->subMinute()->toDateTimeString(),
            ]);

        $response->assertSessionHasErrors('emails');
        $this->assertDatabaseMissing('invitations', [
            'test_id' => $test->id,
            'email' => 'valid@example.com',
        ]);
    }

    public function test_bulk_email_invitations_reject_duplicate_addresses(): void
    {
        Notification::fake();
        [$admin, $test] = $this->publishedOrganizationTest();

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.invitations.store', $test), [
                'emails' => "duplicate@example.com\nduplicate@example.com",
                'starts_at' => now()->subMinute()->toDateTimeString(),
            ]);

        $response->assertSessionHasErrors('emails');
    }

    public function test_invited_email_can_accept_policy_register_and_reach_test_landing(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest([
            'candidate_fields' => ['phone', 'stack_name'],
        ]);
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
            'password' => 'password',
            'password_confirmation' => 'password',
            'stack_name' => 'Laravel',
        ]);

        $candidate = User::where('email', 'invited@example.com')->firstOrFail();
        $invitation = Invitation::where('email', 'invited@example.com')->firstOrFail();

        $response->assertRedirect(route('candidate.tests.show', $test));
        $this->assertTrue($candidate->hasRole(UserRole::Candidate->value));
        $this->assertSame(InvitationStatus::Accepted, $invitation->status);
        $this->assertSame($candidate->id, $invitation->candidate_user_id);
        $this->assertNotNull($invitation->policy_accepted_at);
        $this->assertSame('123456789', $candidate->phone);
        $this->assertSame('Laravel', $candidate->stack_name);
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
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => null,
            'stack_name' => null,
        ]);

        $candidate = User::where('email', 'open@example.com')->firstOrFail();

        $response->assertRedirect(route('candidate.tests.show', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'organization_id' => null,
            'email' => 'open@example.com',
            'candidate_user_id' => $candidate->id,
            'status' => InvitationStatus::Accepted->value,
        ]);
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
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', [
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
            'password' => 'password',
            'password_confirmation' => 'password',
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
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_public_registration_requires_password_confirmation(): void
    {
        [$admin, $test] = $this->publishedOrganizationTest();
        Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'password-required@example.com',
            'status' => InvitationStatus::Sent,
        ]);

        $this->post(route('candidate.public-tests.policy.accept', $test->public_token));

        $response = $this->post(route('candidate.public-tests.register.store', $test->public_token), [
            'name' => 'Password Required',
            'email' => 'password-required@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
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
