<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // Fix for MySQL "Specified key was too long" error
        // Sets default string length to 191 characters (fits within MySQL's 1000-byte index limit with utf8mb4)
        Schema::defaultStringLength(191);
    }
}
