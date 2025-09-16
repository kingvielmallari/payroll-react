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
    private $customErrors = [];

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
            // Find ACTIVE employee by employee number
            $employee = null;

            if (!empty($row['employee_number'])) {
                $employee = Employee::with(['user', 'daySchedule'])
                    ->where('employee_number', $row['employee_number'])
                    ->where('employment_status', 'active')
                    ->first();
            }

            if (!$employee) {
                $this->errorCount++;
                $this->customErrors[] = "Active employee not found for employee number: " . ($row['employee_number'] ?? 'N/A');
                $this->skippedCount++;
                return null;
            }

            // Parse date - handle various formats
            $logDate = null;
            if (!empty($row['date'])) {
                try {
                    if (is_numeric($row['date'])) {
                        // Excel date number
                        $logDate = Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d'));
                    } else {
                        // String date - support multiple formats
                        $logDate = Carbon::parse($row['date']);
                    }
                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->customErrors[] = "Invalid date format: " . ($row['date'] ?? 'N/A') . " for employee: " . $employee->employee_number;
                    return null;
                }
            }

            if (!$logDate) {
                $this->errorCount++;
                $this->customErrors[] = "Missing date for employee: " . $employee->employee_number;
                return null;
            }

            // Check if record already exists
            $existingLog = TimeLog::where('employee_id', $employee->id)
                ->where('log_date', $logDate->format('Y-m-d'))
                ->first();

            if ($existingLog && !$this->overwriteExisting) {
                // Calculate completeness of current row vs existing
                $currentCompleteness = $this->calculateCompletenessScore($row);
                $existingCompleteness = $this->calculateExistingLogCompleteness($existingLog);

                // Only skip if existing record is more or equally complete
                if ($existingCompleteness >= $currentCompleteness) {
                    $this->skippedCount++;
                    return null;
                }
                // If current row is more complete, we'll update the existing record below
            }

            // Parse time values with enhanced format support (both 24-hour and 12-hour with AM/PM)
            $timeIn = $this->parseTimeEnhanced($row['time_in'] ?? null);
            $timeOut = $this->parseTimeEnhanced($row['time_out'] ?? null);
            $breakIn = $this->parseTimeEnhanced($row['break_in'] ?? null);
            $breakOut = $this->parseTimeEnhanced($row['break_out'] ?? null);

            // Validate basic time logic
            if ($timeIn && $timeOut) {
                $timeInCarbon = Carbon::createFromFormat('H:i', $timeIn);
                $timeOutCarbon = Carbon::createFromFormat('H:i', $timeOut);

                // Handle next day time out
                if ($timeOutCarbon->lt($timeInCarbon)) {
                    $timeOutCarbon->addDay();
                }
            }

            // Auto-detect log type based on date and employee schedule
            $isRestDay = $employee->daySchedule ? !$employee->daySchedule->isWorkingDay($logDate) : $logDate->isWeekend();

            // Check for active holidays
            $holiday = \App\Models\Holiday::where('date', $logDate->format('Y-m-d'))
                ->where('is_active', true)
                ->first();

            $logType = 'regular_workday'; // Default

            if ($holiday) {
                if (!$isRestDay && $holiday->type === 'regular') {
                    $logType = 'regular_holiday';
                } elseif (!$isRestDay && $holiday->type === 'special_non_working') {
                    $logType = 'special_holiday';
                } elseif ($isRestDay && $holiday->type === 'regular') {
                    $logType = 'rest_day_regular_holiday';
                } elseif ($isRestDay && $holiday->type === 'special_non_working') {
                    $logType = 'rest_day_special_holiday';
                }
            } elseif ($isRestDay) {
                $logType = 'rest_day';
            }

            // Calculate hours using same logic as bulk time logs creation
            $calculatedHours = $this->calculateHoursLikeBulkCreation($employee, $logDate, $timeIn, $timeOut, $breakIn, $breakOut, $logType);

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
                'log_type' => $logType,
                'is_holiday' => $holiday ? true : false,
                'is_rest_day' => $isRestDay,
                'creation_method' => 'imported',
                'remarks' => 'Imported from Excel/CSV',
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
            $this->customErrors[] = "Error processing row for employee " . ($row['employee_number'] ?? 'N/A') . ": " . $e->getMessage();
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
     * Parse time string into H:i format - Enhanced to support multiple formats
     * Supports: 8:00 AM, 8:00PM, 08:00, 17:00, etc.
     */
    private function parseTimeEnhanced($timeValue)
    {
        if (empty($timeValue)) {
            return null;
        }

        try {
            // Handle Excel time format (decimal)
            if (is_numeric($timeValue)) {
                $time = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($timeValue);
                return $time->format('H:i');
            }

            // Clean the input string
            $cleanTime = trim($timeValue);

            // Handle various string time formats
            // Support formats like: "8:00 AM", "8:00PM", "08:00", "17:00", "8AM", "5PM"

            // Try to parse with Carbon (handles most formats)
            try {
                $time = Carbon::parse($cleanTime);
                return $time->format('H:i');
            } catch (\Exception $e) {
                // Try manual parsing for edge cases

                // Remove extra spaces and make uppercase
                $cleanTime = preg_replace('/\s+/', ' ', strtoupper(trim($cleanTime)));

                // Handle formats like "8AM" or "5PM" (without colon)
                if (preg_match('/^(\d{1,2})\s*(AM|PM)$/i', $cleanTime, $matches)) {
                    $hour = (int)$matches[1];
                    $ampm = strtoupper($matches[2]);

                    if ($ampm === 'PM' && $hour !== 12) {
                        $hour += 12;
                    } elseif ($ampm === 'AM' && $hour === 12) {
                        $hour = 0;
                    }

                    return sprintf('%02d:00', $hour);
                }

                // Handle formats like "8:30AM" or "5:15PM" 
                if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $cleanTime, $matches)) {
                    $hour = (int)$matches[1];
                    $minute = (int)$matches[2];
                    $ampm = strtoupper($matches[3]);

                    if ($ampm === 'PM' && $hour !== 12) {
                        $hour += 12;
                    } elseif ($ampm === 'AM' && $hour === 12) {
                        $hour = 0;
                    }

                    return sprintf('%02d:%02d', $hour, $minute);
                }

                // Handle 24-hour format like "08:00" or "17:30"
                if (preg_match('/^(\d{1,2}):(\d{2})$/', $cleanTime, $matches)) {
                    $hour = (int)$matches[1];
                    $minute = (int)$matches[2];

                    if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                        return sprintf('%02d:%02d', $hour, $minute);
                    }
                }

                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate hours using the same logic as bulk time logs creation
     */
    private function calculateHoursLikeBulkCreation($employee, $logDate, $timeIn, $timeOut, $breakIn, $breakOut, $logType)
    {
        // Initialize default values
        $totalHours = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $lateHours = 0;
        $undertimeHours = 0;

        // Calculate hours only if we have time_in and time_out
        if ($timeIn && $timeOut) {
            try {
                $timeInCarbon = Carbon::createFromFormat('H:i', $timeIn);
                $timeOutCarbon = Carbon::createFromFormat('H:i', $timeOut);

                // Handle next day checkout
                if ($timeOutCarbon->lt($timeInCarbon)) {
                    $timeOutCarbon->addDay();
                }

                $totalMinutes = $timeInCarbon->diffInMinutes($timeOutCarbon);

                // Deduct break time
                if ($breakIn && $breakOut) {
                    $breakInCarbon = Carbon::createFromFormat('H:i', $breakIn);
                    $breakOutCarbon = Carbon::createFromFormat('H:i', $breakOut);

                    if ($breakOutCarbon->gt($breakInCarbon)) {
                        $breakMinutes = $breakInCarbon->diffInMinutes($breakOutCarbon);
                        $totalMinutes -= $breakMinutes;
                    }
                } else {
                    // Auto-deduct 1 hour break if not specified (same as bulk creation)
                    $totalMinutes -= 60;
                }

                $totalHours = max(0, $totalMinutes / 60);

                // Standard work hours (same as bulk creation)
                $standardHours = 8;

                if ($totalHours <= $standardHours) {
                    $regularHours = $totalHours;
                } else {
                    $regularHours = $standardHours;
                    $overtimeHours = $totalHours - $standardHours;
                }

                // Calculate late hours (if applicable)
                if ($employee->daySchedule) {
                    $dayOfWeek = $logDate->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
                    $scheduleStartTime = null;

                    switch ($dayOfWeek) {
                        case 1:
                            $scheduleStartTime = $employee->daySchedule->monday_start;
                            break;
                        case 2:
                            $scheduleStartTime = $employee->daySchedule->tuesday_start;
                            break;
                        case 3:
                            $scheduleStartTime = $employee->daySchedule->wednesday_start;
                            break;
                        case 4:
                            $scheduleStartTime = $employee->daySchedule->thursday_start;
                            break;
                        case 5:
                            $scheduleStartTime = $employee->daySchedule->friday_start;
                            break;
                        case 6:
                            $scheduleStartTime = $employee->daySchedule->saturday_start;
                            break;
                        case 0:
                            $scheduleStartTime = $employee->daySchedule->sunday_start;
                            break;
                    }

                    if ($scheduleStartTime) {
                        $scheduledStart = Carbon::createFromFormat('H:i:s', $scheduleStartTime);
                        $actualStart = Carbon::createFromFormat('H:i', $timeIn);

                        if ($actualStart->gt($scheduledStart)) {
                            $lateMinutes = $scheduledStart->diffInMinutes($actualStart);
                            $lateHours = $lateMinutes / 60;
                        }
                    }
                }

                // Calculate undertime (if applicable)
                if ($totalHours < $standardHours) {
                    $undertimeHours = $standardHours - $totalHours;
                }
            } catch (\Exception $e) {
                // If any calculation fails, return zeros
                return [
                    'total_hours' => 0,
                    'regular_hours' => 0,
                    'overtime_hours' => 0,
                    'late_hours' => 0,
                    'undertime_hours' => 0,
                ];
            }
        }

        return [
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'late_hours' => round($lateHours, 2),
            'undertime_hours' => round($undertimeHours, 2),
        ];
    }

    /**
     * Validation rules for each row
     */
    public function rules(): array
    {
        return [
            'employee_number' => 'required',
            'date' => 'required',
            'time_in' => 'nullable',
            'time_out' => 'nullable',
            'break_in' => 'nullable',
            'break_out' => 'nullable',
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
        return $this->customErrors;
    }

    /**
     * Calculate completeness score of a row (higher score = more complete)
     */
    private function calculateCompletenessScore(array $row)
    {
        $score = 0;

        // Basic required fields
        if (!empty($row['employee_number'])) $score += 10;
        if (!empty($row['date'])) $score += 10;

        // Time fields (more important)
        if (!empty($row['time_in'])) $score += 20;
        if (!empty($row['time_out'])) $score += 20;

        // Break fields (less important but still valuable)
        if (!empty($row['break_in'])) $score += 5;
        if (!empty($row['break_out'])) $score += 5;

        return $score;
    }

    /**
     * Calculate completeness score of an existing TimeLog record
     */
    private function calculateExistingLogCompleteness(TimeLog $timeLog)
    {
        $score = 20; // Employee and date are always present for existing records

        // Time fields (more important)
        if (!empty($timeLog->time_in)) $score += 20;
        if (!empty($timeLog->time_out)) $score += 20;

        // Break fields (less important but still valuable)
        if (!empty($timeLog->break_in)) $score += 5;
        if (!empty($timeLog->break_out)) $score += 5;

        return $score;
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

        // Get grace period settings (except overtime threshold)
        $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
        $lateGracePeriodMinutes = $gracePeriodSettings->late_grace_minutes;
        $undertimeGracePeriodMinutes = $gracePeriodSettings->undertime_grace_minutes;

        // NEW: Use schedule-specific overtime threshold instead of global setting
        $overtimeThresholdMinutes = $timeSchedule ? $timeSchedule->getOvertimeThresholdMinutes() : 480; // Default 8 hours

        // STEP 1: Calculate work period based on employee schedule boundaries and grace period
        // Apply late grace period for ALL day types (regular days, rest days, holidays): 
        // if employee is late but within grace period, treat as if they came in at scheduled time
        $workStartTime = $schedStart; // Default to scheduled start time

        if ($actualTimeIn->gt($schedStart)) {
            $lateMinutes = $schedStart->diffInMinutes($actualTimeIn);
            if ($lateMinutes > $lateGracePeriodMinutes) {
                // Beyond grace period, use actual time in
                $workStartTime = $actualTimeIn;
            }
            // If within grace period, keep workStartTime as scheduled start time
        } else {
            // Employee came in early or on time, use scheduled start time
            $workStartTime = $schedStart;
        }

        // Work end time - apply undertime grace period logic
        $workEndTime = $actualTimeOut;

        // Apply undertime grace period: if employee left early but within grace period,
        // treat as if they left at scheduled time
        if ($actualTimeOut->lt($schedEnd)) {
            $earlyMinutes = $actualTimeOut->diffInMinutes($schedEnd);
            if ($earlyMinutes <= $undertimeGracePeriodMinutes) {
                // Within grace period, use scheduled end time for calculation
                $workEndTime = $schedEnd;
            }
            // If beyond grace period, use actual time out
        }

        // STEP 2: Calculate working time based on employee's schedule break configuration
        $breakMinutesToDeduct = 0;
        $adjustedWorkEndTime = $workEndTime;

        // Get employee's time schedule for break configuration
        $timeSchedule = $employee->timeSchedule ?? null;

        // Check if employee has actual break in/out logs
        $hasActualBreakLogs = ($timeLog->break_in && $timeLog->break_out);

        if ($timeSchedule) {
            // Check schedule break configuration type
            $hasFlexibleBreak = ($timeSchedule->break_duration_minutes && $timeSchedule->break_duration_minutes > 0);
            $hasFixedBreak = ($timeSchedule->break_start && $timeSchedule->break_end);

            if ($hasFlexibleBreak && !$hasFixedBreak) {
                // ===== FLEXIBLE BREAK LOGIC (ALWAYS USE DURATION DEDUCTION) =====
                // Flexible break employees NEVER use actual break logs - always use scheduled duration
                $breakMinutesToDeduct = $timeSchedule->break_duration_minutes;
            } else if ($hasFixedBreak) {
                // FIXED BREAK: Handle based on whether employee has break logs
                $breakStart = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeSchedule->break_start->format('H:i'));
                $breakEnd = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeSchedule->break_end->format('H:i'));

                if ($hasActualBreakLogs) {
                    // Employee has break logs - use hybrid approach
                    // This will be handled in the split calculation below
                    $breakMinutesToDeduct = 0; // Don't deduct, will split calculation

                } else {
                    // No employee break logs - use scheduled break window
                    // Handle scheduled break logic for fixed breaks
                    if ($workStartTime->lt($breakStart) && $workEndTime->gt($breakEnd)) {
                        // Employee worked before break started AND after break ended - exclude break window
                        $breakMinutesToDeduct = 0; // Will be handled by adjusting work times
                        // Split calculation around break window
                    } else if ($workStartTime->lt($breakStart) && $workEndTime->lte($breakEnd)) {
                        // Employee left at or during break period - only count time before break
                        $adjustedWorkEndTime = $breakStart;
                    } else if ($workStartTime->gte($breakStart) && $workStartTime->lt($breakEnd)) {
                        // Employee came during or after break period - start counting from break end
                        $workStartTime = $breakEnd;
                    }
                    // Case 4: Employee came after break ended - no adjustment needed
                }
            }
            // If no break configured, no adjustments needed

        } else if ($hasActualBreakLogs) {
            // No schedule but has break logs - use actual break times
            $breakIn = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->break_in);
            $breakOut = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->break_out);

            if ($breakOut->gt($breakIn)) {
                $breakMinutesToDeduct = $breakIn->diffInMinutes($breakOut);
            }
        }

        // STEP 3: Calculate total working hours based on break configuration
        if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end && $hasActualBreakLogs) {
            // Fixed break with employee break logs - use hybrid calculation
            $schedBreakStart = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeSchedule->break_start->format('H:i'));
            $empBreakIn = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->break_in);
            $empBreakOut = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeLog->break_out);

            // Calculate time before break (stop at the EARLIER of: scheduled break start OR employee break in)
            $beforeBreak = 0;
            $stopCountingAt = min($schedBreakStart, $empBreakIn);
            if ($workStartTime->lt($stopCountingAt)) {
                $beforeBreak = $workStartTime->diffInMinutes(min($stopCountingAt, $workEndTime));
            }

            // Calculate time from employee's break out to work end
            $afterEmployeeBreak = 0;
            if ($workEndTime->gt($empBreakOut)) {
                $afterEmployeeBreak = $empBreakOut->diffInMinutes($workEndTime);
            }

            $rawWorkingMinutes = $beforeBreak + $afterEmployeeBreak;
        } else if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end && !$hasActualBreakLogs) {
            // Fixed break without employee break logs - split around break window
            $breakStart = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeSchedule->break_start->format('H:i'));
            $breakEnd = Carbon::parse($logDate->format('Y-m-d') . ' ' . $timeSchedule->break_end->format('H:i'));

            $beforeBreak = 0;
            $afterBreak = 0;

            // Time worked before break period
            if ($workStartTime->lt($breakStart)) {
                $beforeBreak = $workStartTime->diffInMinutes(min($breakStart, $workEndTime));
            }

            // Time worked after break period
            if ($workEndTime->gt($breakEnd)) {
                $afterBreakStart = max($breakEnd, $workStartTime);
                $afterBreak = $afterBreakStart->diffInMinutes($workEndTime);
            }

            $rawWorkingMinutes = $beforeBreak + $afterBreak;
        } else {
            // Standard calculation with deduction
            $rawWorkingMinutes = $workStartTime->diffInMinutes($adjustedWorkEndTime);
        }

        $totalWorkingMinutes = max(0, $rawWorkingMinutes - $breakMinutesToDeduct);
        $totalHours = $totalWorkingMinutes / 60;

        // STEP 4: Calculate late hours (consistent with grace period logic for ALL day types)
        $lateMinutes = 0;
        if ($actualTimeIn->gt($schedStart)) {
            $actualLateMinutes = $schedStart->diffInMinutes($actualTimeIn);

            // Only count late hours if beyond grace period (same logic as work start time)
            if ($actualLateMinutes > $lateGracePeriodMinutes) {
                // Only charge for the time beyond the grace period
                $lateMinutes = $actualLateMinutes - $lateGracePeriodMinutes;
            }
            // If within grace period, lateMinutes stays 0
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
        $regularOvertimeHours = 0;
        $nightDifferentialOvertimeHours = 0;
        $overtimeThresholdHours = $overtimeThresholdMinutes / 60;

        if ($totalHours > $overtimeThresholdHours) {
            // Everything over threshold is overtime
            $overtimeHours = $totalHours - $overtimeThresholdHours;

            // Calculate night differential period for overtime hours only
            $nightDiffBreakdown = $this->calculateNightDifferentialHours($workStartTime, $adjustedWorkEndTime, $overtimeThresholdHours);
            $regularOvertimeHours = $nightDiffBreakdown['regular_overtime'];
            $nightDifferentialOvertimeHours = $nightDiffBreakdown['night_diff_overtime'];
        }

        // STEP 7: Calculate regular hours (total - overtime, capped at standard)
        $regularHours = $totalHours - $overtimeHours;
        $regularHours = min($regularHours, $standardHours);

        // STEP 8: Calculate night differential for regular hours
        $nightDiffRegularHours = 0;
        if ($regularHours > 0) {
            $regularWorkEndTime = $workStartTime->copy()->addHours($regularHours);
            $nightDiffRegularBreakdown = $this->calculateNightDifferentialForRegularHours($workStartTime, $regularWorkEndTime);
            $nightDiffRegularHours = $nightDiffRegularBreakdown['night_diff_regular_hours'];

            // Adjust regular hours to exclude ND hours (they're tracked separately)
            $regularHours = $regularHours - $nightDiffRegularHours;
        }

        // STEP 9: Calculate undertime hours with grace period
        $undertimeHours = 0;
        if ($timeSchedule) {
            $actualTimeOut = $adjustedWorkEndTime ?? $workEndTime;

            // Check if employee left early
            if ($actualTimeOut->lt($schedEnd)) {
                $earlyMinutes = $actualTimeOut->diffInMinutes($schedEnd);

                // Apply undertime grace period
                if ($earlyMinutes > $undertimeGracePeriodMinutes) {
                    $shortfallMinutes = $earlyMinutes - $undertimeGracePeriodMinutes;
                    $undertimeHours = $shortfallMinutes / 60;
                }
            }
        }

        $lateHours = $lateMinutes / 60;

        return [
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($regularHours, 2),
            'night_diff_regular_hours' => round($nightDiffRegularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'regular_overtime_hours' => round($regularOvertimeHours, 2),
            'night_diff_overtime_hours' => round($nightDifferentialOvertimeHours, 2),
            'late_hours' => round($lateHours, 2),
            'undertime_hours' => round($undertimeHours, 2),
        ];
    }

    /**
     * Calculate night differential hours breakdown for regular hours
     */
    private function calculateNightDifferentialForRegularHours($workStartTime, $workEndTime)
    {
        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();

        if (!$nightDiffSetting || !$nightDiffSetting->is_active) {
            // No night differential configured
            return [
                'night_diff_regular_hours' => 0
            ];
        }

        // Get night differential time period
        $nightStart = \Carbon\Carbon::parse($workStartTime->format('Y-m-d') . ' ' . $nightDiffSetting->start_time);
        $nightEnd = \Carbon\Carbon::parse($workStartTime->format('Y-m-d') . ' ' . $nightDiffSetting->end_time);

        // Handle next day end time (e.g., 10 PM to 5 AM next day)
        if ($nightEnd->lte($nightStart)) {
            $nightEnd->addDay();
        }

        // Calculate overlap between regular work period and night differential period
        $overlapStart = $workStartTime->greaterThan($nightStart) ? $workStartTime : $nightStart;
        $overlapEnd = $workEndTime->lessThan($nightEnd) ? $workEndTime : $nightEnd;

        $nightDiffRegularHours = 0;
        if ($overlapStart->lessThan($overlapEnd)) {
            $nightDiffRegularHours = $overlapEnd->diffInHours($overlapStart, true);
        }

        return [
            'night_diff_regular_hours' => $nightDiffRegularHours
        ];
    }

    /**
     * Calculate night differential hours breakdown for overtime
     */
    private function calculateNightDifferentialHours($workStartTime, $workEndTime, $overtimeThresholdHours)
    {
        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();

        if (!$nightDiffSetting || !$nightDiffSetting->is_active) {
            // No night differential configured, all overtime is regular
            $totalOvertimeHours = $workStartTime->copy()->addHours($overtimeThresholdHours)->diffInHours($workEndTime, true);
            return [
                'regular_overtime' => $totalOvertimeHours,
                'night_diff_overtime' => 0
            ];
        }

        // Get night differential time period
        $nightStart = \Carbon\Carbon::parse($workStartTime->format('Y-m-d') . ' ' . $nightDiffSetting->start_time);
        $nightEnd = \Carbon\Carbon::parse($workStartTime->format('Y-m-d') . ' ' . $nightDiffSetting->end_time);

        // Handle next day end time (e.g., 10 PM to 5 AM next day)
        if ($nightEnd->lte($nightStart)) {
            $nightEnd->addDay();
        }

        // Calculate overtime period start (threshold hours after work start)
        $overtimeStart = $workStartTime->copy()->addHours($overtimeThresholdHours);

        // If overtime starts after work ends, no overtime
        if ($overtimeStart->gte($workEndTime)) {
            return [
                'regular_overtime' => 0,
                'night_diff_overtime' => 0
            ];
        }

        // Calculate overlap between overtime period and night differential period
        $overlapStart = $overtimeStart->greaterThan($nightStart) ? $overtimeStart : $nightStart;
        $overlapEnd = $workEndTime->lessThan($nightEnd) ? $workEndTime : $nightEnd;

        $nightDiffOvertimeHours = 0;
        if ($overlapStart->lessThan($overlapEnd)) {
            $nightDiffOvertimeHours = $overlapEnd->diffInHours($overlapStart, true);
        }

        // Total overtime hours
        $totalOvertimeHours = $overtimeStart->diffInHours($workEndTime, true);

        // Regular overtime hours = total overtime - night diff overtime
        $regularOvertimeHours = max(0, $totalOvertimeHours - $nightDiffOvertimeHours);

        return [
            'regular_overtime' => $regularOvertimeHours,
            'night_diff_overtime' => $nightDiffOvertimeHours
        ];
    }
}
