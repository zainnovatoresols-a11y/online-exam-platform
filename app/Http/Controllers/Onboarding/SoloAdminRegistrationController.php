<?php

namespace App\Http\Controllers\Onboarding;

use App\Actions\Auth\RedirectUserToDashboard;
use App\Actions\Auth\RegisterSoloAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreSoloAdminRegistrationRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SoloAdminRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Onboarding/SoloAdminRegister');
    }

    public function store(
        StoreSoloAdminRegistrationRequest $request,
        RegisterSoloAdmin $registerSoloAdmin,
        RedirectUserToDashboard $redirectUserToDashboard,
    ): RedirectResponse {
        $user = $registerSoloAdmin->handle($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return $redirectUserToDashboard->handle($user);
    }
}
