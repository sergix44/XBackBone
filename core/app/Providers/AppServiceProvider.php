<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        if ($this->app->runningInConsole()) {
            $group = [
                base_path('public/build/') => public_path('build'),
                base_path('public/vendor/') => public_path('vendor'),
                base_path('public/.htaccess') => public_path('.htaccess'),
            ];

            if ($this->app->environment('local')){
                $group[base_path('public/hot')] = public_path('hot');
            }

            $this->publishes($group, 'app');
        }
    }
}
