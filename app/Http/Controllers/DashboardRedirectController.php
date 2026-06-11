<?php

namespace App\Http\Controllers;

use App\Actions\Auth\RedirectUserToDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    /**
     * Redirect authenticated users to their role-specific dashboard.
     */
    public function __invoke(Request $request, RedirectUserToDashboard $redirector): RedirectResponse
    {
        return $redirector->handle($request->user());
    }
}
