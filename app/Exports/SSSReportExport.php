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

class SSSReportExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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
        $rows[] = ['SSS FORM R-3 - MONTHLY REMITTANCE REPORT'];
        $rows[] = ['Period: ' . $this->period];
        $rows[] = ['Company: ' . config('company.company_name', 'Company Name')];
        $rows[] = ['SSS Employer Number: ' . config('company.sss_employer_number', 'XX-XXXXXXX-X')];
        $rows[] = [''];
        
        // Employee details
        foreach ($this->data as $employee) {
            $rows[] = [
                $employee['employee_id'],
                $employee['name'],
                $employee['sss_number'],
                number_format($employee['monthly_salary_credit'], 2),
                number_format($employee['employee_contribution'], 2),
                number_format($employee['employer_contribution'], 2),
                number_format($employee['ec_contribution'], 2),
                number_format($employee['total_contribution'], 2),
                $employee['loan_status'],
                number_format($employee['salary_loan_payment'], 2),
                number_format($employee['calamity_loan_payment'], 2),
            ];
        }
        
        // Add summary section
        $rows[] = [''];
        $rows[] = ['SUMMARY'];
        $rows[] = ['Total Monthly Salary Credit', number_format($this->summary['total_salary_credit'], 2)];
        $rows[] = ['Total Employee Contribution', number_format($this->summary['total_employee_contribution'], 2)];
        $rows[] = ['Total Employer Contribution', number_format($this->summary['total_employer_contribution'], 2)];
        $rows[] = ['Total EC Contribution', number_format($this->summary['total_ec_contribution'], 2)];
        $rows[] = ['Total Contribution', number_format($this->summary['total_contribution'], 2)];
        $rows[] = ['Total Salary Loan Payments', number_format($this->summary['total_salary_loan'], 2)];
        $rows[] = ['Total Calamity Loan Payments', number_format($this->summary['total_calamity_loan'], 2)];
        $rows[] = ['Total Remittance', number_format($this->summary['total_remittance'], 2)];
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'SSS Number',
            'Monthly Salary Credit',
            'Employee Contribution',
            'Employer Contribution',
            'EC Contribution',
            'Total Contribution',
            'Loan Status',
            'Salary Loan Payment',
            'Calamity Loan Payment'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header
        $sheet->getStyle('A1:K1')->applyFromArray([
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
                    'argb' => 'FFD6EAF8',
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
        $sheet->getStyle('A6:K6')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFEBF3FD',
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
        $sheet->getStyle('A6:K' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge title cell
        $sheet->mergeCells('A1:K1');

        return [];
    }

    public function title(): string
    {
        return 'SSS R-3 ' . $this->period;
    }
}
