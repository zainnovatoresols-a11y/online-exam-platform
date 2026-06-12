<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Seed local users for role-based access testing.
     */
    public function run(): void
    {
        $defaultOrganization = Organization::firstOrCreate([
            'name' => 'Default Organization',
        ]);

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => UserRole::SuperAdmin,
                'organization_id' => null,
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => UserRole::Admin,
                'organization_id' => $defaultOrganization->id,
            ],
            [
                'name' => 'Candidate User',
                'email' => 'candidate@example.com',
                'role' => UserRole::Candidate,
                'organization_id' => null,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'organization_id' => $userData['organization_id'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$userData['role']->value]);
        }
    }
}
