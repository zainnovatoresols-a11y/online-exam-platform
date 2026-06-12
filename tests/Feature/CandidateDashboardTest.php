<?php

namespace Tests\Feature;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CandidateDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_dashboard_shows_accepted_tests_with_links(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $candidate = $this->userWithRole(UserRole::Candidate);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'title' => 'Laravel Developer Test',
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

        $response = $this->actingAs($candidate)->get(route('candidate.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Dashboard')
            ->has('invitations', 1)
            ->where('invitations.0.test.id', $test->id)
            ->where('invitations.0.test.title', 'Laravel Developer Test'));
    }

    public function test_candidate_dashboard_does_not_show_pending_or_other_candidates_tests(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $candidate = $this->userWithRole(UserRole::Candidate);
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        $pendingTest = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
        ]);
        $otherCandidateTest = Test::factory()->published()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
        ]);

        Invitation::factory()->create([
            'organization_id' => $organization->id,
            'test_id' => $pendingTest->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => InvitationStatus::Pending,
        ]);
        Invitation::factory()->create([
            'organization_id' => $organization->id,
            'test_id' => $otherCandidateTest->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $otherCandidate->id,
            'email' => $otherCandidate->email,
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($candidate)->get(route('candidate.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Dashboard')
            ->has('invitations', 0));
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
