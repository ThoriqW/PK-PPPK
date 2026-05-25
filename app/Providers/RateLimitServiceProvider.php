<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('verify', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
        RateLimiter::for('login',  fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('api',    fn (Request $r) => Limit::perMinute(120)->by(optional($r->user())->id ?: $r->ip()));
    }
}
