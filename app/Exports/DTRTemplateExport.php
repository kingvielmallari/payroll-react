<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DTRTemplateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * Return a collection of sample data for the template
     */
    public function collection()
    {
        // Get first 3 active employees as examples
        $employees = Employee::with('user')
                            ->where('employment_status', 'active')
                            ->take(3)
                            ->get();

        $sampleData = collect();

        foreach ($employees as $employee) {
            // Add 5 sample days for each employee
            for ($i = 1; $i <= 5; $i++) {
                $date = now()->subDays($i)->format('Y-m-d');
                
                $sampleData->push([
                    'employee_number' => $employee->employee_number,
                    'email' => $employee->user->email ?? '',
                    'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                    'date' => $date,
                    'time_in' => '08:00',
                    'time_out' => '17:00',
                    'break_in' => '12:00',
                    'break_out' => '13:00',
                    'log_type' => 'regular',
                    'is_holiday' => 'no',
                    'is_rest_day' => 'no',
                    'remarks' => '',
                ]);
            }
        }

        // Add empty rows for users to fill
        for ($i = 0; $i < 10; $i++) {
            $sampleData->push([
                'employee_number' => '',
                'email' => '',
                'employee_name' => '',
                'date' => '',
                'time_in' => '',
                'time_out' => '',
                'break_in' => '',
                'break_out' => '',
                'log_type' => 'regular',
                'is_holiday' => 'no',
                'is_rest_day' => 'no',
                'remarks' => '',
            ]);
        }

        return $sampleData;
    }

    /**
     * Define the headings for the Excel file
     */
    public function headings(): array
    {
        return [
            'Employee Number',
            'Email',
            'Employee Name',
            'Date (YYYY-MM-DD)',
            'Time In (HH:MM)',
            'Time Out (HH:MM)',
            'Break In (HH:MM)',
            'Break Out (HH:MM)',
            'Log Type',
            'Is Holiday (yes/no)',
            'Is Rest Day (yes/no)',
            'Remarks',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            
            // Style all cells
            'A:L' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            // Style date column
            'D:D' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],

            // Style time columns
            'E:H' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
