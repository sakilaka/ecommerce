<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ((bool) config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::define('access-commerce-admin', fn (User $user): bool => $user->isAdmin());

        RateLimiter::for('commerce-login', function (Request $request): Limit {
            $login = Str::transliterate(Str::lower((string) $request->input('login')));

            return Limit::perMinute(5)->by($login.'|'.$request->ip());
        });

        RateLimiter::for('commerce-register', fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('commerce-checkout', fn (Request $request): Limit => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('commerce-cart', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('commerce-public-action', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('commerce-leads', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('commerce-order-tracking', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
    }
}
