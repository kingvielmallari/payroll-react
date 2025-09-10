<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\GracePeriodSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class GracePeriodController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display grace period settings
     */
    public function index()
    {
        $this->authorize('edit settings');

        $gracePeriodSettings = GracePeriodSetting::current();

        return response()->json([
            'late_grace_minutes' => $gracePeriodSettings->late_grace_minutes,
            'undertime_grace_minutes' => $gracePeriodSettings->undertime_grace_minutes,
            // overtime_threshold_minutes removed - now schedule-specific
        ]);
    }

    /**
     * Update grace period settings
     */
    public function update(Request $request)
    {
        $this->authorize('edit settings');

        $request->validate([
            'late_grace_minutes' => 'required|integer|min:0|max:120',
            'undertime_grace_minutes' => 'required|integer|min:0|max:120',
            // overtime_threshold_minutes removed - now schedule-specific
        ]);

        // Update grace period settings in database
        $gracePeriodSetting = GracePeriodSetting::updateCurrent([
            'late_grace_minutes' => $request->late_grace_minutes,
            'undertime_grace_minutes' => $request->undertime_grace_minutes,
            // overtime_threshold_minutes removed - now schedule-specific
        ]);

        return response()->json([
            'message' => 'Grace period settings updated successfully.',
            'data' => [
                'late_grace_minutes' => $gracePeriodSetting->late_grace_minutes,
                'undertime_grace_minutes' => $gracePeriodSetting->undertime_grace_minutes,
                // overtime_threshold_minutes removed - now schedule-specific
            ]
        ]);
    }
}
