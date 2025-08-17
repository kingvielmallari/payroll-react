<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class GracePeriodController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display grace period settings
     */
    public function index()
    {
        $this->authorize('edit settings');

        $gracePeriodSettings = [
            'late_grace_minutes' => config('company.late_grace_minutes', 0),
            'undertime_grace_minutes' => config('company.undertime_grace_minutes', 0),
            'overtime_threshold_minutes' => config('company.overtime_threshold_minutes', 0),
        ];

        return response()->json($gracePeriodSettings);
    }

    /**
     * Update grace period settings
     */
    public function update(Request $request)
    {
        $this->authorize('edit settings');

        $request->validate([
            'late_grace_minutes' => 'required|integer|min:0|max:60',
            'undertime_grace_minutes' => 'required|integer|min:0|max:60',
            'overtime_threshold_minutes' => 'required|integer|min:0|max:60',
        ]);

        // Update the company.php config file
        $configPath = config_path('company.php');

        if (File::exists($configPath)) {
            $config = include $configPath;
        } else {
            $config = [];
        }

        // Update the config values
        $config['late_grace_minutes'] = $request->late_grace_minutes;
        $config['undertime_grace_minutes'] = $request->undertime_grace_minutes;
        $config['overtime_threshold_minutes'] = $request->overtime_threshold_minutes;

        // Write the updated config back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        File::put($configPath, $configContent);

        // Clear the config cache
        Cache::forget('config.company');

        return response()->json([
            'message' => 'Grace period settings updated successfully.',
            'data' => [
                'late_grace_minutes' => $request->late_grace_minutes,
                'undertime_grace_minutes' => $request->undertime_grace_minutes,
                'overtime_threshold_minutes' => $request->overtime_threshold_minutes,
            ]
        ]);
    }
}
