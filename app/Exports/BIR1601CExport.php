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

class BIR1601CExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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
        $rows[] = ['BIR FORM 1601-C - MONTHLY REMITTANCE RETURN OF INCOME TAXES WITHHELD ON COMPENSATION'];
        $rows[] = ['Period: ' . $this->period];
        $rows[] = ['Company: ' . config('company.company_name', 'Company Name')];
        $rows[] = ['TIN: ' . config('company.company_tin', 'XXX-XXX-XXX-XXX')];
        $rows[] = [''];
        
        // Employee details
        foreach ($this->data as $employee) {
            $rows[] = [
                $employee['employee_id'],
                $employee['name'],
                $employee['tin'],
                number_format($employee['gross_compensation'], 2),
                number_format($employee['non_taxable_13th_month'], 2),
                number_format($employee['non_taxable_de_minimis'], 2),
                number_format($employee['sss_contribution'], 2),
                number_format($employee['philhealth_contribution'], 2),
                number_format($employee['pagibig_contribution'], 2),
                number_format($employee['union_dues'], 2),
                number_format($employee['taxable_compensation'], 2),
                number_format($employee['tax_withheld'], 2),
            ];
        }
        
        // Add summary section
        $rows[] = [''];
        $rows[] = ['SUMMARY'];
        $rows[] = ['Total Gross Compensation', number_format($this->summary['total_gross_compensation'], 2)];
        $rows[] = ['Total Non-taxable 13th Month Pay', number_format($this->summary['total_non_taxable_13th_month'], 2)];
        $rows[] = ['Total Non-taxable De Minimis Benefits', number_format($this->summary['total_non_taxable_de_minimis'], 2)];
        $rows[] = ['Total Contributions and Union Dues', number_format($this->summary['total_contributions'], 2)];
        $rows[] = ['Total Taxable Compensation', number_format($this->summary['total_taxable_compensation'], 2)];
        $rows[] = ['Total Tax Withheld', number_format($this->summary['total_tax_withheld'], 2)];
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'TIN',
            'Gross Compensation',
            'Non-taxable 13th Month Pay',
            'Non-taxable De Minimis Benefits',
            'SSS Contribution',
            'PhilHealth Contribution',
            'Pag-IBIG Contribution',
            'Union Dues',
            'Taxable Compensation',
            'Tax Withheld'
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
        return 'BIR 1601-C ' . $this->period;
    }
}
