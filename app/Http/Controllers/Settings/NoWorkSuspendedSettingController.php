<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NoWorkSuspendedSetting;
use App\Models\Department;
use App\Models\Position;
use App\Models\Employee;
use Illuminate\Http\Request;

class NoWorkSuspendedSettingController extends Controller
{
    public function index()
    {
        $suspensions = NoWorkSuspendedSetting::with([
                'affectedDepartments',
                'affectedPositions', 
                'affectedEmployees'
            ])
            ->orderBy('date_from', 'desc')
            ->get()
            ->groupBy('status');

        return view('settings.no-work.index', compact('suspensions'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get(['id', 'name']);
        $positions = Position::where('is_active', true)->get(['id', 'title', 'department_id']);
        $employees = Employee::where('employment_status', 'active')
            ->with(['user:id,name', 'department:id,name', 'position:id,title'])
            ->get(['id', 'user_id', 'employee_number', 'department_id', 'position_id']);

        return view('settings.no-work.create', compact('departments', 'positions', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:no_work_suspended_settings,code',
            'description' => 'nullable|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i|after:time_from',
            'type' => 'required|in:no_work,suspended,partial_suspension',
            'reason' => 'required|in:weather,system_maintenance,emergency,government_order,other',
            'detailed_reason' => 'nullable|string',
            'pay_rule' => 'required|in:no_pay,half_pay,full_pay,custom_rate',
            'custom_pay_rate' => 'nullable|numeric|min:0|max:2',
            'scope' => 'required|in:company_wide,department,position,specific_employees',
            'affected_departments' => 'nullable|array',
            'affected_positions' => 'nullable|array',
            'affected_employees' => 'nullable|array',
            'allow_makeup_work' => 'boolean',
            'makeup_deadline' => 'nullable|date|after:date_to',
            'makeup_instructions' => 'nullable|string',
            'declared_by' => 'nullable|string|max:255',
            'declaration_date' => 'nullable|date',
            'official_memo' => 'nullable|string',
            'status' => 'required|in:draft,active,completed,cancelled',
        ]);

        NoWorkSuspendedSetting::create($validated);

        return redirect()->route('settings.no-work.index')
            ->with('success', 'No Work/Suspended setting created successfully.');
    }

    public function show(NoWorkSuspendedSetting $noWork)
    {
        $noWork->load(['affectedDepartments', 'affectedPositions', 'affectedEmployees']);

        return view('settings.no-work.show', compact('noWork'));
    }

    public function edit(NoWorkSuspendedSetting $noWork)
    {
        $departments = Department::where('is_active', true)->get(['id', 'name']);
        $positions = Position::where('is_active', true)->get(['id', 'title', 'department_id']);
        $employees = Employee::where('employment_status', 'active')
            ->with(['user:id,name', 'department:id,name', 'position:id,title'])
            ->get(['id', 'user_id', 'employee_number', 'department_id', 'position_id']);

        return view('settings.no-work.edit', compact('noWork', 'departments', 'positions', 'employees'));
    }

    public function update(Request $request, NoWorkSuspendedSetting $noWork)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i|after:time_from',
            'type' => 'required|in:no_work,suspended,partial_suspension',
            'reason' => 'required|in:weather,system_maintenance,emergency,government_order,other',
            'detailed_reason' => 'nullable|string',
            'pay_rule' => 'required|in:no_pay,half_pay,full_pay,custom_rate',
            'custom_pay_rate' => 'nullable|numeric|min:0|max:2',
            'scope' => 'required|in:company_wide,department,position,specific_employees',
            'affected_departments' => 'nullable|array',
            'affected_positions' => 'nullable|array',
            'affected_employees' => 'nullable|array',
            'allow_makeup_work' => 'boolean',
            'makeup_deadline' => 'nullable|date|after:date_to',
            'makeup_instructions' => 'nullable|string',
            'declared_by' => 'nullable|string|max:255',
            'declaration_date' => 'nullable|date',
            'official_memo' => 'nullable|string',
            'status' => 'required|in:draft,active,completed,cancelled',
        ]);

        $noWork->update($validated);

        return redirect()->route('settings.no-work.index')
            ->with('success', 'No Work/Suspended setting updated successfully.');
    }

    public function destroy(NoWorkSuspendedSetting $noWork)
    {
        $noWork->delete();

        return redirect()->route('settings.no-work.index')
            ->with('success', 'No Work/Suspended setting deleted successfully.');
    }

    public function activate(NoWorkSuspendedSetting $noWork)
    {
        $noWork->update(['status' => 'active']);

        return back()->with('success', 'No Work/Suspended setting activated.');
    }

    public function complete(NoWorkSuspendedSetting $noWork)
    {
        $noWork->update(['status' => 'completed']);

        return back()->with('success', 'No Work/Suspended setting marked as completed.');
    }

    public function cancel(NoWorkSuspendedSetting $noWork)
    {
        $noWork->update(['status' => 'cancelled']);

        return back()->with('success', 'No Work/Suspended setting cancelled.');
    }

    public function getAffectedEmployees(NoWorkSuspendedSetting $noWork)
    {
        $employees = collect();

        switch ($noWork->scope) {
            case 'company_wide':
                $employees = Employee::where('employment_status', 'active')->get();
                break;
                
            case 'department':
                if ($noWork->affected_departments) {
                    $employees = Employee::whereIn('department_id', $noWork->affected_departments)
                        ->where('employment_status', 'active')
                        ->get();
                }
                break;
                
            case 'position':
                if ($noWork->affected_positions) {
                    $employees = Employee::whereIn('position_id', $noWork->affected_positions)
                        ->where('employment_status', 'active')
                        ->get();
                }
                break;
                
            case 'specific_employees':
                if ($noWork->affected_employees) {
                    $employees = Employee::whereIn('id', $noWork->affected_employees)
                        ->where('employment_status', 'active')
                        ->get();
                }
                break;
        }

        return response()->json([
            'affected_employees' => $employees->load(['user', 'department', 'position']),
            'total_count' => $employees->count()
        ]);
    }
}
