<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidaySettingController extends Controller
{
    public function index()
    {
        $currentYear = date('Y');
        $holidays = Holiday::where('year', $currentYear)
            ->orderBy('date')
            ->get()
            ->groupBy('type');

        $years = Holiday::selectRaw('DISTINCT year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('settings.holidays.index', compact('holidays', 'years', 'currentYear'));
    }

    public function create()
    {
        return view('settings.holidays.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:regular,special_non_working,special_working',
            'rate_multiplier' => 'required|numeric|min:1|max:5',
            'is_double_pay' => 'boolean',
            'double_pay_rate' => 'nullable|numeric|min:1|max:5',
            'pay_rule' => 'required|in:regular_rate,holiday_rate,double_pay,no_pay',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['year'] = date('Y', strtotime($validated['date']));

        Holiday::create($validated);

        return redirect()->route('settings.holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function show(Holiday $holiday)
    {
        return view('settings.holidays.show', compact('holiday'));
    }

    public function edit(Holiday $holiday)
    {
        return view('settings.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:regular,special_non_working,special_working',
            'rate_multiplier' => 'required|numeric|min:1|max:5',
            'is_double_pay' => 'boolean',
            'double_pay_rate' => 'nullable|numeric|min:1|max:5',
            'pay_rule' => 'required|in:regular_rate,holiday_rate,double_pay,no_pay',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['year'] = date('Y', strtotime($validated['date']));

        $holiday->update($validated);

        return redirect()->route('settings.holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return redirect()->route('settings.holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }

    public function toggle(Holiday $holiday)
    {
        $holiday->update([
            'is_active' => !$holiday->is_active
        ]);

        return back()->with('success', 'Holiday status updated.');
    }

    public function filterByYear(Request $request)
    {
        $year = $request->year ?? date('Y');
        
        $holidays = Holiday::where('year', $year)
            ->orderBy('date')
            ->get()
            ->groupBy('type');

        return response()->json([
            'holidays' => $holidays
        ]);
    }

    public function generateRecurring(Request $request)
    {
        $targetYear = $request->validate(['year' => 'required|integer|min:2020|max:2030'])['year'];
        
        $recurringHolidays = Holiday::where('is_recurring', true)
            ->where('year', $targetYear - 1)
            ->get();

        $created = 0;
        foreach ($recurringHolidays as $holiday) {
            $newDate = date('Y-m-d', strtotime(str_replace($targetYear - 1, $targetYear, $holiday->date)));
            
            $exists = Holiday::where('date', $newDate)
                ->where('year', $targetYear)
                ->exists();
                
            if (!$exists) {
                Holiday::create([
                    'name' => $holiday->name,
                    'date' => $newDate,
                    'type' => $holiday->type,
                    'rate_multiplier' => $holiday->rate_multiplier,
                    'is_double_pay' => $holiday->is_double_pay,
                    'double_pay_rate' => $holiday->double_pay_rate,
                    'pay_rule' => $holiday->pay_rule,
                    'description' => $holiday->description,
                    'is_recurring' => $holiday->is_recurring,
                    'is_active' => $holiday->is_active,
                    'year' => $targetYear,
                ]);
                $created++;
            }
        }

        return back()->with('success', "Generated {$created} recurring holidays for {$targetYear}.");
    }
}
