<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\Question;
use App\Models\Test;
use App\Policies\OrganizationPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\TestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Test::class, TestPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);

        Vite::prefetch(concurrency: 3);
    }
}
