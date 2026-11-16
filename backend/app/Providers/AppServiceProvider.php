<?php

namespace App\Providers;

use App\Payments\FakePaymentProvider;
use App\Payments\PaymentProvider;
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
        //
    }
}
