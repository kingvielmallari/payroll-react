<?php

namespace App\Http\Controllers;

use App\Models\SystemLicense;
use App\Models\SubscriptionPlan;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function showActivation(): View
    {
        // Check if already licensed
        $currentLicense = SystemLicense::current();

        return view('license.activate', [
            'currentLicense' => $currentLicense,
            'plans' => SubscriptionPlan::where('is_active', true)->get()
        ]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|min:32'
        ]);

        $result = LicenseService::activateLicense($request->license_key);

        if ($result['success']) {
            return redirect()->route('dashboard')
                ->with('success', 'License activated successfully! Welcome to your payroll system.');
        }

        return back()
            ->withInput()
            ->withErrors(['license_key' => $result['message']]);
    }

    public function status(): View
    {
        $validation = LicenseService::validateLicense();
        $currentLicense = SystemLicense::current();

        return view('license.status', [
            'validation' => $validation,
            'license' => $currentLicense,
            'employeeCount' => \App\Models\Employee::count()
        ]);
    }

    public function manage(): View
    {
        $currentLicense = SystemLicense::current();
        $allLicenses = SystemLicense::with('subscriptionPlan')->orderBy('created_at', 'desc')->get();

        return view('license.manage', [
            'currentLicense' => $currentLicense,
            'allLicenses' => $allLicenses,
            'plans' => SubscriptionPlan::where('is_active', true)->get()
        ]);
    }

    public function expired(): View
    {
        return view('license.expired');
    }

    public function invalid(): View
    {
        return view('license.invalid');
    }

    public function limitExceeded(): View
    {
        $license = SystemLicense::current();
        $employeeCount = \App\Models\Employee::count();

        return view('license.limit-exceeded', [
            'license' => $license,
            'employeeCount' => $employeeCount
        ]);
    }
}
