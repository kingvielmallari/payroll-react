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
        $tempTimeLog->employee = $employee;

        // Use the same dynamic calculation method as TimeLogController
        return $this->calculateDynamicWorkingHours($tempTimeLog);
    }

    /**
     * Calculate working hours dynamically based on employee schedule and grace periods
     * (Same logic as TimeLogController)
     */
    private function calculateDynamicWorkingHours(\App\Models\TimeLog $timeLog)
    {
        $timeIn = Carbon::parse($timeLog->log_date . ' ' . $timeLog->time_in);
        $timeOut = Carbon::parse($timeLog->log_date . ' ' . $timeLog->time_out);

        // Handle next day time out
        if ($timeOut->lt($timeIn)) {
            $timeOut->addDay();
        }

        // Get employee's time schedule
        $employee = $timeLog->employee;
        $timeSchedule = $employee->timeSchedule ?? null;

        // Default to 8-5 schedule if no time schedule is set
        $scheduledStartTime = $timeSchedule ? $timeSchedule->time_in->format('H:i') : '08:00';
        $scheduledEndTime = $timeSchedule ? $timeSchedule->time_out->format('H:i') : '17:00';

        // Calculate scheduled work hours
        $schedStart = Carbon::parse($timeLog->log_date . ' ' . $scheduledStartTime);
        $schedEnd = Carbon::parse($timeLog->log_date . ' ' . $scheduledEndTime);

        // Handle next day scheduled end time
        if ($schedEnd->lt($schedStart)) {
            $schedEnd->addDay();
        }

        // Get grace period settings from database
        $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
        $lateGracePeriodMinutes = $gracePeriodSettings->late_grace_minutes;
        $undertimeGracePeriodMinutes = $gracePeriodSettings->undertime_grace_minutes;
        $overtimeThresholdMinutes = $gracePeriodSettings->overtime_threshold_minutes;

        // Apply late grace period logic
        $lateMinutes = 0;
        $adjustedTimeIn = $timeIn;

        if ($timeIn->gt($schedStart)) {
            $actualLateMinutes = $timeIn->diffInMinutes($schedStart);

            if ($actualLateMinutes <= $lateGracePeriodMinutes) {
                // Within grace period - treat as on time, use scheduled start for calculation
                $adjustedTimeIn = $schedStart;
                $lateMinutes = 0;
            } else {
                // Beyond grace period - use actual time in, count full late minutes
                $adjustedTimeIn = $timeIn;
                $lateMinutes = $actualLateMinutes;
            }
        }

        // Calculate total minutes worked (this automatically accounts for late start if beyond grace period)
        $totalMinutes = $timeOut->diffInMinutes($adjustedTimeIn);

        // Subtract break time based on employee's time schedule
        if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
            // Use dynamic break period from time schedule
            $breakStart = Carbon::parse($timeLog->log_date . ' ' . $timeSchedule->break_start->format('H:i'));
            $breakEnd = Carbon::parse($timeLog->log_date . ' ' . $timeSchedule->break_end->format('H:i'));

            // Only deduct break if employee was present during break period
            if ($adjustedTimeIn->lte($breakStart) && $timeOut->gte($breakEnd)) {
                $breakMinutes = $breakEnd->diffInMinutes($breakStart);
                $totalMinutes -= $breakMinutes;
            } elseif ($adjustedTimeIn->between($breakStart, $breakEnd) || $timeOut->between($breakStart, $breakEnd)) {
                // Partial break period overlap
                $actualBreakStart = max($breakStart, $adjustedTimeIn);
                $actualBreakEnd = min($breakEnd, $timeOut);
                if ($actualBreakEnd->gt($actualBreakStart)) {
                    $partialBreakMinutes = $actualBreakEnd->diffInMinutes($actualBreakStart);
                    $totalMinutes -= $partialBreakMinutes;
                }
            }
        } elseif ($timeLog->break_in && $timeLog->break_out) {
            // Fallback to manual break times if no schedule break is set
            $breakIn = Carbon::parse($timeLog->log_date . ' ' . $timeLog->break_in);
            $breakOut = Carbon::parse($timeLog->log_date . ' ' . $timeLog->break_out);

            if ($breakOut->gt($breakIn)) {
                $breakMinutes = $breakOut->diffInMinutes($breakIn);
                $totalMinutes -= $breakMinutes;
            }
        } else {
            // If no break times provided and no schedule break, deduct 1 hour for backwards compatibility
            $totalMinutes -= 60;
        }

        $totalHours = max(0, $totalMinutes / 60);

        // Calculate scheduled work hours (including break deduction)
        $scheduledWorkMinutes = $schedEnd->diffInMinutes($schedStart);
        if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
            $scheduleBreakMinutes = $timeSchedule->break_end->diffInMinutes($timeSchedule->break_start);
            $scheduledWorkMinutes -= $scheduleBreakMinutes;
        } else {
            // Default break deduction for backwards compatibility
            $scheduledWorkMinutes -= 60;
        }
        $standardHours = max(0, $scheduledWorkMinutes / 60);

        // Calculate regular hours
        $regularHours = min($totalHours, $standardHours);

        // Apply undertime grace period
        $undertimeHours = 0;
        if ($totalHours < $standardHours) {
            $actualUndertimeMinutes = ($standardHours - $totalHours) * 60;

            if ($actualUndertimeMinutes > $undertimeGracePeriodMinutes) {
                // Beyond grace period - count undertime minutes minus grace period
                $undertimeHours = ($actualUndertimeMinutes - $undertimeGracePeriodMinutes) / 60;
            }
            // Within grace period - no undertime deduction
        }

        // Calculate overtime hours with threshold
        $overtimeHours = 0;
        $actualEndForOT = Carbon::parse($timeLog->log_date . ' ' . $timeLog->time_out);
        if ($actualEndForOT->lt($timeIn)) {
            $actualEndForOT->addDay();
        }

        if ($actualEndForOT->gt($schedEnd)) {
            $overtimeMinutes = $actualEndForOT->diffInMinutes($schedEnd);

            // Apply overtime threshold
            if ($overtimeMinutes > $overtimeThresholdMinutes) {
                $overtimeMinutes -= $overtimeThresholdMinutes;

                // Subtract break time from OT if break extends into OT period
                if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
                    $breakStart = Carbon::parse($timeLog->log_date . ' ' . $timeSchedule->break_start->format('H:i'));
                    $breakEnd = Carbon::parse($timeLog->log_date . ' ' . $timeSchedule->break_end->format('H:i'));

                    if ($breakStart->lt($actualEndForOT) && $breakEnd->gt($schedEnd)) {
                        $overlapStart = max($breakStart, $schedEnd);
                        $overlapEnd = min($breakEnd, $actualEndForOT);
                        if ($overlapEnd->gt($overlapStart)) {
                            $overlapMinutes = $overlapEnd->diffInMinutes($overlapStart);
                            $overtimeMinutes -= $overlapMinutes;
                        }
                    }
                }

                $overtimeHours = max(0, $overtimeMinutes / 60);
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
