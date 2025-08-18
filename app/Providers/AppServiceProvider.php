<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Force HTTPS URLs when behind proxy (like Cloudflare)
        // if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
        //     URL::forceScheme('https');
        // }

        // Trust proxies (for Cloudflare)
        // if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        //     request()->setTrustedProxies(['*'], \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);
        // }
    }
}
