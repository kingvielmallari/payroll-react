<?php

namespace App\Http\Controllers;

use App\Models\PayrollScheduleSetting;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PayrollScheduleSettingsController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('edit settings');

        $payrollSchedules = PayrollScheduleSetting::orderBy('pay_type')->get();

        // If no settings exist, create default ones
        if ($payrollSchedules->isEmpty()) {
            $this->createDefaultSettings();
            $payrollSchedules = PayrollScheduleSetting::orderBy('pay_type')->get();
        }

        return view('admin.payroll-schedule-settings.index', compact('payrollSchedules'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PayrollScheduleSetting $payrollScheduleSetting)
    {
        $this->authorize('edit settings');
        
        return view('admin.payroll-schedule-settings.edit', compact('payrollScheduleSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PayrollScheduleSetting $payrollScheduleSetting)
    {
        $this->authorize('edit settings');

        // Base validation rules
        $rules = [
            'is_active' => 'boolean',
        ];

        // Semi-monthly specific validation
        if ($payrollScheduleSetting->pay_type === 'semi_monthly') {
            $rules = array_merge($rules, [
                'first_period_start' => 'required|integer|min:1|max:31',
                'first_period_end' => 'required|integer|min:1|max:31',
                'first_period_pay' => 'required|integer|min:1|max:31',
                'second_period_start' => 'required|integer|min:1|max:31',
                'second_period_end' => 'required|integer|min:-1|max:31',
                'second_period_pay' => 'required|integer|min:-1|max:31',
            ]);
        } else {
            // Weekly/Monthly validation
            $rules = array_merge($rules, [
                'cutoff_description' => 'required|string|max:255',
                'cutoff_start_day' => 'nullable|integer|min:1|max:31',
                'cutoff_end_day' => 'nullable|integer|min:1|max:31',
                'payday_offset_days' => 'required|integer|min:0|max:30',
                'payday_description' => 'required|string|max:255',
            ]);
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->has('is_active');

        // Handle semi-monthly configuration
        if ($payrollScheduleSetting->pay_type === 'semi_monthly') {
            $semiMonthlyConfig = [
                'first_period' => [
                    'start_day' => $validated['first_period_start'],
                    'end_day' => $validated['first_period_end'],
                    'pay_day' => $validated['first_period_pay']
                ],
                'second_period' => [
                    'start_day' => $validated['second_period_start'],
                    'end_day' => $validated['second_period_end'],
                    'pay_day' => $validated['second_period_pay']
                ]
            ];

            $updateData = [
                'semi_monthly_config' => $semiMonthlyConfig,
                'is_active' => $validated['is_active']
            ];

            // Update descriptions based on config
            $firstPayText = $validated['first_period_pay'] == -1 ? 'Last day' : $validated['first_period_pay'] . 'th';
            $secondEndText = $validated['second_period_end'] == -1 ? 'Last day' : $validated['second_period_end'] . 'th';
            $secondPayText = $validated['second_period_pay'] == -1 ? 'Last day' : ($validated['second_period_pay'] == 1 || $validated['second_period_pay'] == 5 ? $validated['second_period_pay'] . 'st of next month' : $validated['second_period_pay'] . 'th');

            $updateData['cutoff_description'] = "1st-{$validated['first_period_end']}th and {$validated['second_period_start']}th-{$secondEndText}";
            $updateData['payday_description'] = "Pay {$firstPayText} and {$secondPayText}";
        } else {
            $updateData = [
                'cutoff_description' => $validated['cutoff_description'],
                'cutoff_start_day' => $validated['cutoff_start_day'],
                'cutoff_end_day' => $validated['cutoff_end_day'],
                'payday_offset_days' => $validated['payday_offset_days'],
                'payday_description' => $validated['payday_description'],
                'is_active' => $validated['is_active']
            ];
        }

        $payrollScheduleSetting->update($updateData);

        return redirect()->route('payroll-schedule-settings.index')
                        ->with('success', 'Payroll schedule settings updated successfully!');
    }

    /**
     * Create default payroll schedule settings
     */
    private function createDefaultSettings()
    {
        $defaultSettings = [
            [
                'pay_type' => 'weekly',
                'cutoff_description' => 'Monday to Sunday',
                'cutoff_start_day' => 1, // Monday
                'cutoff_end_day' => 7, // Sunday
                'payday_offset_days' => 5, // Next Friday
                'payday_description' => 'Next Friday',
                'notes' => 'Needs attendance validation buffer',
                'is_active' => true,
            ],
            [
                'pay_type' => 'semi_monthly',
                'cutoff_description' => '1st to 15th / 16th to 30th/31st',
                'cutoff_start_day' => 1,
                'cutoff_end_day' => 15,
                'payday_offset_days' => 0,
                'payday_description' => '15th & 30th/31st',
                'notes' => 'BIR-compliant and widely used',
                'is_active' => true,
            ],
            [
                'pay_type' => 'monthly',
                'cutoff_description' => '1st to 30th/31st',
                'cutoff_start_day' => 1,
                'cutoff_end_day' => 31,
                'payday_offset_days' => 0,
                'payday_description' => '30th/31st',
                'notes' => 'Simplest but least flexible',
                'is_active' => true,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            PayrollScheduleSetting::create($setting);
        }
    }
}
