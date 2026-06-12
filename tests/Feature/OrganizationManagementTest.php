<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_an_organization(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.organizations.store'), [
            'name' => 'Acme Institute',
        ]);

        $organization = Organization::where('name', 'Acme Institute')->firstOrFail();

        $response->assertRedirect(route('super-admin.organizations.show', $organization));
        $this->assertDatabaseHas('organizations', [
            'name' => 'Acme Institute',
        ]);
    }

    public function test_admin_cannot_create_an_organization(): void
    {
        $admin = $this->userWithRole(UserRole::Admin, Organization::factory()->create());

        $response = $this->actingAs($admin)->post(route('super-admin.organizations.store'), [
            'name' => 'Blocked Organization',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('organizations', [
            'name' => 'Blocked Organization',
        ]);
    }

    public function test_candidate_cannot_create_an_organization(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);

        $response = $this->actingAs($candidate)->post(route('super-admin.organizations.store'), [
            'name' => 'Blocked Candidate Organization',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('organizations', [
            'name' => 'Blocked Candidate Organization',
        ]);
    }

    public function test_super_admin_can_create_an_admin_inside_an_organization(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);
        $organization = Organization::factory()->create();

        $response = $this->actingAs($superAdmin)->post(
            route('super-admin.organizations.admins.store', $organization),
            [
                'name' => 'Organization Admin',
                'email' => 'org-admin@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        );

        $admin = User::where('email', 'org-admin@example.com')->firstOrFail();

        $response->assertRedirect(route('super-admin.organizations.show', $organization));
        $this->assertSame($organization->id, $admin->organization_id);
        $this->assertTrue($admin->hasRole(UserRole::Admin->value));
    }

    public function test_created_organization_admin_receives_admin_role(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);
        $organization = Organization::factory()->create();

        $this->actingAs($superAdmin)->post(
            route('super-admin.organizations.admins.store', $organization),
            [
                'name' => 'Second Organization Admin',
                'email' => 'second-org-admin@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        );

        $admin = User::where('email', 'second-org-admin@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole(UserRole::Admin->value));
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
