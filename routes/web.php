<?php

use App\Http\Controllers\Admin\CodingQuestionController as AdminCodingQuestionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\Invitations\InvitationController as AdminInvitationController;
use App\Http\Controllers\Admin\ProctoringRecordingChunkController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\Results\TestResultController as AdminTestResultController;
use App\Http\Controllers\Admin\TestController as AdminTestController;
use App\Http\Controllers\Admin\TestLifecycleController;
use App\Http\Controllers\Candidate\Attempts\ProctoringEventController;
use App\Http\Controllers\Candidate\Attempts\ProctoringRecordingController;
use App\Http\Controllers\Candidate\Attempts\RunCodingQuestionController;
use App\Http\Controllers\Candidate\Attempts\TestAttemptController;
use App\Http\Controllers\Candidate\DashboardController as CandidateDashboardController;
use App\Http\Controllers\Candidate\Invitations\InvitationController as CandidateInvitationController;
use App\Http\Controllers\Candidate\PublicTests\PublicAttemptController;
use App\Http\Controllers\Candidate\PublicTests\PublicTestController;
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

Route::get('/public-tests/{publicToken}', [PublicTestController::class, 'policy'])
    ->name('candidate.public-tests.policy');
Route::post('/public-tests/{publicToken}/policy', [PublicTestController::class, 'acceptPolicy'])
    ->name('candidate.public-tests.policy.accept');
Route::get('/public-tests/{publicToken}/register', [PublicTestController::class, 'register'])
    ->name('candidate.public-tests.register');
Route::post('/public-tests/{publicToken}/register', [PublicTestController::class, 'store'])
    ->name('candidate.public-tests.register.store');
Route::get('/public-attempts/{attemptToken}', [PublicAttemptController::class, 'show'])
    ->name('candidate.public-attempts.show');
Route::post('/public-attempts/{attemptToken}/answers', [PublicAttemptController::class, 'save'])
    ->name('candidate.public-attempts.answers.save');
Route::post('/public-attempts/{attemptToken}/coding-answers', [PublicAttemptController::class, 'saveCoding'])
    ->name('candidate.public-attempts.coding-answers.save');
Route::post('/public-attempts/{attemptToken}/coding-questions/run', [RunCodingQuestionController::class, 'storePublic'])
    ->middleware('throttle:30,1')
    ->name('candidate.public-attempts.coding-questions.run');
Route::post('/public-attempts/{attemptToken}/proctoring-events', [ProctoringEventController::class, 'storePublic'])
    ->middleware('throttle:60,1')
    ->name('candidate.public-attempts.proctoring-events.store');
Route::post('/public-attempts/{attemptToken}/proctoring-recordings/start', [ProctoringRecordingController::class, 'startPublic'])
    ->middleware('throttle:20,1')
    ->name('candidate.public-attempts.proctoring-recordings.start');
Route::post('/public-attempts/{attemptToken}/proctoring-recordings/chunks', [ProctoringRecordingController::class, 'storePublicChunk'])
    ->middleware('throttle:30,1')
    ->name('candidate.public-attempts.proctoring-recordings.chunks.store');
Route::post('/public-attempts/{attemptToken}/proctoring-recordings/stop', [ProctoringRecordingController::class, 'stopPublic'])
    ->middleware('throttle:20,1')
    ->name('candidate.public-attempts.proctoring-recordings.stop');
Route::post('/public-attempts/{attemptToken}/submit', [PublicAttemptController::class, 'submit'])
    ->name('candidate.public-attempts.submit');

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

Route::middleware(['auth', 'verified', 'role:super_admin|admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/proctoring-recording-chunks/{chunk}', [ProctoringRecordingChunkController::class, 'show'])
            ->name('proctoring-recording-chunks.show');
        Route::scopeBindings()
            ->prefix('tests/{test}')
            ->name('tests.')
            ->group(function (): void {
                Route::get('results', [AdminTestResultController::class, 'index'])
                    ->name('results.index');
                Route::get('results/{attempt}', [AdminTestResultController::class, 'show'])
                    ->name('results.show');
            });
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

                Route::get('coding-questions/create', [AdminCodingQuestionController::class, 'create'])
                    ->name('coding-questions.create');
                Route::post('coding-questions', [AdminCodingQuestionController::class, 'store'])
                    ->name('coding-questions.store');
                Route::get('coding-questions/{question}/edit', [AdminCodingQuestionController::class, 'edit'])
                    ->name('coding-questions.edit');
                Route::patch('coding-questions/{question}', [AdminCodingQuestionController::class, 'update'])
                    ->name('coding-questions.update');
                Route::delete('coding-questions/{question}', [AdminCodingQuestionController::class, 'destroy'])
                    ->name('coding-questions.destroy');

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
        Route::post('/attempts/{attempt}/answers', [TestAttemptController::class, 'save'])
            ->name('attempts.answers.save');
        Route::post('/attempts/{attempt}/coding-answers', [TestAttemptController::class, 'saveCoding'])
            ->name('attempts.coding-answers.save');
        Route::post('/attempts/{attempt}/coding-questions/run', [RunCodingQuestionController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('attempts.coding-questions.run');
        Route::post('/attempts/{attempt}/proctoring-events', [ProctoringEventController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name('attempts.proctoring-events.store');
        Route::post('/attempts/{attempt}/proctoring-recordings/start', [ProctoringRecordingController::class, 'start'])
            ->middleware('throttle:20,1')
            ->name('attempts.proctoring-recordings.start');
        Route::post('/attempts/{attempt}/proctoring-recordings/chunks', [ProctoringRecordingController::class, 'storeChunk'])
            ->middleware('throttle:30,1')
            ->name('attempts.proctoring-recordings.chunks.store');
        Route::post('/attempts/{attempt}/proctoring-recordings/stop', [ProctoringRecordingController::class, 'stop'])
            ->middleware('throttle:20,1')
            ->name('attempts.proctoring-recordings.stop');
        Route::post('/attempts/{attempt}/submit', [TestAttemptController::class, 'submit'])
            ->name('attempts.submit');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
