<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DeductionTaxSetting;
use App\Models\PhilHealthTaxTable;
use App\Models\SssTaxTable;
use App\Models\PagibigTaxTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeductionTaxSettingController extends Controller
{
    public function index()
    {
        $deductions = DeductionTaxSetting::orderBy('type')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');

        return view('settings.deductions.index', compact('deductions'));
    }

    public function create()
    {
        return view('settings.deductions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:deduction_tax_settings,code',
            'description' => 'nullable|string',
            'type' => 'required|in:government,loan,custom',
            'category' => 'required|in:mandatory,voluntary',
            'calculation_type' => 'required|in:percentage,fixed_amount,bracket,sss_table,philhealth_table,pagibig_table,withholding_tax_table',
            'tax_table_type' => 'nullable|in:sss,philhealth,pagibig,withholding_tax',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'bracket_rates' => 'nullable|array',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'salary_cap' => 'nullable|numeric|min:0',
            'apply_to_regular' => 'boolean',
            'apply_to_overtime' => 'boolean',
            'apply_to_bonus' => 'boolean',
            'apply_to_allowances' => 'boolean',
            'apply_to_basic_pay' => 'boolean',
            'apply_to_gross_pay' => 'boolean',
            'apply_to_taxable_income' => 'boolean',
            'apply_to_net_pay' => 'boolean',
            'apply_to_monthly_basic_salary' => 'boolean',
            'employer_share_rate' => 'nullable|numeric|min:0|max:100',
            'employer_share_fixed' => 'nullable|numeric|min:0',
            'share_with_employer' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'benefit_eligibility' => 'required|in:both,with_benefits,without_benefits',
        ]);

        // Convert tax table types to bracket with appropriate tax_table_type
        if (in_array($validated['calculation_type'], ['sss_table', 'philhealth_table', 'pagibig_table', 'withholding_tax_table'])) {
            $validated['tax_table_type'] = str_replace('_table', '', $validated['calculation_type']);
            $validated['calculation_type'] = 'bracket';
        }

        // Convert empty strings to null for decimal fields and tax_table_type to prevent casting errors
        $decimalFields = ['rate_percentage', 'fixed_amount', 'minimum_amount', 'maximum_amount', 'salary_cap', 'employer_share_rate', 'employer_share_fixed'];
        foreach ($decimalFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Handle tax_table_type empty string - convert to null
        if (isset($validated['tax_table_type']) && $validated['tax_table_type'] === '') {
            $validated['tax_table_type'] = null;
        }

        DeductionTaxSetting::create($validated);

        return redirect()->route('settings.deductions.index')
            ->with('success', 'Deduction/Tax setting created successfully.');
    }

    public function show(DeductionTaxSetting $deduction)
    {
        return view('settings.deductions.show', compact('deduction'));
    }

    public function edit(DeductionTaxSetting $deduction)
    {
        return view('settings.deductions.edit', compact('deduction'));
    }

    public function update(Request $request, DeductionTaxSetting $deduction)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:government,loan,custom',
            'category' => 'required|in:mandatory,voluntary',
            'calculation_type' => 'required|in:percentage,fixed_amount,bracket,sss_table,philhealth_table,pagibig_table,withholding_tax_table',
            'tax_table_type' => 'nullable|in:sss,philhealth,pagibig,withholding_tax',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'bracket_rates' => 'nullable|array',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'salary_cap' => 'nullable|numeric|min:0',
            'apply_to_regular' => 'boolean',
            'apply_to_overtime' => 'boolean',
            'apply_to_bonus' => 'boolean',
            'apply_to_allowances' => 'boolean',
            'apply_to_basic_pay' => 'boolean',
            'apply_to_gross_pay' => 'boolean',
            'apply_to_taxable_income' => 'boolean',
            'apply_to_net_pay' => 'boolean',
            'apply_to_monthly_basic_salary' => 'boolean',
            'employer_share_rate' => 'nullable|numeric|min:0|max:100',
            'employer_share_fixed' => 'nullable|numeric|min:0',
            'share_with_employer' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'benefit_eligibility' => 'required|in:both,with_benefits,without_benefits',
        ]);

        // Convert tax table types to bracket with appropriate tax_table_type
        if (in_array($validated['calculation_type'], ['sss_table', 'philhealth_table', 'pagibig_table', 'withholding_tax_table'])) {
            $validated['tax_table_type'] = str_replace('_table', '', $validated['calculation_type']);
            $validated['calculation_type'] = 'bracket';
        }

        // Convert empty strings to null for decimal fields and tax_table_type to prevent casting errors
        $decimalFields = ['rate_percentage', 'fixed_amount', 'minimum_amount', 'maximum_amount', 'salary_cap', 'employer_share_rate', 'employer_share_fixed'];
        foreach ($decimalFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Handle tax_table_type empty string - convert to null
        if (isset($validated['tax_table_type']) && $validated['tax_table_type'] === '') {
            $validated['tax_table_type'] = null;
        }

        $deduction->update($validated);

        return redirect()->route('settings.deductions.index')
            ->with('success', 'Deduction/Tax setting updated successfully.');
    }

    public function destroy(DeductionTaxSetting $deduction)
    {
        if ($deduction->is_system_default) {
            return back()->with('error', 'Cannot delete system default deduction.');
        }

        $deduction->delete();

        return redirect()->route('settings.deductions.index')
            ->with('success', 'Deduction/Tax setting deleted successfully.');
    }

    public function toggle(DeductionTaxSetting $deduction)
    {
        $deduction->update([
            'is_active' => !$deduction->is_active
        ]);

        return back()->with('success', 'Deduction status updated.');
    }

    public function calculatePreview(Request $request)
    {
        $deduction = DeductionTaxSetting::findOrFail($request->deduction_id);
        $salary = $request->salary ?? 0;
        $overtime = $request->overtime ?? 0;
        $bonus = $request->bonus ?? 0;
        $allowances = $request->allowances ?? 0;

        $amount = $deduction->calculateDeduction($salary, $overtime, $bonus, $allowances);
        $employerShare = $deduction->calculateEmployerShare($amount, $salary);

        return response()->json([
            'employee_deduction' => $amount,
            'employer_share' => $employerShare,
            'total_cost' => $amount + $employerShare
        ]);
    }

    public function getPhilHealthTaxTable()
    {
        $taxTable = PhilHealthTaxTable::where('is_active', true)
            ->orderBy('range_start')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $taxTable->map(function ($item) {
                return [
                    'range_start' => $item->range_start,
                    'range_end' => $item->range_end,
                    'employee_share' => $item->employee_share,
                    'employer_share' => $item->employer_share,
                    'total_contribution' => $item->total_contribution,
                    'min_contribution' => $item->min_contribution,
                    'max_contribution' => $item->max_contribution,
                ];
            })
        ]);
    }

    public function getSssTaxTable()
    {
        $taxTable = SssTaxTable::where('is_active', true)
            ->orderBy('range_start')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $taxTable->map(function ($item) {
                return [
                    'range_start' => $item->range_start,
                    'range_end' => $item->range_end,
                    'employee_share' => $item->employee_share,
                    'employer_share' => $item->employer_share,
                    'total_contribution' => $item->total_contribution,
                ];
            })
        ]);
    }

    public function getPagibigTaxTable()
    {
        $taxTable = PagibigTaxTable::where('is_active', true)
            ->orderBy('range_start')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $taxTable->map(function ($item) {
                return [
                    'range_start' => $item->range_start,
                    'range_end' => $item->range_end,
                    'employee_share' => $item->employee_share . '%',
                    'employer_share' => $item->employer_share . '%',
                    'total_contribution' => $item->total_contribution . '%',
                    'min_contribution' => $item->min_contribution,
                    'max_contribution' => $item->max_contribution,
                ];
            })
        ]);
    }

    public function getWithholdingTaxTable()
    {
        $taxTable = DB::table('withholding_tax_tables')
            ->orderBy('pay_frequency')
            ->orderBy('bracket')
            ->get();

        $groupedData = $taxTable->groupBy('pay_frequency');

        return response()->json([
            'success' => true,
            'data' => $groupedData->map(function ($items, $frequency) {
                return [
                    'pay_frequency' => ucfirst($frequency),
                    'brackets' => $items->map(function ($item) {
                        $isAndAbove = $item->range_end === 'NULL' || $item->range_end === null || $item->range_end === '0.00';
                        return [
                            'bracket' => $item->bracket,
                            'pay_frequency' => $item->pay_frequency,
                            'range_start' => number_format((float)$item->range_start, 2),
                            'range_end' => $isAndAbove ? 'and above' : number_format((float)$item->range_end, 2),
                            'base_tax' => number_format((float)$item->base_tax, 2),
                            'tax_rate' => number_format((float)$item->tax_rate, 2),
                            'excess_over' => number_format((float)$item->excess_over, 2),
                            'formatted_range' => $isAndAbove
                                ? '₱' . number_format((float)$item->range_start, 2) . ' and above'
                                : '₱' . number_format((float)$item->range_start, 2) . ' - ₱' . number_format((float)$item->range_end, 2),
                        ];
                    })
                ];
            })
        ]);
    }
}
