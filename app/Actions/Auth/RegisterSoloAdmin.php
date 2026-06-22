<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterSoloAdmin
{
    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string
     * }  $validated
     */
    public function handle(array $validated): User
    {
        return DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
            ]);

            Role::findOrCreate(UserRole::Admin->value, 'web');
            $user->assignRole(UserRole::Admin->value);
            $user->forceFill(['created_by_id' => $user->id])->save();

            return $user->refresh();
        });
    }
}
