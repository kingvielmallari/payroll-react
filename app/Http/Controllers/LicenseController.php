<?php

namespace App\Http\Controllers;

use App\Models\SystemLicense;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function showActivation(Request $request)
    {
        // Check if already licensed
        $currentLicense = SystemLicense::current();
        $isUpgrade = $request->has('upgrade') && $request->get('upgrade') == '1';

        // If upgrade is requested but there's no current valid license, redirect to regular activation
        if ($isUpgrade && (!$currentLicense || !$currentLicense->isValid())) {
            return redirect()->route('license.activate')
                ->with('error', 'You must have an active license before you can upgrade.');
        }

        return view('license.activate', [
            'currentLicense' => $currentLicense,
            'isUpgrade' => $isUpgrade
        ]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|min:32'
        ]);

        $result = LicenseService::activateLicense($request->license_key);

        if ($result['success']) {
            return redirect()->route('license.activate')
                ->with('success', 'License activated successfully!');
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
        $allLicenses = SystemLicense::orderBy('created_at', 'desc')->get();

        return view('license.manage', [
            'currentLicense' => $currentLicense,
            'allLicenses' => $allLicenses
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
