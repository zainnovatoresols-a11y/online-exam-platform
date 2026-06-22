<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_index_can_be_rendered(): void
    {
        $this->get(route('onboarding.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Onboarding/Index')
            );
    }

    public function test_organization_owner_can_self_register(): void
    {
        $response = $this->post(route('onboarding.organization-owner.store'), [
            'organization_name' => 'Acme Institute',
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $organization = Organization::where('name', 'Acme Institute')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(UserRole::SuperAdmin->value));
        $this->assertSame($organization->id, $user->organization_id);
        $this->assertSame($user->id, $user->created_by_id);
        $response->assertRedirect(route('super-admin.dashboard', absolute: false));
    }

    public function test_solo_admin_can_self_register(): void
    {
        $response = $this->post(route('onboarding.solo-admin.store'), [
            'name' => 'Solo Admin',
            'email' => 'solo-admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'solo-admin@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(UserRole::Admin->value));
        $this->assertNull($user->organization_id);
        $this->assertSame($user->id, $user->created_by_id);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_organization_owner_only_sees_their_own_organization(): void
    {
        $ownOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::SuperAdmin, $ownOrganization);

        $this->actingAs($owner)
            ->get(route('super-admin.organizations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/Organizations/Index')
                ->where('can_create_organizations', false)
                ->has('organizations.data', 1)
                ->where('organizations.data.0.id', $ownOrganization->id)
                ->where('organizations.data.0.name', $ownOrganization->name)
                ->missing('organizations.data.1')
            );

        $this->actingAs($owner)
            ->get(route('super-admin.organizations.show', $otherOrganization))
            ->assertForbidden();
    }

    public function test_organization_owner_cannot_create_a_new_organization(): void
    {
        $owner = $this->userWithRole(
            UserRole::SuperAdmin,
            Organization::factory()->create(),
        );

        $this->actingAs($owner)
            ->post(route('super-admin.organizations.store'), [
                'name' => 'Blocked Organization',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('organizations', [
            'name' => 'Blocked Organization',
        ]);
    }

    public function test_organization_owner_can_create_admin_for_their_own_organization(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::SuperAdmin, $organization);

        $response = $this->actingAs($owner)->post(
            route('super-admin.organizations.admins.store', $organization),
            [
                'name' => 'Scoped Admin',
                'email' => 'scoped-admin@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        );

        $admin = User::where('email', 'scoped-admin@example.com')->firstOrFail();

        $response->assertRedirect(route('super-admin.organizations.show', $organization));
        $this->assertTrue($admin->hasRole(UserRole::Admin->value));
        $this->assertSame($organization->id, $admin->organization_id);
        $this->assertSame($owner->id, $admin->created_by_id);
    }

    public function test_organization_owner_cannot_create_admin_for_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $owner = $this->userWithRole(UserRole::SuperAdmin, $organization);

        $this->actingAs($owner)->post(
            route('super-admin.organizations.admins.store', $otherOrganization),
            [
                'name' => 'Blocked Admin',
                'email' => 'blocked-admin@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        )->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked-admin@example.com',
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
