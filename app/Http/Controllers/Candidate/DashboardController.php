<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the candidate dashboard.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Candidate/Dashboard');
    }
}
