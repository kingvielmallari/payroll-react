<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\DaySchedule;
use App\Models\TimeSchedule;

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

        // Get grace period settings - using config or database
        $gracePeriodSettings = [
            'late_grace_minutes' => config('company.late_grace_minutes', 0),
            'undertime_grace_minutes' => config('company.undertime_grace_minutes', 0),
            'overtime_threshold_minutes' => config('company.overtime_threshold_minutes', 0),
        ];

        return view('settings.time-logs.index', compact(
            'daySchedules',
            'timeSchedules',
            'gracePeriodSettings'
        ));
    }
}
