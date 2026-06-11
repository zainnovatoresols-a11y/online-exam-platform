<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class RedirectUserToDashboard
{
    /**
     * Redirect a user to the dashboard assigned to their role.
     */
    public function handle(User $user, bool $useIntendedUrl = false): RedirectResponse
    {
        $routeName = $this->routeNameFor($user);

        if ($useIntendedUrl) {
            return redirect()->intended(route($routeName, absolute: false));
        }

        return to_route($routeName);
    }

    /**
     * Resolve the dashboard route name for the user's role.
     */
    private function routeNameFor(User $user): string
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return 'super-admin.dashboard';
        }

        if ($user->hasRole(UserRole::Admin->value)) {
            return 'admin.dashboard';
        }

        if ($user->hasRole(UserRole::Candidate->value)) {
            return 'candidate.dashboard';
        }

        abort(403);
    }
}
