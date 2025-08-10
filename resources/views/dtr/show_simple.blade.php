<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Daily Time Record
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($dtr->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($dtr->period_end)->format('M d, Y') }}
                </p>
            </div>
            <div class="flex space-x-2">
                @can('edit time logs')
                    <a href="{{ route('dtr.edit', $dtr) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Edit Time Logs
                    </a>
                @endcan
                <a href="{{ route('payrolls.show', $dtr->payroll_id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Payroll
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- DTR Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <!-- DTR Header -->
                    <div class="text-center mb-6 border-b-2 border-black pb-4">
                        <h1 class="text-xl font-bold uppercase">Daily Time Record</h1>
                    </div>

                    <!-- Employee Info Section -->
                    <div class="grid grid-cols-2 gap-8 mb-6">
                        <div>
                            <div class="border-b border-black pb-1 mb-2">
                                <span class="text-sm font-medium">Name: </span>
                                <span class="text-lg font-bold uppercase">
                                    @if(isset($dtr->employee) && isset($dtr->employee->user) && isset($dtr->employee->user->name))
                                        {{ $dtr->employee->user->name }}
                                    @elseif(isset($dtr->employee) && isset($dtr->employee->first_name))
                                        {{ $dtr->employee->first_name }} {{ $dtr->employee->last_name ?? '' }}
                                    @else
                                        UNKNOWN EMPLOYEE
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="border-b border-black pb-1 mb-2">
                                <span class="text-sm font-medium">For the Month of: </span>
                                <span class="text-lg font-bold uppercase">{{ \Carbon\Carbon::parse($dtr->period_start ?? now())->format('F Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="grid grid-cols-2 gap-8 mb-6">
                        <div class="text-sm">
                            <div class="mb-1">
                                <span class="font-medium">Regular Days: </span>
                                <span class="border-b border-black px-4">{{ $dtr->regular_days ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="text-sm">
                            <div class="mb-1">
                                <span class="font-medium">Saturday: </span>
                                <span class="border-b border-black px-4">{{ $dtr->saturday_count ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Time Record Table -->
                    <div class="border-2 border-black">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-black">
                                    <th rowspan="2" class="border-r border-black p-2 text-sm font-bold bg-gray-100">Day</th>
                                    <th colspan="2" class="border-r border-black p-2 text-sm font-bold bg-gray-100">AM</th>
                                    <th colspan="2" class="border-r border-black p-2 text-sm font-bold bg-gray-100">PM</th>
                                    <th colspan="2" class="p-2 text-sm font-bold bg-gray-100">Over Time</th>
                                </tr>
                                <tr class="border-b border-black">
                                    <th class="border-r border-black p-1 text-xs font-bold bg-gray-50">Arrival</th>
                                    <th class="border-r border-black p-1 text-xs font-bold bg-gray-50">Depart</th>
                                    <th class="border-r border-black p-1 text-xs font-bold bg-gray-50">Arrival</th>
                                    <th class="border-r border-black p-1 text-xs font-bold bg-gray-50">Depart</th>
                                    <th class="border-r border-black p-1 text-xs font-bold bg-gray-50">Arrival</th>
                                    <th class="p-1 text-xs font-bold bg-gray-50">Depart</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $dtrData = is_string($dtr->dtr_data) ? json_decode($dtr->dtr_data, true) : $dtr->dtr_data;
                                    $dailyRecords = $dtrData && is_array($dtrData) ? $dtrData : [];
                                    
                                    // Generate all dates in the period if no data exists
                                    if (empty($dailyRecords)) {
                                        $currentDate = \Carbon\Carbon::parse($dtr->period_start ?? now()->startOfMonth());
                                        $endDate = \Carbon\Carbon::parse($dtr->period_end ?? now()->endOfMonth());
                                        
                                        while ($currentDate->lte($endDate)) {
                                            $dateStr = $currentDate->format('Y-m-d');
                                            $dailyRecords[$dateStr] = [
                                                'date' => $dateStr,
                                                'day_name' => $currentDate->format('l'),
                                                'is_weekend' => $currentDate->isWeekend(),
                                                'time_in' => null,
                                                'time_out' => null,
                                                'break_start' => null,
                                                'break_end' => null,
                                                'overtime_start' => null,
                                                'overtime_end' => null,
                                            ];
                                            $currentDate->addDay();
                                        }
                                    }
                                    
                                    // Sort by date
                                    ksort($dailyRecords);
                                @endphp

                                @forelse($dailyRecords as $date => $record)
                                    @php
                                        $carbonDate = \Carbon\Carbon::parse($date);
                                        $dayNumber = $carbonDate->day;
                                        $isWeekend = $carbonDate->isWeekend();
                                    @endphp
                                    <tr class="border-b border-gray-300 {{ $isWeekend ? 'bg-gray-100' : '' }}">
                                        <td class="border-r border-black p-2 text-center font-bold">{{ $dayNumber }}</td>
                                        <td class="border-r border-black p-2 text-center">
                                            {{ $record['time_in'] ?? ($isWeekend ? '' : '') }}
                                        </td>
                                        <td class="border-r border-black p-2 text-center">
                                            {{ $record['break_start'] ?? ($isWeekend ? '' : '') }}
                                        </td>
                                        <td class="border-r border-black p-2 text-center">
                                            {{ $record['break_end'] ?? ($isWeekend ? '' : '') }}
                                        </td>
                                        <td class="border-r border-black p-2 text-center">
                                            {{ $record['time_out'] ?? ($isWeekend ? '' : '') }}
                                        </td>
                                        <td class="border-r border-black p-2 text-center">
                                            {{ $record['overtime_start'] ?? '' }}
                                        </td>
                                        <td class="p-2 text-center">
                                            {{ $record['overtime_end'] ?? '' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-8 text-center text-gray-500">
                                            No time records found. Click "Edit Time Logs" to add entries.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Legend -->
                    <div class="mt-4 grid grid-cols-3 gap-4 text-xs">
                        <div>
                            <div class="flex items-center mb-1">
                                <div class="w-3 h-3 bg-blue-500 mr-2"></div>
                                <span>Time In/Out</span>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center mb-1">
                                <div class="w-3 h-3 bg-red-500 mr-2"></div>
                                <span>Overtime Hours</span>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center mb-1">
                                <div class="w-3 h-3 bg-yellow-500 mr-2"></div>
                                <span>Late Hours</span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="mt-6 pt-4 border-t border-gray-300">
                        <div class="text-sm">
                            <div class="mb-2"><strong>Weekend days are highlighted in gray background</strong></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>Total Regular Hours: <strong>{{ number_format($dtr->total_regular_hours ?? 0, 2) }}</strong></div>
                                <div>Total Overtime Hours: <strong>{{ number_format($dtr->total_overtime_hours ?? 0, 2) }}</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
