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

class CandidatePoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_candidate_to_organization_candidate_pool(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);

        $response = $this->actingAs($admin)->post(route('admin.candidates.store'), [
            'name' => 'Laravel Candidate',
            'email' => 'laravel-candidate@example.com',
            'password' => 'password',
            'phone' => '03001234567',
            'stack_name' => 'Laravel',
        ]);

        $candidate = User::where('email', 'laravel-candidate@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.candidates.index'));
        $this->assertSame($organization->id, $candidate->organization_id);
        $this->assertSame($admin->id, $candidate->created_by_id);
        $this->assertSame('03001234567', $candidate->phone);
        $this->assertSame('Laravel', $candidate->stack_name);
        $this->assertTrue($candidate->hasRole(UserRole::Candidate->value));
    }

    public function test_solo_admin_can_add_candidate_to_personal_candidate_pool(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $response = $this->actingAs($admin)->post(route('admin.candidates.store'), [
            'name' => 'MERN Candidate',
            'email' => 'mern-candidate@example.com',
            'password' => 'password',
            'phone' => null,
            'stack_name' => 'MERN',
        ]);

        $candidate = User::where('email', 'mern-candidate@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.candidates.index'));
        $this->assertNull($candidate->organization_id);
        $this->assertSame($admin->id, $candidate->created_by_id);
        $this->assertTrue($candidate->hasRole(UserRole::Candidate->value));
    }

    public function test_admin_can_filter_candidate_pool_by_stack(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $laravelCandidate = $this->candidateWithStack('Laravel', $organization);
        $this->candidateWithStack('MERN', $organization);

        $response = $this->actingAs($admin)->get(route('admin.candidates.index', [
            'stack' => 'Laravel',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Candidates/Index')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.email', $laravelCandidate->email)
            ->where('filters.stack', 'Laravel'));
    }

    public function test_admin_can_delete_candidate_from_their_pool(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $candidate = $this->candidateWithStack('Laravel', $organization);

        $response = $this->actingAs($admin)->delete(route('admin.candidates.destroy', $candidate));

        $response->assertRedirect(route('admin.candidates.index'));
        $this->assertDatabaseMissing('users', [
            'id' => $candidate->id,
        ]);
    }

    public function test_admin_cannot_delete_candidate_outside_their_pool(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $outsideCandidate = $this->candidateWithStack('Laravel', $otherOrganization);

        $response = $this->actingAs($admin)->delete(route('admin.candidates.destroy', $outsideCandidate));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $outsideCandidate->id,
        ]);
    }

    public function test_admin_can_bulk_invite_selected_candidates_for_test(): void
    {
        Notification::fake();
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $firstCandidate = $this->candidateWithStack('Laravel', $organization);
        $secondCandidate = $this->candidateWithStack('Laravel', $organization);
        $unselectedCandidate = $this->candidateWithStack('MERN', $organization);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'candidate_ids' => [$firstCandidate->id, $secondCandidate->id],
            'starts_at' => now()->addHour()->toDateTimeString(),
            'expires_at' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => $firstCandidate->email,
            'status' => InvitationStatus::Sent->value,
        ]);
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'email' => $secondCandidate->email,
            'status' => InvitationStatus::Sent->value,
        ]);
        $this->assertDatabaseMissing('invitations', [
            'test_id' => $test->id,
            'email' => $unselectedCandidate->email,
        ]);
    }

    public function test_solo_admin_can_bulk_invite_candidates_from_personal_pool(): void
    {
        Notification::fake();
        $admin = $this->userWithRole(UserRole::Admin);
        $candidate = $this->candidateWithStack('AI', creator: $admin);
        $test = Test::factory()->published()->create([
            'organization_id' => null,
            'created_by_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'candidate_ids' => [$candidate->id],
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.invitations.index', $test));
        $this->assertDatabaseHas('invitations', [
            'test_id' => $test->id,
            'organization_id' => null,
            'email' => $candidate->email,
            'status' => InvitationStatus::Sent->value,
        ]);
    }

    public function test_admin_cannot_bulk_invite_candidate_outside_their_pool(): void
    {
        Notification::fake();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $outsideCandidate = $this->candidateWithStack('Laravel', $otherOrganization);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.invitations.store', $test), [
            'candidate_ids' => [$outsideCandidate->id],
            'starts_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertSessionHasErrors('candidate_ids');
        $this->assertDatabaseMissing('invitations', [
            'test_id' => $test->id,
            'email' => $outsideCandidate->email,
        ]);
    }

    public function test_sent_invitation_accept_route_redirects_to_public_policy_flow(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
        ]);
        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'email' => 'accepted-from-sent@example.com',
            'status' => InvitationStatus::Sent,
        ]);

        $response = $this->post(route('candidate.invitations.accept', $invitation->token));

        $response->assertRedirect(route('candidate.public-tests.policy', [
            'publicToken' => $test->public_token,
            'email' => $invitation->email,
        ]));
        $this->assertSame(InvitationStatus::Sent, $invitation->refresh()->status);
        $this->assertNull($invitation->accepted_at);
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

    private function candidateWithStack(
        string $stack,
        ?Organization $organization = null,
        ?User $creator = null,
    ): User {
        Role::findOrCreate(UserRole::Candidate->value, 'web');

        $candidate = User::factory()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $creator?->id,
            'stack_name' => $stack,
        ]);

        $candidate->assignRole(UserRole::Candidate->value);

        return $candidate;
    }
}
