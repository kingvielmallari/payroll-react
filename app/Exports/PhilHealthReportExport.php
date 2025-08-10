<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PhilHealthReportExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $summary;
    protected $period;

    public function __construct($data, $summary, $period)
    {
        $this->data = $data;
        $this->summary = $summary;
        $this->period = $period;
    }

    public function array(): array
    {
        $rows = [];
        
        // Add company info header
        $rows[] = ['PHILHEALTH FORM RF-1 - MONTHLY REMITTANCE FORM'];
        $rows[] = ['Period: ' . $this->period];
        $rows[] = ['Company: ' . config('company.company_name', 'Company Name')];
        $rows[] = ['PhilHealth Employer PEN: ' . config('company.philhealth_pen', 'XX-XXXXXXXXX-X')];
        $rows[] = [''];
        
        // Employee details
        foreach ($this->data as $employee) {
            $rows[] = [
                $employee['employee_id'],
                $employee['name'],
                $employee['philhealth_number'],
                $employee['membership_category'],
                number_format($employee['monthly_basic_salary'], 2),
                number_format($employee['premium_contribution'], 2),
                number_format($employee['employee_share'], 2),
                number_format($employee['employer_share'], 2),
                $employee['contribution_period'],
                $employee['payment_status'],
            ];
        }
        
        // Add summary section
        $rows[] = [''];
        $rows[] = ['SUMMARY'];
        $rows[] = ['Total Monthly Basic Salary', number_format($this->summary['total_basic_salary'], 2)];
        $rows[] = ['Total Premium Contribution', number_format($this->summary['total_premium_contribution'], 2)];
        $rows[] = ['Total Employee Share', number_format($this->summary['total_employee_share'], 2)];
        $rows[] = ['Total Employer Share', number_format($this->summary['total_employer_share'], 2)];
        $rows[] = ['Total Remittance', number_format($this->summary['total_remittance'], 2)];
        $rows[] = ['Total Active Members', $this->summary['total_members']];
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'PhilHealth Number',
            'Membership Category',
            'Monthly Basic Salary',
            'Premium Contribution',
            'Employee Share',
            'Employer Share',
            'Contribution Period',
            'Payment Status'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFD5EDDA',
                ],
            ],
        ]);

        // Style the company info section
        $sheet->getStyle('A2:A4')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
        ]);

        // Style the column headers
        $sheet->getStyle('A6:J6')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFEAF6ED',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Apply borders to data rows
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A6:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge title cell
        $sheet->mergeCells('A1:J1');

        return [];
    }

    public function title(): string
    {
        return 'PhilHealth RF-1 ' . $this->period;
    }
}
