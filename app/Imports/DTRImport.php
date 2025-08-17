<?php

namespace App\Imports;

use App\Models\TimeLog;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DTRImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    private $overwriteExisting;
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errorCount = 0;
    private $errors = [];

    public function __construct($overwriteExisting = false)
    {
        $this->overwriteExisting = $overwriteExisting;
    }

    /**
     * Transform each row into a TimeLog model
     */
    public function model(array $row)
    {
        try {
            // Find employee by employee number only
            $employee = null;

            if (!empty($row['employee_number'])) {
                $employee = Employee::where('employee_number', $row['employee_number'])->first();
            }

            if (!$employee) {
                $this->errorCount++;
                $this->errors[] = "Employee not found for employee number: " . ($row['employee_number'] ?? 'N/A') . " in row: " . json_encode($row);
                return null;
            }

            // Parse date
            $logDate = null;
            if (!empty($row['date'])) {
                try {
                    if (is_numeric($row['date'])) {
                        // Excel date number
                        $logDate = Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d'));
                    } else {
                        // String date
                        $logDate = Carbon::parse($row['date']);
                    }
                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->errors[] = "Invalid date format for row: " . json_encode($row);
                    return null;
                }
            }

            if (!$logDate) {
                $this->errorCount++;
                $this->errors[] = "Missing date for row: " . json_encode($row);
                return null;
            }

            // Check if record already exists
            $existingLog = TimeLog::where('employee_id', $employee->id)
                ->where('log_date', $logDate->format('Y-m-d'))
                ->first();

            if ($existingLog && !$this->overwriteExisting) {
                $this->skippedCount++;
                return null;
            }

            // Parse time values - now supports AM/PM format and 24-hour format
            $timeIn = $this->parseTime($row['time_in'] ?? null);
            $timeOut = $this->parseTime($row['time_out'] ?? null);
            $breakIn = $this->parseTime($row['break_in'] ?? null);
            $breakOut = $this->parseTime($row['break_out'] ?? null);

            // Calculate hours with dynamic calculation based on employee schedule
            $calculatedHours = $this->calculateWorkingHoursForEmployee($employee, $logDate, $timeIn, $timeOut, $breakIn, $breakOut);

            $timeLogData = [
                'employee_id' => $employee->id,
                'log_date' => $logDate->format('Y-m-d'),
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'break_in' => $breakIn,
                'break_out' => $breakOut,
                'total_hours' => $calculatedHours['total_hours'],
                'regular_hours' => $calculatedHours['regular_hours'],
                'overtime_hours' => $calculatedHours['overtime_hours'],
                'late_hours' => $calculatedHours['late_hours'],
                'undertime_hours' => $calculatedHours['undertime_hours'],
                'log_type' => 'regular',
                'creation_method' => 'imported',
                'remarks' => $row['remarks'] ?? null,
            ];

            if ($existingLog) {
                $existingLog->update($timeLogData);
                $this->importedCount++;
                return null;
            } else {
                $this->importedCount++;
                return new TimeLog($timeLogData);
            }
        } catch (\Exception $e) {
            $this->errorCount++;
            $this->errors[] = "Error processing row: " . $e->getMessage() . " - Row data: " . json_encode($row);
            return null;
        }
    }

    /**
     * Calculate working hours with automatic break deduction
     */
    private function calculateWorkingHours($timeIn, $timeOut, $breakIn, $breakOut)
    {
        if (!$timeIn || !$timeOut) {
            return [
                'total_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'late_hours' => 0,
                'undertime_hours' => 0,
            ];
        }

        $timeInCarbon = Carbon::parse($timeIn);
        $timeOutCarbon = Carbon::parse($timeOut);

        // Handle next day time out
        if ($timeOutCarbon->lt($timeInCarbon)) {
            $timeOutCarbon->addDay();
        }

        $totalMinutes = $timeOutCarbon->diffInMinutes($timeInCarbon);

        // Deduct break time
        if ($breakIn && $breakOut) {
            $breakInCarbon = Carbon::parse($breakIn);
            $breakOutCarbon = Carbon::parse($breakOut);

            if ($breakOutCarbon->gt($breakInCarbon)) {
                $breakMinutes = $breakOutCarbon->diffInMinutes($breakInCarbon);
                $totalMinutes -= $breakMinutes;
            }
        } else {
            // If no break times provided, automatically deduct 1 hour (60 minutes)
            $totalMinutes -= 60;
        }

        $totalHours = max(0, $totalMinutes / 60); // Ensure no negative hours

        // Standard work hours (8 hours)
        $standardHours = 8;

        $regularHours = min($totalHours, $standardHours);
        $overtimeHours = max(0, $totalHours - $standardHours);

        // Calculate late hours (assuming standard start time is 8:00 AM)
        $standardStartTime = Carbon::parse('08:00:00');
        $lateMinutes = max(0, $timeInCarbon->diffInMinutes($standardStartTime));
        $lateHours = $lateMinutes / 60;

        // Calculate undertime hours
        $undertimeHours = max(0, $standardHours - $totalHours);

        return [
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'late_hours' => round($lateHours, 2),
            'undertime_hours' => round($undertimeHours, 2),
        ];
    }

    /**
     * Parse time string into H:i:s format - supports AM/PM and 24-hour format
     */
    private function parseTime($timeValue)
    {
        if (empty($timeValue)) {
            return null;
        }

        try {
            // Handle Excel time format (decimal)
            if (is_numeric($timeValue)) {
                $timeValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($timeValue)->format('H:i:s');
            } else {
                // Handle string time formats - both 12-hour and 24-hour
                $time = Carbon::parse($timeValue);
                $timeValue = $time->format('H:i:s');
            }

            return $timeValue;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validation rules for each row
     */
    public function rules(): array
    {
        return [
            'employee_number' => 'required',
            'date' => 'required',
            'time_in' => 'required',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'employee_number.required' => 'Employee number is required.',
            'date.required' => 'Date is required.',
            'time_in.required' => 'Time in is required.',
        ];
    }

    /**
     * Get the number of imported records
     */
    public function getImportedCount()
    {
        return $this->importedCount;
    }

    /**
     * Get the number of skipped records
     */
    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    /**
     * Get the number of error records
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * Get error messages
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Calculate working hours with dynamic calculation based on employee schedule
     */
    private function calculateWorkingHoursForEmployee($employee, $logDate, $timeIn, $timeOut, $breakIn, $breakOut)
    {
        if (!$timeIn || !$timeOut) {
            return [
                'total_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'late_hours' => 0,
                'undertime_hours' => 0,
            ];
        }

        // Create a temporary TimeLog object for calculation
        $tempTimeLog = new \App\Models\TimeLog([
            'employee_id' => $employee->id,
            'log_date' => $logDate->format('Y-m-d'),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'break_in' => $breakIn,
            'break_out' => $breakOut,
        ]);
        $tempTimeLog->setRelation('employee', $employee);

        // Use the same dynamic calculation method as TimeLogController
        return $this->calculateDynamicWorkingHours($tempTimeLog);
    }

    /**
     * Calculate working hours dynamically based on employee schedule and grace periods
     * (Same logic as TimeLogController)
     */
    private function calculateDynamicWorkingHours(\App\Models\TimeLog $timeLog)
    {
        // Parse times properly - handle both string and Carbon objects
        $logDate = $timeLog->log_date instanceof Carbon ? $timeLog->log_date : Carbon::parse($timeLog->log_date);

        $actualTimeIn = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->time_in);
        $actualTimeOut = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->time_out);

        // Handle next day time out
        if ($actualTimeOut->lt($actualTimeIn)) {
            $actualTimeOut->addDay();
        }

        // Get employee's time schedule
        $employee = $timeLog->employee;
        $timeSchedule = $employee->timeSchedule ?? null;

        // Get scheduled times - use 8-5 as default if no schedule
        $scheduledStartTime = $timeSchedule ? $timeSchedule->time_in->format('H:i') : '08:00';
        $scheduledEndTime = $timeSchedule ? $timeSchedule->time_out->format('H:i') : '17:00';

        $schedStart = Carbon::parse($logDate->format('Y-m-d') . ' ' . $scheduledStartTime);
        $schedEnd = Carbon::parse($logDate->format('Y-m-d') . ' ' . $scheduledEndTime);

        // Handle next day scheduled end time
        if ($schedEnd->lt($schedStart)) {
            $schedEnd->addDay();
        }

        // Get grace period settings
        $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
        $lateGracePeriodMinutes = $gracePeriodSettings->late_grace_minutes;
        $undertimeGracePeriodMinutes = $gracePeriodSettings->undertime_grace_minutes;
        $overtimeThresholdMinutes = $gracePeriodSettings->overtime_threshold_minutes;

        // STEP 1: Calculate work period based on employee schedule boundaries
        // If employee comes in early, start counting from scheduled start time
        $workStartTime = $actualTimeIn->gt($schedStart) ? $actualTimeIn : $schedStart;

        // Work end time is the actual time out
        $workEndTime = $actualTimeOut;

        // STEP 2: Calculate working time with proper break handling
        $breakMinutesToDeduct = 0;
        $adjustedWorkEndTime = $workEndTime;

        if ($timeLog->break_in && $timeLog->break_out) {
            // If manual break times provided, use them
            $breakIn = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->break_in);
            $breakOut = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->break_out);

            if ($breakOut->gt($breakIn)) {
                $breakMinutesToDeduct = $breakIn->diffInMinutes($breakOut);
            }
        } else if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
            // Handle scheduled break logic
            $breakStart = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeSchedule->break_start->format('H:i'));
            $breakEnd = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeSchedule->break_end->format('H:i'));

            // Case 1: Employee worked before break started AND after break ended - deduct full break
            if ($workStartTime->lt($breakStart) && $workEndTime->gt($breakEnd)) {
                $breakMinutesToDeduct = $breakStart->diffInMinutes($breakEnd);
            }
            // Case 2: Employee left at or during break period - only count time before break
            else if ($workStartTime->lt($breakStart) && $workEndTime->lte($breakEnd)) {
                // Only count work time before break started
                $adjustedWorkEndTime = $breakStart;
            }
            // Case 3: Employee came during or after break period - only count time after break
            else if ($workStartTime->gte($breakStart) && $workStartTime->lt($breakEnd)) {
                // Start counting from break end
                $workStartTime = $breakEnd;
            }
            // Case 4: Employee came after break ended - no adjustment needed
        }

        // STEP 3: Calculate total working hours
        $rawWorkingMinutes = $workStartTime->diffInMinutes($adjustedWorkEndTime);
        $totalWorkingMinutes = max(0, $rawWorkingMinutes - $breakMinutesToDeduct);
        $totalHours = $totalWorkingMinutes / 60;

        // STEP 4: Calculate late hours (with grace period)
        $lateMinutes = 0;
        if ($actualTimeIn->gt($schedStart)) {
            $actualLateMinutes = $schedStart->diffInMinutes($actualTimeIn);

            // Apply grace period
            if ($actualLateMinutes > $lateGracePeriodMinutes) {
                $lateMinutes = $actualLateMinutes;
            }
        }

        // STEP 5: Calculate standard work hours (scheduled hours minus break)
        $standardWorkMinutes = $schedStart->diffInMinutes($schedEnd);
        if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
            $scheduledBreakMinutes = $timeSchedule->break_start->diffInMinutes($timeSchedule->break_end);
            $standardWorkMinutes -= $scheduledBreakMinutes;
        }
        $standardHours = max(0, $standardWorkMinutes / 60);

        // STEP 6: Calculate overtime based on threshold
        $overtimeHours = 0;
        $overtimeThresholdHours = $overtimeThresholdMinutes / 60;

        if ($totalHours > $overtimeThresholdHours) {
            // Everything over threshold is overtime
            $overtimeHours = $totalHours - $overtimeThresholdHours;
        }

        // STEP 7: Calculate regular hours (total - overtime, capped at standard)
        $regularHours = $totalHours - $overtimeHours;
        $regularHours = min($regularHours, $standardHours);

        // STEP 8: Calculate undertime (with grace period)
        $undertimeHours = 0;
        if ($totalHours < $standardHours) {
            $actualUndertimeMinutes = ($standardHours - $totalHours) * 60;

            if ($actualUndertimeMinutes > $undertimeGracePeriodMinutes) {
                $undertimeHours = ($actualUndertimeMinutes - $undertimeGracePeriodMinutes) / 60;
            }
        }

        $lateHours = $lateMinutes / 60;

        return [
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'late_hours' => round($lateHours, 2),
            'undertime_hours' => round($undertimeHours, 2),
        ];
    }
}
