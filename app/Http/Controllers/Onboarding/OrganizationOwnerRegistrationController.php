<?php

namespace App\Http\Controllers\Onboarding;

use App\Actions\Auth\RedirectUserToDashboard;
use App\Actions\Auth\RegisterOrganizationOwner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreOrganizationOwnerRegistrationRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationOwnerRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Onboarding/OrganizationOwnerRegister');
    }

    public function store(
        StoreOrganizationOwnerRegistrationRequest $request,
        RegisterOrganizationOwner $registerOrganizationOwner,
        RedirectUserToDashboard $redirectUserToDashboard,
    ): RedirectResponse {
        $user = $registerOrganizationOwner->handle($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return $redirectUserToDashboard->handle($user);
    }
}
