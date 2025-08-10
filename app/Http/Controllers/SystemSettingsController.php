<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemSettingsController extends Controller
{
    /**
     * Display the system settings page.
     */
    public function index()
    {
        // Get current theme preference from session or default to 'light'
        $currentTheme = session('theme', 'light');
        
        $settings = [
            'appearance' => [
                'theme' => $currentTheme,
                'available_themes' => ['light', 'dark'],
            ],
            'system' => [
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ],
            'notifications' => [
                'email_notifications' => true,
                'browser_notifications' => false,
            ],
        ];

        return view('system-settings.index', compact('settings'));
    }

    /**
     * Update system settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
            'email_notifications' => 'boolean',
            'browser_notifications' => 'boolean',
        ]);

        // Update theme preference in session
        session(['theme' => $request->theme]);

        // You can add more setting updates here as needed
        // For example, store in database for persistent user preferences

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Toggle theme between light and dark.
     */
    public function toggleTheme(Request $request)
    {
        $currentTheme = session('theme', 'light');
        $newTheme = $currentTheme === 'light' ? 'dark' : 'light';
        
        session(['theme' => $newTheme]);

        return response()->json([
            'success' => true,
            'theme' => $newTheme
        ]);
    }
}
