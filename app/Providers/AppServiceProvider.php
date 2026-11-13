<?php

namespace App\Providers;

use App\Game\Mathematics\Rng\RandomIntRng;
use App\Game\Mathematics\Rng\RngInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->bind(RngInterface::class, RandomIntRng::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        //
    }
}
