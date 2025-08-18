<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NightDifferentialSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NightDifferentialController extends Controller
{
    use AuthorizesRequests;

    /**
     * Update night differential settings
     */
    public function update(Request $request)
    {
        $this->authorize('edit settings');

        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'rate_multiplier' => 'required|numeric|min:1|max:2',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        // Get current setting or create new one
        $setting = NightDifferentialSetting::first();

        if (!$setting) {
            $setting = new NightDifferentialSetting();
        }

        $setting->fill([
            'start_time' => $request->start_time . ':00',
            'end_time' => $request->end_time . ':00',
            'rate_multiplier' => $request->rate_multiplier,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true)
        ]);

        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Night differential settings updated successfully'
        ]);
    }
}
