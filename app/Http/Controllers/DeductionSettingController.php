<?php

namespace App\Http\Controllers;

use App\Models\DeductionSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DeductionSettingController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view deduction settings');

        $deductionSettings = DeductionSetting::orderBy('type')
                                            ->orderBy('name')
                                            ->paginate(15);

        return view('deduction-settings.index', compact('deductionSettings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create deduction settings');
        
        return view('deduction-settings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create deduction settings');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:deduction_settings,code',
            'type' => 'required|in:government,custom',
            'calculation_type' => 'required|in:percentage,fixed,tiered,table_based',
            'rate' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'salary_threshold' => 'nullable|numeric|min:0',
            'rate_table' => 'nullable|json',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'formula_notes' => 'nullable|string',
        ]);

        DeductionSetting::create($validated);

        return redirect()->route('deduction-settings.index')
                        ->with('success', 'Deduction setting created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(DeductionSetting $deductionSetting)
    {
        $this->authorize('view deduction settings');

        return view('deduction-settings.show', compact('deductionSetting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeductionSetting $deductionSetting)
    {
        $this->authorize('edit deduction settings');

        return view('deduction-settings.edit', compact('deductionSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DeductionSetting $deductionSetting)
    {
        $this->authorize('edit deduction settings');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:deduction_settings,code,' . $deductionSetting->id,
            'type' => 'required|in:government,custom',
            'calculation_type' => 'required|in:percentage,fixed,tiered,table_based',
            'rate' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'salary_threshold' => 'nullable|numeric|min:0',
            'rate_table' => 'nullable|json',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'formula_notes' => 'nullable|string',
        ]);

        $deductionSetting->update($validated);

        return redirect()->route('deduction-settings.index')
                        ->with('success', 'Deduction setting updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeductionSetting $deductionSetting)
    {
        $this->authorize('delete deduction settings');

        $deductionSetting->delete();

        return redirect()->route('deduction-settings.index')
                        ->with('success', 'Deduction setting deleted successfully!');
    }

    /**
     * Toggle active status
     */
    public function toggle(DeductionSetting $deductionSetting)
    {
        $this->authorize('edit deduction settings');

        $deductionSetting->update(['is_active' => !$deductionSetting->is_active]);

        $status = $deductionSetting->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
                        ->with('success', "Deduction setting {$status} successfully!");
    }

    /**
     * Calculate deduction preview
     */
    public function calculatePreview(Request $request, DeductionSetting $deductionSetting)
    {
        $salary = $request->input('salary', 0);
        $amount = $deductionSetting->calculateDeduction($salary);

        return response()->json([
            'amount' => $amount,
            'formatted_amount' => number_format($amount, 2),
        ]);
    }
}
