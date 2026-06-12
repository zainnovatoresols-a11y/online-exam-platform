<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\Invitations\InvitationController as AdminInvitationController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\TestController as AdminTestController;
use App\Http\Controllers\Admin\TestLifecycleController;
use App\Http\Controllers\Candidate\Attempts\TestAttemptController;
use App\Http\Controllers\Candidate\DashboardController as CandidateDashboardController;
use App\Http\Controllers\Candidate\Invitations\InvitationController as CandidateInvitationController;
use App\Http\Controllers\Candidate\Tests\TestLandingController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\OrganizationAdminController;
use App\Http\Controllers\SuperAdmin\OrganizationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/invite/{token}', [CandidateInvitationController::class, 'show'])
    ->name('candidate.invitations.show');
Route::post('/invite/{token}/accept', [CandidateInvitationController::class, 'accept'])
    ->name('candidate.invitations.accept');

Route::middleware(['auth', 'verified'])->get('/dashboard', DashboardRedirectController::class)
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function (): void {
        Route::get('/dashboard', SuperAdminDashboardController::class)->name('dashboard');

        Route::resource('organizations', OrganizationController::class)
            ->except(['destroy']);

        Route::get('organizations/{organization}/admins/create', [OrganizationAdminController::class, 'create'])
            ->name('organizations.admins.create');
        Route::post('organizations/{organization}/admins', [OrganizationAdminController::class, 'store'])
            ->name('organizations.admins.store');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::resource('tests', AdminTestController::class);
        Route::post('tests/{test}/publish', [TestLifecycleController::class, 'publish'])
            ->name('tests.publish');
        Route::post('tests/{test}/close', [TestLifecycleController::class, 'close'])
            ->name('tests.close');

        Route::scopeBindings()
            ->prefix('tests/{test}')
            ->name('tests.')
            ->group(function (): void {
                Route::get('invitations', [AdminInvitationController::class, 'index'])
                    ->name('invitations.index');
                Route::get('invitations/create', [AdminInvitationController::class, 'create'])
                    ->name('invitations.create');
                Route::post('invitations', [AdminInvitationController::class, 'store'])
                    ->name('invitations.store');
                Route::post('invitations/{invitation}/resend', [AdminInvitationController::class, 'resend'])
                    ->name('invitations.resend');
                Route::delete('invitations/{invitation}/revoke', [AdminInvitationController::class, 'revoke'])
                    ->name('invitations.revoke');

                Route::resource('questions', AdminQuestionController::class)
                    ->except(['show']);
            });
    });

Route::middleware(['auth', 'verified', 'role:candidate'])
    ->prefix('candidate')
    ->name('candidate.')
    ->group(function (): void {
        Route::get('/dashboard', CandidateDashboardController::class)->name('dashboard');
        Route::get('/tests/{test}', TestLandingController::class)->name('tests.show');
        Route::post('/tests/{test}/attempts', [TestAttemptController::class, 'store'])
            ->name('tests.attempts.store');
        Route::get('/attempts/{attempt}', [TestAttemptController::class, 'show'])
            ->name('attempts.show');
        Route::post('/attempts/{attempt}/submit', [TestAttemptController::class, 'submit'])
            ->name('attempts.submit');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
