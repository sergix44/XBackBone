<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Feature::resolveScopeUsing(static fn() => null);

        Gate::define('administrate', static fn (User $user) => $user->is_admin);

        if ($this->app->runningInConsole()) {
            $this->registerPublishes();
        }
    }

    public function registerPublishes(): void
    {
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
