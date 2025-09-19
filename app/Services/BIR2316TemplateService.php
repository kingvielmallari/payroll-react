<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\PayrollSnapshot;
use App\Models\Payroll;
use App\Models\EmployerSetting;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use ZipArchive;

class BIR2316TemplateService
{
    private $templatePath;
    private $pdfTemplatePath;

    public function __construct()
    {
        $this->templatePath = storage_path('app/private/BIR-2316.xltx');
        $this->pdfTemplatePath = storage_path('app/private/BIR-23161.pdf');
    }

    /**
     * Generate BIR 2316 data for a specific employee and year.
     */
    public function generateEmployeeData(Employee $employee, $year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();

        // Get all payroll snapshots for the employee for the year
        $payrollSnapshots = PayrollSnapshot::where('employee_id', $employee->id)
            ->whereHas('payroll', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('period_start', [$startDate, $endDate]);
            })
            ->with('payroll')
            ->get();

        // Get employer settings for employer information
        $employerSettings = EmployerSetting::getSettings();

        $totals = [
            'gross_compensation' => 0,
            'basic_salary' => 0,
            'overtime_pay' => 0,
            'night_differential' => 0,
            'holiday_pay' => 0,
            'allowances' => 0,
            'bonuses' => 0,
            'incentives' => 0,
            'other_compensation' => 0,
            'sss_contribution' => 0,
            'philhealth_contribution' => 0,
            'pagibig_contribution' => 0,
            'union_dues' => 0,
            'total_contributions' => 0,
            'tax_withheld' => 0,
            'non_taxable_13th_month' => 0,
            'non_taxable_de_minimis' => 0,
            'taxable_compensation' => 0,
            'other_deductions' => 0,
        ];

        // Calculate totals from payroll snapshots
        foreach ($payrollSnapshots as $snapshot) {
            $totals['basic_salary'] += $snapshot->regular_pay ?? 0;
            $totals['overtime_pay'] += $snapshot->overtime_pay ?? 0;
            $totals['night_differential'] += $snapshot->night_differential_pay ?? 0;
            $totals['holiday_pay'] += $snapshot->holiday_pay ?? 0;
            $totals['allowances'] += $snapshot->allowances_total ?? 0;
            $totals['bonuses'] += $snapshot->bonuses_total ?? 0;
            $totals['incentives'] += $snapshot->incentives_total ?? 0;
            $totals['other_compensation'] += $snapshot->other_earnings ?? 0;
            $totals['sss_contribution'] += $snapshot->sss_contribution ?? 0;
            $totals['philhealth_contribution'] += $snapshot->philhealth_contribution ?? 0;
            $totals['pagibig_contribution'] += $snapshot->pagibig_contribution ?? 0;
            $totals['tax_withheld'] += $snapshot->withholding_tax ?? 0;
            $totals['other_deductions'] += $snapshot->other_deductions ?? 0;
        }

        // Calculate derived values
        $totals['gross_compensation'] = $totals['basic_salary'] + $totals['overtime_pay'] +
            $totals['night_differential'] + $totals['holiday_pay'] +
            $totals['allowances'] + $totals['bonuses'] + $totals['incentives'] +
            $totals['other_compensation'];

        $totals['total_contributions'] = $totals['sss_contribution'] +
            $totals['philhealth_contribution'] + $totals['pagibig_contribution'] +
            $totals['union_dues'];

        // Calculate 13th month (non-taxable portion up to 90,000)
        $thirteenthMonth = $totals['basic_salary']; // Simplified calculation
        $totals['non_taxable_13th_month'] = min($thirteenthMonth, 90000);

        $totals['taxable_compensation'] = $totals['gross_compensation'] - $totals['total_contributions'] -
            $totals['non_taxable_13th_month'] - $totals['non_taxable_de_minimis'];

        // Add employer and employee information
        $totals['employer'] = [
            'name' => $employerSettings->registered_business_name ?? 'N/A',
            'tin' => $employerSettings->tax_identification_number ?? 'N/A',
            'address' => $employerSettings->registered_address ?? 'N/A',
            'zip_code' => $employerSettings->postal_zip_code ?? 'N/A',
            'rdo_code' => $employerSettings->rdo_code ?? 'N/A',
        ];

        $totals['employee'] = [
            'name' => trim($employee->first_name . ' ' . ($employee->middle_name ?? '') . ' ' . $employee->last_name),
            'tin' => $employee->tin_number ?? 'N/A',
            'address' => $employee->address ?? 'N/A',
        ];

        return $totals;
    }

    /**
     * Inject data into the Excel template for a single employee.
     */
    public function injectDataToTemplate(Employee $employee, $data, $year)
    {
        try {
            // Load the template
            $spreadsheet = IOFactory::load($this->templatePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Year input (Part 1)
            $worksheet->setCellValue('D12', $year);

            // Employee Information (Part I)
            $worksheet->setCellValue('D14', $employee->tin_number ?? 'Not provided'); // Employee TIN
            $worksheet->setCellValue('D16', $employee->first_name . ' ' . ($employee->middle_name ? $employee->middle_name . ' ' : '') . $employee->last_name); // Employee Name
            $worksheet->setCellValue('D19', $employee->address ?? 'Not provided'); // Employee Address

            // Employer Information (Part II)
            $worksheet->setCellValue('D40', $data['employer']['name'] ?? 'Your Company Name'); // Employer Name
            $worksheet->setCellValue('D43', $data['employer']['address'] ?? 'Your Company Address'); // Employer Address
            $worksheet->setCellValue('D49', $data['employer']['tin'] ?? 'XXX-XXX-XXX-XXX'); // Employer TIN
            $worksheet->setCellValue('H49', $data['employer']['rdo_code'] ?? 'XXX'); // RDO Code

            // Compensation Information (Part IV Summary)
            $worksheet->setCellValue('K58', number_format($data['gross_compensation'], 2, '.', '')); // Gross Compensation Income
            $worksheet->setCellValue('K60', number_format($data['non_taxable_13th_month'] + $data['non_taxable_de_minimis'], 2, '.', '')); // Total Non-Taxable
            $worksheet->setCellValue('K62', number_format($data['taxable_compensation'], 2, '.', '')); // Taxable Compensation from Present
            $worksheet->setCellValue('K66', number_format($data['taxable_compensation'], 2, '.', '')); // Gross Taxable Compensation (same as K62 for single employer)
            $worksheet->setCellValue('K70', number_format($data['tax_withheld'], 2, '.', '')); // Amount of Taxes Withheld
            $worksheet->setCellValue('K80', number_format($data['tax_withheld'], 2, '.', '')); // Total Taxes Withheld Final

            return $spreadsheet;
        } catch (\Exception $e) {
            // Log the error and return a basic spreadsheet
            Log::error('Error injecting data to BIR template: ' . $e->getMessage());
            throw new \Exception('Unable to process BIR template: ' . $e->getMessage());
        }
    }

    /**
     * Download individual Excel file for an employee (.xltx format).
     */
    public function downloadIndividualExcel(Employee $employee, $year)
    {
        // Use the exact Excel template file: BIR-2316.xltx
        if (file_exists($this->templatePath)) {
            // Create filename with employee name format: BIR_2316_LASTNAME_FIRSTNAME_YEAR
            $lastName = strtoupper($employee->last_name);
            $firstName = strtoupper($employee->first_name);
            $middleName = $employee->middle_name ? strtoupper($employee->middle_name) : '';

            $fullName = $middleName ? "{$lastName}_{$firstName}_{$middleName}" : "{$lastName}_{$firstName}";
            $filename = "BIR_2316_{$fullName}_{$year}.xltx";
            $tempPath = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Copy the exact Excel template
            copy($this->templatePath, $tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } else {
            throw new \Exception('Excel template file BIR-2316.xltx not found in storage/app/private/');
        }
    }
    /**
     * Download individual PDF file for an employee.
     */
    public function downloadIndividualPDF(Employee $employee, $year)
    {
        // Use the exact PDF template file: BIR-23161.pdf
        if (file_exists($this->pdfTemplatePath)) {
            // Create filename with employee name format: BIR_2316_LASTNAME_FIRSTNAME_YEAR
            $lastName = strtoupper($employee->last_name);
            $firstName = strtoupper($employee->first_name);
            $middleName = $employee->middle_name ? strtoupper($employee->middle_name) : '';

            $fullName = $middleName ? "{$lastName}_{$firstName}_{$middleName}" : "{$lastName}_{$firstName}";
            $filename = "BIR_2316_{$fullName}_{$year}.pdf";
            $tempPath = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Copy the exact PDF template
            copy($this->pdfTemplatePath, $tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } else {
            throw new \Exception('PDF template file BIR-23161.pdf not found in storage/app/private/');
        }
    }

    /**
     * Download all employees' forms as individual .xltx files in a ZIP archive.
     * File Name: BIR_2316_EMPLOYEES_2025.zip
     * Each employee gets their own .xltx file with original formatting
     */
    public function downloadAllExcel($employees, $year)
    {
        try {
            // Create a ZIP archive containing individual .xltx files for each employee
            $zipFilename = "BIR_2316_EMPLOYEES_{$year}.zip";
            $zipPath = storage_path('app/temp/' . $zipFilename);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Cannot create ZIP file');
            }

            // Add individual .xltx file for each employee
            foreach ($employees as $employee) {
                // Create individual filename
                $lastName = strtoupper($employee->last_name);
                $firstName = strtoupper($employee->first_name);
                $middleName = $employee->middle_name ? strtoupper($employee->middle_name) : '';
                $fullName = $middleName ? "{$lastName}_{$firstName}_{$middleName}" : "{$lastName}_{$firstName}";
                $individualFilename = "BIR_2316_{$fullName}_{$year}.xltx";

                // Copy the original template (preserves exact formatting)
                $tempIndividualPath = storage_path('app/temp/' . $individualFilename);
                copy($this->templatePath, $tempIndividualPath);

                // Add to ZIP
                $zip->addFile($tempIndividualPath, $individualFilename);
            }

            // Close ZIP
            $zip->close();

            // Clean up individual temp files
            foreach ($employees as $employee) {
                $lastName = strtoupper($employee->last_name);
                $firstName = strtoupper($employee->first_name);
                $middleName = $employee->middle_name ? strtoupper($employee->middle_name) : '';
                $fullName = $middleName ? "{$lastName}_{$firstName}_{$middleName}" : "{$lastName}_{$firstName}";
                $individualFilename = "BIR_2316_{$fullName}_{$year}.xltx";
                $tempIndividualPath = storage_path('app/temp/' . $individualFilename);
                if (file_exists($tempIndividualPath)) {
                    unlink($tempIndividualPath);
                }
            }

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error generating Excel ZIP for all employees: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate Excel files: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download all employees' forms in a single PDF file (multiple pages).
     * File Name: BIR_2316_EMPLOYEES_2025.pdf
     * Each employee gets one page (duplicate copy of the original PDF page)
     */
    public function downloadAllPDF($employees, $year)
    {
        try {
            // Use the exact PDF template file: BIR-23161.pdf (same as individual download)
            if (file_exists($this->pdfTemplatePath)) {
                // Read the original PDF content
                $originalPdfContent = file_get_contents($this->pdfTemplatePath);

                // Create a new PDF with mPDF to combine multiple pages
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'Legal',
                    'orientation' => 'P'
                ]);

                // For each employee, add the same page content
                foreach ($employees as $index => $employee) {
                    if ($index > 0) {
                        // Add new page for each employee after the first
                        $mpdf->AddPage();
                    }

                    // Import the original PDF page
                    $pagecount = $mpdf->SetSourceFile($this->pdfTemplatePath);
                    for ($i = 1; $i <= $pagecount; $i++) {
                        $template = $mpdf->ImportPage($i);
                        $mpdf->UseTemplate($template);

                        // If there are more pages in the original PDF, add them too
                        if ($i < $pagecount) {
                            $mpdf->AddPage();
                        }
                    }
                }

                // Create the filename: BIR_2316_EMPLOYEES_YEAR.pdf
                $filename = "BIR_2316_EMPLOYEES_{$year}.pdf";
                $tempPath = storage_path('app/temp/' . $filename);

                // Ensure temp directory exists
                if (!file_exists(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }

                // Output the PDF to file
                $mpdf->Output($tempPath, 'F');

                return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
            } else {
                throw new \Exception('PDF template file BIR-23161.pdf not found in storage/app/private/');
            }
        } catch (\Exception $e) {
            Log::error('Error generating PDF for all employees: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }
}
