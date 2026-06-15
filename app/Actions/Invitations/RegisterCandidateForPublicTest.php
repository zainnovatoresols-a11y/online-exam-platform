<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RegisterCandidateForPublicTest
{
    /**
     * Register a candidate through a test public link and accept/create their invitation.
     *
     * @param  array{name: string, email: string, password: string, phone?: string|null, stack_name?: string|null}  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(Test $test, array $data, ?User $currentUser = null): User
    {
        if (! $test->isPublished()) {
            throw ValidationException::withMessages([
                'email' => 'This test is not available.',
            ]);
        }

        $email = strtolower(trim($data['email']));

        if ($currentUser && strtolower($currentUser->email) !== $email) {
            throw new AuthorizationException('You are logged in with a different email address.');
        }

        $invitation = Invitation::query()
            ->where('test_id', $test->id)
            ->where('email', $email)
            ->latest('id')
            ->first();

        if ($invitation?->isRevoked()) {
            throw ValidationException::withMessages([
                'email' => 'This invitation has been revoked.',
            ]);
        }

        if ($invitation?->isAcceptable() && $invitation->hasExpired()) {
            $invitation->update([
                'status' => InvitationStatus::Expired,
            ]);
        }

        if ($invitation?->isExpired()) {
            throw ValidationException::withMessages([
                'email' => 'This invitation has expired.',
            ]);
        }

        if (! $invitation && ! $test->public_access_enabled) {
            throw ValidationException::withMessages([
                'email' => 'This email is not allowed to access this test.',
            ]);
        }

        if (! $invitation) {
            $invitation = Invitation::create([
                'organization_id' => $test->organization_id,
                'test_id' => $test->id,
                'invited_by' => $test->created_by_id,
                'name' => $data['name'],
                'email' => $email,
                'token' => $this->token(),
                'status' => InvitationStatus::Pending,
                'starts_at' => $test->starts_at,
                'expires_at' => null,
            ]);
        }

        if ($currentUser) {
            $candidate = $currentUser;
        } else {
            $candidate = User::query()->where('email', $email)->first();

            if ($candidate && ! Hash::check((string) $data['password'], $candidate->password)) {
                throw ValidationException::withMessages([
                    'password' => 'The provided password is incorrect for this email.',
                ]);
            }

            if (! $candidate) {
                $candidate = User::create([
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => Hash::make((string) $data['password']),
                    'email_verified_at' => now(),
                ]);
            }
        }

        $candidate->forceFill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? $candidate->phone,
            'stack_name' => $data['stack_name'] ?? $candidate->stack_name,
            'email_verified_at' => $candidate->email_verified_at ?? now(),
        ])->save();

        Role::findOrCreate(UserRole::Candidate->value, 'web');

        if (! $candidate->hasRole(UserRole::Candidate->value)) {
            $candidate->assignRole(UserRole::Candidate->value);
        }

        if ($candidate->organization_id === null && $test->organization_id !== null) {
            $candidate->update([
                'organization_id' => $test->organization_id,
            ]);
        }

        if ($candidate->organization_id === null && $test->organization_id === null && $candidate->created_by_id === null) {
            $candidate->update([
                'created_by_id' => $test->created_by_id,
            ]);
        }

        $invitation->update([
            'candidate_user_id' => $candidate->id,
            'name' => $data['name'],
            'status' => InvitationStatus::Accepted,
            'accepted_at' => $invitation->accepted_at ?? now(),
            'policy_accepted_at' => now(),
            'candidate_profile' => [
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'stack_name' => $data['stack_name'] ?? null,
            ],
        ]);

        Auth::login($candidate);

        return $candidate;
    }

    private function token(): string
    {
        do {
            $token = Str::random(64);
        } while (Invitation::query()->where('token', $token)->exists());

        return $token;
    }
}
