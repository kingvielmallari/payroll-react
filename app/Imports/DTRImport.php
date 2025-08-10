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
            // Find employee by employee number or email
            $employee = null;
            
            if (!empty($row['employee_number'])) {
                $employee = Employee::where('employee_number', $row['employee_number'])->first();
            } elseif (!empty($row['email'])) {
                $employee = Employee::whereHas('user', function($query) use ($row) {
                    $query->where('email', $row['email']);
                })->first();
            }

            if (!$employee) {
                $this->errorCount++;
                $this->errors[] = "Employee not found for row: " . json_encode($row);
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

            // Calculate hours with automatic break deduction if break times are missing
            $calculatedHours = $this->calculateWorkingHours($timeIn, $timeOut, $breakIn, $breakOut);

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
                'status' => 'approved',
                'approved_by' => Auth::id() ?? 1,
                'approved_at' => now(),
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
            'employee_number' => 'required_without:email',
            'email' => 'required_without:employee_number',
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
            'employee_number.required_without' => 'Either employee number or email is required.',
            'email.required_without' => 'Either employee number or email is required.',
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
}
