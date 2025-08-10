<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PaidLeaveSetting;
use Illuminate\Http\Request;

class PaidLeaveSettingController extends Controller
{
    public function index()
    {
        $leaveSettings = PaidLeaveSetting::orderBy('sort_order')->get();

        return view('settings.leaves.index', compact('leaveSettings'));
    }

    public function create()
    {
        return view('settings.leaves.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:paid_leave_settings,code',
            'description' => 'nullable|string',
            'days_per_year' => 'required|integer|min:0|max:365',
            'accrual_method' => 'required|in:yearly,monthly,per_payroll',
            'accrual_rate' => 'required|numeric|min:0',
            'minimum_service_months' => 'required|integer|min:0|max:120',
            'prorated_first_year' => 'boolean',
            'minimum_days_usage' => 'required|integer|min:1',
            'maximum_days_usage' => 'nullable|integer|min:0',
            'notice_days_required' => 'required|integer|min:0',
            'can_carry_over' => 'boolean',
            'max_carry_over_days' => 'nullable|integer|min:0',
            'expires_annually' => 'boolean',
            'expiry_month' => 'required|integer|between:1,12',
            'can_convert_to_cash' => 'boolean',
            'cash_conversion_rate' => 'nullable|numeric|min:0|max:2',
            'max_convertible_days' => 'nullable|integer|min:0',
            'applicable_gender' => 'nullable|array',
            'applicable_employment_types' => 'nullable|array',
            'applicable_employment_status' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'benefit_eligibility' => 'required|in:both,with_benefits,without_benefits',
        ]);

        PaidLeaveSetting::create($validated);

        return redirect()->route('settings.leaves.index')
            ->with('success', 'Leave setting created successfully.');
    }

    public function show(PaidLeaveSetting $leave)
    {
        return view('settings.leaves.show', compact('leave'));
    }

    public function edit(PaidLeaveSetting $leave)
    {
        return view('settings.leaves.edit', compact('leave'));
    }

    public function update(Request $request, PaidLeaveSetting $leave)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'days_per_year' => 'required|integer|min:0|max:365',
            'accrual_method' => 'required|in:yearly,monthly,per_payroll',
            'accrual_rate' => 'required|numeric|min:0',
            'minimum_service_months' => 'required|integer|min:0|max:120',
            'prorated_first_year' => 'boolean',
            'minimum_days_usage' => 'required|integer|min:1',
            'maximum_days_usage' => 'nullable|integer|min:0',
            'notice_days_required' => 'required|integer|min:0',
            'can_carry_over' => 'boolean',
            'max_carry_over_days' => 'nullable|integer|min:0',
            'expires_annually' => 'boolean',
            'expiry_month' => 'required|integer|between:1,12',
            'can_convert_to_cash' => 'boolean',
            'cash_conversion_rate' => 'nullable|numeric|min:0|max:2',
            'max_convertible_days' => 'nullable|integer|min:0',
            'applicable_gender' => 'nullable|array',
            'applicable_employment_types' => 'nullable|array',
            'applicable_employment_status' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'benefit_eligibility' => 'required|in:both,with_benefits,without_benefits',
        ]);

        $leave->update($validated);

        return redirect()->route('settings.leaves.index')
            ->with('success', 'Leave setting updated successfully.');
    }

    public function destroy(PaidLeaveSetting $leave)
    {
        if ($leave->is_system_default) {
            return back()->with('error', 'Cannot delete system default leave setting.');
        }

        $leave->delete();

        return redirect()->route('settings.leaves.index')
            ->with('success', 'Leave setting deleted successfully.');
    }

    public function toggle(PaidLeaveSetting $leave)
    {
        $leave->update([
            'is_active' => !$leave->is_active
        ]);

        return back()->with('success', 'Leave setting status updated.');
    }

    public function calculatePreview(Request $request)
    {
        $leave = PaidLeaveSetting::findOrFail($request->leave_id);
        $employeeData = $request->employee ?? [];

        // Mock employee object for calculation
        $employee = (object) array_merge([
            'gender' => 'male',
            'employment_type' => 'regular',
            'employment_status' => 'active',
            'hire_date' => now()->subYear(),
        ], $employeeData);

        $isEligible = $leave->isEmployeeEligible($employee);
        $annualEntitlement = $isEligible ? $leave->calculateAnnualEntitlement($employee) : 0;

        return response()->json([
            'is_eligible' => $isEligible,
            'annual_entitlement' => $annualEntitlement,
            'accrual_rate' => $leave->accrual_rate,
            'minimum_service_months' => $leave->minimum_service_months
        ]);
    }
}
