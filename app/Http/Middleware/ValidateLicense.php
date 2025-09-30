<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseService;
use Symfony\Component\HttpFoundation\Response;

class ValidateLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validation = LicenseService::validateLicense();

        if (!$validation['valid']) {
            switch ($validation['reason']) {
                case 'No active license found':
                    return redirect()->route('license.activate');
                case 'License expired':
                    return redirect()->route('license.expired');
                case 'License not valid for this server':
                    return redirect()->route('license.invalid');
                case 'Employee limit exceeded':
                    return redirect()->route('license.limit-exceeded');
                default:
                    return redirect()->route('license.activate');
            }
        }

        // Add license info to view
        view()->share('currentLicense', $validation['license']);

        return $next($request);
    }
}
