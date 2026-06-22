<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Onboarding/Index');
    }
}
