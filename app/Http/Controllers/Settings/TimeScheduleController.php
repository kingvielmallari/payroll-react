<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\TimeSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TimeScheduleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('edit settings');

        $timeSchedules = TimeSchedule::orderBy('name')->get();

        return response()->json($timeSchedules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('edit settings');

        // Prepare validation data - convert empty strings to null for time fields
        $validationData = $request->all();
        if (empty($validationData['break_start'])) {
            $validationData['break_start'] = null;
        }
        if (empty($validationData['break_end'])) {
            $validationData['break_end'] = null;
        }

        $validator = Validator::make($validationData, [
            'name' => 'required|string|max:255|unique:time_schedules',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i|after:time_in',
            'break_duration_minutes' => 'nullable|integer|min:0|max:480', // Max 8 hours break
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Clean up empty break times
        $breakStart = $validationData['break_start'];
        $breakEnd = $validationData['break_end'];

        // Validate break times if provided
        if ($breakStart && $breakEnd) {
            $timeIn = Carbon::createFromFormat('H:i', $request->time_in);
            $timeOut = Carbon::createFromFormat('H:i', $request->time_out);
            $breakStartTime = Carbon::createFromFormat('H:i', $breakStart);
            $breakEndTime = Carbon::createFromFormat('H:i', $breakEnd);

            if (
                $breakStartTime->lt($timeIn) || $breakStartTime->gt($timeOut) ||
                $breakEndTime->lt($timeIn) || $breakEndTime->gt($timeOut) ||
                $breakEndTime->lt($breakStartTime)
            ) {
                return response()->json([
                    'message' => 'Break times must be within work hours and break end must be after break start.',
                    'errors' => [
                        'break_start' => ['Break times must be within work hours.'],
                        'break_end' => ['Break end must be after break start.']
                    ]
                ], 422);
            }
        }

        $timeSchedule = TimeSchedule::create([
            'name' => $request->name,
            'time_in' => $request->time_in,
            'time_out' => $request->time_out,
            'break_duration_minutes' => $request->break_duration_minutes,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        return response()->json([
            'message' => 'Time schedule created successfully.',
            'data' => $timeSchedule
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeSchedule $timeSchedule)
    {
        $this->authorize('edit settings');

        return response()->json($timeSchedule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeSchedule $timeSchedule)
    {
        $this->authorize('edit settings');

        // Prepare validation data - convert empty strings to null for time fields
        $validationData = $request->all();
        if (empty($validationData['break_start'])) {
            $validationData['break_start'] = null;
        }
        if (empty($validationData['break_end'])) {
            $validationData['break_end'] = null;
        }

        $validator = Validator::make($validationData, [
            'name' => 'required|string|max:255|unique:time_schedules,name,' . $timeSchedule->id,
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i|after:time_in',
            'break_duration_minutes' => 'nullable|integer|min:0|max:480',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Clean up empty break times
        $breakStart = $validationData['break_start'];
        $breakEnd = $validationData['break_end'];

        // Validate break times if provided
        if ($breakStart && $breakEnd) {
            $timeIn = Carbon::createFromFormat('H:i', $request->time_in);
            $timeOut = Carbon::createFromFormat('H:i', $request->time_out);
            $breakStartTime = Carbon::createFromFormat('H:i', $breakStart);
            $breakEndTime = Carbon::createFromFormat('H:i', $breakEnd);

            if (
                $breakStartTime->lt($timeIn) || $breakStartTime->gt($timeOut) ||
                $breakEndTime->lt($timeIn) || $breakEndTime->gt($timeOut) ||
                $breakEndTime->lt($breakStartTime)
            ) {
                return response()->json([
                    'message' => 'Break times must be within work hours and break end must be after break start.',
                    'errors' => [
                        'break_start' => ['Break times must be within work hours.'],
                        'break_end' => ['Break end must be after break start.']
                    ]
                ], 422);
            }
        }

        $timeSchedule->update([
            'name' => $request->name,
            'time_in' => $request->time_in,
            'time_out' => $request->time_out,
            'break_duration_minutes' => $request->break_duration_minutes,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        return response()->json([
            'message' => 'Time schedule updated successfully.',
            'data' => $timeSchedule
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeSchedule $timeSchedule)
    {
        $this->authorize('edit settings');

        // Check if this schedule is being used by any employees
        $employeeCount = $timeSchedule->employees()->count();

        if ($employeeCount > 0) {
            return response()->json([
                'message' => "Cannot delete time schedule. It is currently assigned to {$employeeCount} employee(s)."
            ], 422);
        }

        $timeSchedule->delete();

        return response()->json([
            'message' => 'Time schedule deleted successfully.'
        ]);
    }

    /**
     * Update break periods for a specific time schedule
     */
    public function updateBreakPeriods(Request $request, TimeSchedule $timeSchedule)
    {
        $this->authorize('edit settings');

        $request->validate([
            'break_duration_minutes' => 'nullable|integer|min:0|max:480',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
        ]);

        // Validate break times if provided
        if ($request->break_start && $request->break_end) {
            $timeIn = Carbon::createFromFormat('H:i', $timeSchedule->time_in);
            $timeOut = Carbon::createFromFormat('H:i', $timeSchedule->time_out);
            $breakStart = Carbon::createFromFormat('H:i', $request->break_start);
            $breakEnd = Carbon::createFromFormat('H:i', $request->break_end);

            if (
                $breakStart->lt($timeIn) || $breakStart->gt($timeOut) ||
                $breakEnd->lt($timeIn) || $breakEnd->gt($timeOut) ||
                $breakEnd->lt($breakStart)
            ) {
                return response()->json([
                    'message' => 'Break times must be within work hours and break end must be after break start.',
                    'errors' => [
                        'break_start' => ['Break times must be within work hours.'],
                        'break_end' => ['Break end must be after break start.']
                    ]
                ], 422);
            }
        }

        $timeSchedule->update([
            'break_duration_minutes' => $request->break_duration_minutes,
            'break_start' => $request->break_start,
            'break_end' => $request->break_end,
        ]);

        return response()->json([
            'message' => 'Break periods updated successfully.',
            'data' => $timeSchedule
        ]);
    }
}
