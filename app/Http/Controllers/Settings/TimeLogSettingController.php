<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\DaySchedule;
use App\Models\TimeSchedule;
use App\Models\Employee;
use App\Models\NightDifferentialSetting;

class TimeLogSettingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the main Time Log Settings page
     */
    public function index()
    {
        $this->authorize('edit settings');

        // Get all day schedules
        $daySchedules = DaySchedule::orderBy('name')->get();

        // Get all time schedules
        $timeSchedules = TimeSchedule::orderBy('name')->get();

        // Get all active employees
        $employees = Employee::with('user')->active()->orderBy('employee_number')->get();

        // Get grace period settings from database
        $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
        $gracePeriodData = [
            'late_grace_minutes' => $gracePeriodSettings->late_grace_minutes,
            'undertime_grace_minutes' => $gracePeriodSettings->undertime_grace_minutes,
            'overtime_threshold_minutes' => $gracePeriodSettings->overtime_threshold_minutes,
        ];

        // Get night differential settings
        $nightDifferentialSetting = NightDifferentialSetting::current();

        // Provide default values if no setting exists
        if (!$nightDifferentialSetting) {
            $nightDifferentialData = [
                'start_time' => '22:00:00',
                'end_time' => '05:00:00',
                'rate_multiplier' => 1.10,
                'description' => 'Standard night differential',
                'is_active' => true,
            ];
        } else {
            $nightDifferentialData = [
                'start_time' => $nightDifferentialSetting->start_time,
                'end_time' => $nightDifferentialSetting->end_time,
                'rate_multiplier' => $nightDifferentialSetting->rate_multiplier,
                'description' => $nightDifferentialSetting->description,
                'is_active' => $nightDifferentialSetting->is_active,
            ];
        }

        return view('settings.time-logs.index', compact(
            'daySchedules',
            'timeSchedules',
            'employees',
            'gracePeriodData',
            'nightDifferentialData'
        ));
    }
}
