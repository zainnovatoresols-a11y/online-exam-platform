<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterOrganizationOwner
{
    /**
     * @param  array{
     *     organization_name: string,
     *     name: string,
     *     email: string,
     *     password: string
     * }  $validated
     */
    public function handle(array $validated): User
    {
        return DB::transaction(function () use ($validated): User {
            $organization = Organization::create([
                'name' => $validated['organization_name'],
            ]);

            $user = User::create([
                'organization_id' => $organization->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
            ]);

            Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
            $user->assignRole(UserRole::SuperAdmin->value);
            $user->forceFill(['created_by_id' => $user->id])->save();

            return $user->refresh();
        });
    }
}
