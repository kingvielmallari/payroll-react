<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\DaySchedule;
use App\Models\TimeSchedule;
use App\Models\Employee;

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

        return view('settings.time-logs.index', compact(
            'daySchedules',
            'timeSchedules',
            'employees',
            'gracePeriodData'
        ));
    }
}
