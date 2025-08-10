<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Services\BIR1601CService;
use App\Services\BIR2316Service;
use App\Services\SSSReportService;
use App\Services\PhilHealthReportService;
use App\Services\PagibigReportService;
use App\Exports\BIR1601CExport;
use App\Exports\BIR2316Export;
use App\Exports\SSSReportExport;
use App\Exports\PhilHealthReportExport;
use App\Exports\PagibigReportExport;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class GovernmentFormsController extends Controller
{
    use AuthorizesRequests;

    protected $bir1601CService;
    protected $bir2316Service;
    protected $sssReportService;
    protected $philHealthReportService;
    protected $pagibigReportService;

    public function __construct(
        BIR1601CService $bir1601CService,
        BIR2316Service $bir2316Service,
        SSSReportService $sssReportService,
        PhilHealthReportService $philHealthReportService,
        PagibigReportService $pagibigReportService
    ) {
        $this->bir1601CService = $bir1601CService;
        $this->bir2316Service = $bir2316Service;
        $this->sssReportService = $sssReportService;
        $this->philHealthReportService = $philHealthReportService;
        $this->pagibigReportService = $pagibigReportService;
    }

    /**
     * Display government forms index page.
     */
    public function index()
    {
        $this->authorize('view reports');

        return view('government-forms.index');
    }

    /**
     * Show BIR 1601C form generator.
     */
    public function bir1601C(Request $request)
    {
        $this->authorize('generate reports');

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $data = $this->bir1601CService->generateData($year, $month);

        if ($request->get('action') === 'download') {
            return $this->bir1601CService->downloadPDF($data, $year, $month);
        }

        if ($request->get('action') === 'excel') {
            $period = Carbon::create($year, $month)->format('F Y');
            return Excel::download(
                new BIR1601CExport($data['employees'], $data['summary'], $period),
                "bir-1601c-{$year}-{$month}.xlsx"
            );
        }

        return view('government-forms.bir-1601c', compact('data', 'year', 'month'));
    }

    /**
     * Show BIR 2316 form generator.
     */
    public function bir2316(Request $request)
    {
        $this->authorize('generate reports');

        $year = $request->get('year', now()->year);
        $employeeId = $request->get('employee_id');

        if ($employeeId) {
            $employee = Employee::findOrFail($employeeId);
            $data = $this->bir2316Service->generateForEmployee($employee, $year);

            if ($request->get('action') === 'download') {
                return $this->bir2316Service->downloadPDF($employee, $data, $year);
            }

            return view('government-forms.bir-2316-preview', compact('employee', 'data', 'year'));
        }

        $employees = Employee::active()->with(['user', 'department', 'position'])->get();
        
        if ($request->get('action') === 'download_all') {
            $allData = [];
            $employees = Employee::active()->get();
            foreach ($employees as $employee) {
                $employeeData = $this->bir2316Service->generateForEmployee($employee, $year);
                $allData[] = $employeeData;
            }
            return Excel::download(
                new BIR2316Export($allData, $year),
                "bir-2316-all-employees-{$year}.xlsx"
            );
        }

        if ($request->get('action') === 'excel' && $employeeId) {
            $employee = Employee::findOrFail($employeeId);
            $data = $this->bir2316Service->generateForEmployee($employee, $year);
            return Excel::download(
                new BIR2316Export([$data], $year),
                "bir-2316-{$employee->employee_id}-{$year}.xlsx"
            );
        }

        return view('government-forms.bir-2316', compact('employees', 'year'));
    }

    /**
     * Show SSS R-3 form generator.
     */
    public function sssR3(Request $request)
    {
        $this->authorize('generate reports');

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $data = $this->sssReportService->generateR3Data($year, $month);

        if ($request->get('action') === 'download') {
            return $this->sssReportService->downloadPDF($data, $year, $month);
        }

        if ($request->get('action') === 'excel') {
            $period = Carbon::create($year, $month)->format('F Y');
            return Excel::download(
                new SSSReportExport($data['employees'], $data['summary'], $period),
                "sss-r3-{$year}-{$month}.xlsx"
            );
        }

        return view('government-forms.sss-r3', compact('data', 'year', 'month'));
    }

    /**
     * Show PhilHealth RF-1 form generator.
     */
    public function philHealthRF1(Request $request)
    {
        $this->authorize('generate reports');

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $data = $this->philHealthReportService->generateRF1Data($year, $month);

        if ($request->get('action') === 'download') {
            return $this->philHealthReportService->downloadPDF($data, $year, $month);
        }

        if ($request->get('action') === 'excel') {
            $period = Carbon::create($year, $month)->format('F Y');
            return Excel::download(
                new PhilHealthReportExport($data['employees'], $data['summary'], $period),
                "philhealth-rf1-{$year}-{$month}.xlsx"
            );
        }

        return view('government-forms.philhealth-rf1', compact('data', 'year', 'month'));
    }

    /**
     * Show Pag-IBIG MCRF form generator.
     */
    public function pagibigMCRF(Request $request)
    {
        $this->authorize('generate reports');

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $data = $this->pagibigReportService->generateMCRFData($year, $month);

        if ($request->get('action') === 'download') {
            return $this->pagibigReportService->downloadPDF($data, $year, $month);
        }

        if ($request->get('action') === 'excel') {
            $period = Carbon::create($year, $month)->format('F Y');
            return Excel::download(
                new PagibigReportExport($data['employees'], $data['summary'], $period),
                "pagibig-mcrf-{$year}-{$month}.xlsx"
            );
        }

        return view('government-forms.pagibig-mcrf', compact('data', 'year', 'month'));
    }

    /**
     * Show BIR 1604-C form generator (Annual).
     */
    public function bir1604C(Request $request)
    {
        $this->authorize('generate reports');

        $year = $request->get('year', now()->year - 1); // Previous year for annual report

        $data = $this->bir1601CService->generateAnnualData($year);

        if ($request->get('action') === 'download') {
            return $this->bir1601CService->downloadAnnualPDF($data, $year);
        }

        if ($request->get('action') === 'excel') {
            return $this->bir1601CService->downloadAnnualExcel($data, $year);
        }

        return view('government-forms.bir-1604c', compact('data', 'year'));
    }
}
