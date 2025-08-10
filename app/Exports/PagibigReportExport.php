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

class PagibigReportExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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
        $rows[] = ['PAG-IBIG MCRF - MONTHLY COLLECTION/REMITTANCE FORM'];
        $rows[] = ['Period: ' . $this->period];
        $rows[] = ['Company: ' . config('company.company_name', 'Company Name')];
        $rows[] = ['Pag-IBIG Employer Registration Number: ' . config('company.pagibig_ern', 'XXXXXXXXXXXX')];
        $rows[] = [''];
        
        // Employee details
        foreach ($this->data as $employee) {
            $rows[] = [
                $employee['employee_id'],
                $employee['name'],
                $employee['pagibig_number'],
                number_format($employee['monthly_compensation'], 2),
                number_format($employee['employee_contribution'], 2),
                number_format($employee['employer_contribution'], 2),
                number_format($employee['total_contribution'], 2),
                $employee['contribution_type'],
                $employee['loan_eligibility'] ? 'Eligible' : 'Not Eligible',
                number_format($employee['housing_loan_payment'], 2),
                number_format($employee['multi_purpose_loan_payment'], 2),
                number_format($employee['calamity_loan_payment'], 2),
            ];
        }
        
        // Add summary section
        $rows[] = [''];
        $rows[] = ['SUMMARY'];
        $rows[] = ['Total Monthly Compensation', number_format($this->summary['total_compensation'], 2)];
        $rows[] = ['Total Employee Contribution', number_format($this->summary['total_employee_contribution'], 2)];
        $rows[] = ['Total Employer Contribution', number_format($this->summary['total_employer_contribution'], 2)];
        $rows[] = ['Total Contribution', number_format($this->summary['total_contribution'], 2)];
        $rows[] = ['Total Housing Loan Payments', number_format($this->summary['total_housing_loan'], 2)];
        $rows[] = ['Total Multi-Purpose Loan Payments', number_format($this->summary['total_mpl_loan'], 2)];
        $rows[] = ['Total Calamity Loan Payments', number_format($this->summary['total_calamity_loan'], 2)];
        $rows[] = ['Total Remittance', number_format($this->summary['total_remittance'], 2)];
        $rows[] = ['Total Active Members', $this->summary['total_members']];
        $rows[] = ['Loan Eligible Members', $this->summary['loan_eligible_members']];
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Pag-IBIG Number',
            'Monthly Compensation',
            'Employee Contribution',
            'Employer Contribution',
            'Total Contribution',
            'Contribution Type',
            'Loan Eligibility',
            'Housing Loan Payment',
            'Multi-Purpose Loan Payment',
            'Calamity Loan Payment'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header
        $sheet->getStyle('A1:L1')->applyFromArray([
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
                    'argb' => 'FFFCE4EC',
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
        $sheet->getStyle('A6:L6')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFFEF1F5',
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
        $sheet->getStyle('A6:L' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge title cell
        $sheet->mergeCells('A1:L1');

        return [];
    }

    public function title(): string
    {
        return 'Pag-IBIG MCRF ' . $this->period;
    }
}
