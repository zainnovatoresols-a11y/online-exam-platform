<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_login_redirects_to_super_admin_dashboard(): void
    {
        $user = $this->userWithRole(UserRole::SuperAdmin);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/super-admin/dashboard');
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $user = $this->userWithRole(UserRole::Admin);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_candidate_login_redirects_to_candidate_dashboard(): void
    {
        $user = $this->userWithRole(UserRole::Candidate);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/candidate/dashboard');
    }

    public function test_admin_cannot_access_candidate_dashboard(): void
    {
        $user = $this->userWithRole(UserRole::Admin);

        $response = $this->actingAs($user)->get('/candidate/dashboard');

        $response->assertForbidden();
    }

    public function test_candidate_cannot_access_admin_dashboard(): void
    {
        $user = $this->userWithRole(UserRole::Candidate);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_new_registered_users_get_the_candidate_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Candidate',
            'email' => 'new-candidate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'new-candidate@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(UserRole::Candidate->value));
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    private function userWithRole(UserRole $role): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate($role->value, 'web');

        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
