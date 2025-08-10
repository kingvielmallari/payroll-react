<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    DTR - {{ $dtr->employee->user->name ?? $dtr->employee->first_name ?? 'Unknown Employee' }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($dtr->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($dtr->period_end)->format('M d, Y') }}
                    ({{ ucfirst(str_replace('_', ' ', $dtr->period_type)) }})
                </p>
            </div>
            <div class="flex space-x-2">
                @can('edit time logs')
                    <a href="{{ route('dtr.edit', $dtr) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add/Edit Time Logs
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
            <!-- Employee Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <div class="text-sm font-medium text-gray-500">Employee Name</div>
                            <div class="mt-1 text-sm text-gray-900">{{ $dtr->employee->first_name ?? 'Unknown' }} {{ $dtr->employee->last_name ?? '' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Employee Number</div>
                            <div class="mt-1 text-sm text-gray-900">{{ $dtr->employee->employee_number ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">DTR ID</div>
                            <div class="mt-1 text-sm text-gray-900">{{ $dtr->id }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Payroll ID</div>
                            <div class="mt-1 text-sm text-gray-900">{{ $dtr->payroll_id }}</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-4">
                        <div>
                            <div class="text-sm font-medium text-gray-500">Status</div>
                            <div class="mt-1 text-sm text-gray-900">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $dtr->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                       ($dtr->status == 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst($dtr->status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Total Regular Hours</div>
                            <div class="mt-1 text-sm text-gray-900">{{ number_format($dtr->total_regular_hours, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Total Overtime Hours</div>
                            <div class="mt-1 text-sm text-gray-900">{{ number_format($dtr->total_overtime_hours, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Total Late Hours</div>
                            <div class="mt-1 text-sm text-gray-900">{{ number_format($dtr->total_late_hours, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Time Records -->
            @php
                $dtrData = is_string($dtr->dtr_data) ? json_decode($dtr->dtr_data, true) : $dtr->dtr_data;
                $dailyRecords = $dtrData && is_array($dtrData) ? $dtrData : [];
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Time Records</h3>
                    
                    @if(empty($dailyRecords))
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-lg">No time records found.</p>
                            <p class="text-gray-400 text-sm mt-2">Click "Add/Edit Time Logs" to add daily time entries.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($dailyRecords as $date => $record)
                                        <tr class="{{ $record['is_weekend'] ?? false ? 'bg-gray-50' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($date)->format('M d') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $record['day_name'] ?? \Carbon\Carbon::parse($date)->format('l') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $record['time_in'] ?? '--:--' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $record['time_out'] ?? '--:--' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($record['break_start'] && $record['break_end'])
                                                    {{ $record['break_start'] }} - {{ $record['break_end'] }}
                                                @else
                                                    --
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($record['overtime_start'] && $record['overtime_end'])
                                                    {{ $record['overtime_start'] }} - {{ $record['overtime_end'] }}
                                                @else
                                                    --
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="text-xs">
                                                    <div>Reg: {{ number_format($record['regular_hours'] ?? 0, 2) }}h</div>
                                                    @if(($record['overtime_hours'] ?? 0) > 0)
                                                        <div class="text-blue-600">OT: {{ number_format($record['overtime_hours'], 2) }}h</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ ($record['status'] ?? 'pending') == 'present' ? 'bg-green-100 text-green-800' :
                                                       (($record['status'] ?? 'pending') == 'absent' ? 'bg-red-100 text-red-800' :
                                                        (($record['status'] ?? 'pending') == 'weekend' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                                    {{ ucfirst($record['status'] ?? 'pending') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- DTR Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">DTR Summary</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ number_format($dtr->total_regular_hours, 1) }}</div>
                            <div class="text-sm text-gray-500">Regular Hours</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ number_format($dtr->total_overtime_hours, 1) }}</div>
                            <div class="text-sm text-gray-500">Overtime Hours</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">{{ number_format($dtr->total_late_hours, 1) }}</div>
                            <div class="text-sm text-gray-500">Late Hours</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">{{ number_format($dtr->total_undertime_hours, 1) }}</div>
                            <div class="text-sm text-gray-500">Undertime Hours</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Time Records -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Daily Time Records</h3>
                        <div class="text-sm text-gray-500">
                            Status: <span class="px-2 py-1 text-xs font-medium rounded-full 
                                {{ $dtr->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $dtr->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $dtr->status === 'finalized' ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ ucfirst($dtr->status) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regular Hours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late Hours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @if(is_array($dtr->dtr_data))
                                    @foreach($dtr->dtr_data as $date => $record)
                                    <tr class="{{ $record['is_weekend'] ? 'bg-gray-50' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $record['day_name'] ?? \Carbon\Carbon::parse($date)->format('l') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $record['time_in'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $record['time_out'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-medium">
                                            {{ number_format($record['regular_hours'] ?? 0, 1) }}h
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">
                                            {{ number_format($record['overtime_hours'] ?? 0, 1) }}h
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">
                                            {{ number_format($record['late_hours'] ?? 0, 1) }}h
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                                {{ ($record['status'] ?? '') === 'present' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ ($record['status'] ?? '') === 'absent' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ ($record['status'] ?? '') === 'weekend' ? 'bg-gray-100 text-gray-800' : '' }}
                                                {{ ($record['status'] ?? '') === 'holiday' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ ($record['status'] ?? '') === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                                {{ ucfirst($record['status'] ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                            No DTR data available. Time logs can be added later.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Add Time Log Button -->
                    <div class="mt-6 flex justify-center">
                        <a href="{{ route('dtr.edit', $dtr) }}" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Time Logs
                        </a>
                    </div>
                </div>
            </div>

            @if($dtr->remarks)
            <!-- Remarks -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Remarks</h3>
                    <p class="text-sm text-gray-700">{{ $dtr->remarks }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
