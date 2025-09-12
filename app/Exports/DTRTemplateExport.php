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
                    'date' => $date,
                    'time_in' => '8:00 AM',
                    'time_out' => '5:00 PM',
                    'break_in' => '12:00 PM',
                    'break_out' => '1:00 PM',
                ]);
            }
        }

        // Add empty rows for users to fill
        for ($i = 0; $i < 20; $i++) {
            $sampleData->push([
                'employee_number' => '',
                'date' => '',
                'time_in' => '',
                'time_out' => '',
                'break_in' => '',
                'break_out' => '',
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
            'Date (YYYY-MM-DD)',
            'Time In (8:00 AM or 08:00)',
            'Time Out (5:00 PM or 17:00)',
            'Break In (12:00 PM or 12:00)',
            'Break Out (1:00 PM or 13:00)',
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
            'A:F' => [
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
            'B:B' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],

            // Style time columns
            'C:F' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
