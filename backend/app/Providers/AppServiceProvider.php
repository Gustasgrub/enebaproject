<?php

namespace App\Providers;

use App\Models\User;
use App\Payments\FakePaymentProvider;
use App\Payments\PaymentProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The one place that decides which payment provider checkout uses.
        $this->app->bind(PaymentProvider::class, FakePaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Outside production, loading a relation lazily (one query per row, the N+1 problem) throws
        // instead of silently running extra queries, so tests catch a missing eager load. Production
        // keeps working (just slower) if one ever slips through.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Used by the admin routes: Route::middleware('can:admin').
        Gate::define('admin', fn (User $user) => $user->isAdmin());
    }
}
