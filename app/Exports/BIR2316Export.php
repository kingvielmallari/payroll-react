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

class BIR2316Export implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $year;

    public function __construct($data, $year)
    {
        $this->data = $data;
        $this->year = $year;
    }

    public function array(): array
    {
        $rows = [];
        
        // Add company info header
        $rows[] = ['BIR FORM 2316 - CERTIFICATE OF COMPENSATION PAYMENT/TAX WITHHELD'];
        $rows[] = ['Year: ' . $this->year];
        $rows[] = ['Company: ' . config('company.company_name', 'Company Name')];
        $rows[] = ['TIN: ' . config('company.company_tin', 'XXX-XXX-XXX-XXX')];
        $rows[] = [''];
        
        // Employee details
        foreach ($this->data as $employee) {
            $rows[] = [
                $employee['employee_id'],
                $employee['name'],
                $employee['tin'],
                $employee['position'],
                number_format($employee['gross_compensation'], 2),
                number_format($employee['non_taxable_13th_month'], 2),
                number_format($employee['non_taxable_de_minimis'], 2),
                number_format($employee['sss_contribution'], 2),
                number_format($employee['philhealth_contribution'], 2),
                number_format($employee['pagibig_contribution'], 2),
                number_format($employee['union_dues'], 2),
                number_format($employee['total_contributions'], 2),
                number_format($employee['taxable_compensation'], 2),
                number_format($employee['tax_withheld'], 2),
            ];
        }
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'TIN',
            'Position',
            'Gross Compensation',
            'Non-taxable 13th Month Pay',
            'Non-taxable De Minimis Benefits',
            'SSS Contribution',
            'PhilHealth Contribution',
            'Pag-IBIG Contribution',
            'Union Dues',
            'Total Contributions',
            'Taxable Compensation',
            'Tax Withheld'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header
        $sheet->getStyle('A1:N1')->applyFromArray([
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
                    'argb' => 'FFCCCCCC',
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
        $sheet->getStyle('A6:N6')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFE6E6E6',
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
        $sheet->getStyle('A6:N' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge title cell
        $sheet->mergeCells('A1:N1');

        return [];
    }

    public function title(): string
    {
        return 'BIR 2316 ' . $this->year;
    }
}
