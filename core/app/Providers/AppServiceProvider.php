<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Sqids\Sqids;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Sqids::class, function () {
            return new Sqids(Feature::value('id-alphabet'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $group = [
            base_path('public/build/') => public_path('build'),
            base_path('public/vendor/') => public_path('vendor'),
            base_path('public/.htaccess') => public_path('.htaccess'),
        ];

        if ($this->app->environment('local')) {
            $group[base_path('public/hot')] = public_path('hot');
        }

        $this->publishes($group, 'app');

        $this->publishes([
            base_path('public/favicon.ico') => public_path('favicon.ico'),
            base_path('public/img/') => public_path('img'),
        ], 'app-img');
    }
}
