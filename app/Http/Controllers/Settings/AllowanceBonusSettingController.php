<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AllowanceBonusSetting;
use Illuminate\Http\Request;

class AllowanceBonusSettingController extends Controller
{
    public function index()
    {
        $settings = AllowanceBonusSetting::orderBy('type')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');

        return view('settings.allowances.index', compact('settings'));
    }

    public function create()
    {
        return view('settings.allowances.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:allowance_bonus_settings,code',
            'description' => 'nullable|string',
            'type' => 'required|in:allowance,bonus,benefit',
            'category' => 'required|in:regular,conditional,one_time',
            'calculation_type' => 'required|in:percentage,fixed_amount,daily_rate_multiplier',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0',
            'is_taxable' => 'boolean',
            'apply_to_regular_days' => 'boolean',
            'apply_to_overtime' => 'boolean',
            'apply_to_holidays' => 'boolean',
            'apply_to_rest_days' => 'boolean',
            'frequency' => 'required|in:daily,per_payroll,monthly,quarterly,annually',
            'conditions' => 'nullable|array',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'max_days_per_period' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        AllowanceBonusSetting::create($validated);

        return redirect()->route('settings.allowances.index')
            ->with('success', 'Allowance/Bonus setting created successfully.');
    }

    public function show(AllowanceBonusSetting $allowance)
    {
        return view('settings.allowances.show', compact('allowance'));
    }

    public function edit(AllowanceBonusSetting $allowance)
    {
        return view('settings.allowances.edit', compact('allowance'));
    }

    public function update(Request $request, AllowanceBonusSetting $allowance)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:allowance,bonus,benefit',
            'category' => 'required|in:regular,conditional,one_time',
            'calculation_type' => 'required|in:percentage,fixed_amount,daily_rate_multiplier',
            'rate_percentage' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0',
            'is_taxable' => 'boolean',
            'apply_to_regular_days' => 'boolean',
            'apply_to_overtime' => 'boolean',
            'apply_to_holidays' => 'boolean',
            'apply_to_rest_days' => 'boolean',
            'frequency' => 'required|in:daily,per_payroll,monthly,quarterly,annually',
            'conditions' => 'nullable|array',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'max_days_per_period' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $allowance->update($validated);

        return redirect()->route('settings.allowances.index')
            ->with('success', 'Allowance/Bonus setting updated successfully.');
    }

    public function destroy(AllowanceBonusSetting $allowance)
    {
        if ($allowance->is_system_default) {
            return back()->with('error', 'Cannot delete system default allowance/bonus.');
        }

        $allowance->delete();

        return redirect()->route('settings.allowances.index')
            ->with('success', 'Allowance/Bonus setting deleted successfully.');
    }

    public function toggle(AllowanceBonusSetting $allowance)
    {
        $allowance->update([
            'is_active' => !$allowance->is_active
        ]);

        return back()->with('success', 'Allowance/Bonus status updated.');
    }

    public function calculatePreview(Request $request)
    {
        $setting = AllowanceBonusSetting::findOrFail($request->setting_id);
        $basicSalary = $request->basic_salary ?? 0;
        $dailyRate = $request->daily_rate ?? 0;
        $workingDays = $request->working_days ?? 22;

        $amount = $setting->calculateAmount($basicSalary, $dailyRate, $workingDays);

        return response()->json([
            'amount' => $amount,
            'is_taxable' => $setting->is_taxable,
            'frequency' => $setting->frequency
        ]);
    }
}
