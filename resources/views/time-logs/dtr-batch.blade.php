<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    DTR Details - {{ $dtrBatch->first_name }} {{ $dtrBatch->last_name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($dtrBatch->period_start)->format('M d') }} - 
                    {{ \Carbon\Carbon::parse($dtrBatch->period_end)->format('M d, Y') }}
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('time-logs.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to DTR List
                </a>
                @if($dtrBatch->payroll_id)
                <a href="{{ route('payrolls.show', $dtrBatch->payroll_id) }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                    View Payroll
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Employee and Summary Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Employee Details Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Employee Information</h3>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $dtrBatch->first_name }} {{ $dtrBatch->last_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Employee Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $dtrBatch->employee_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $dtrBatch->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Department</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $dtrBatch->department_name ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Summary Statistics Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Period Summary</h3>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Regular Hours</dt>
                                <dd class="mt-1 text-lg font-semibold text-green-600">{{ number_format($dtrBatch->total_regular_hours, 2) }}h</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Overtime Hours</dt>
                                <dd class="mt-1 text-lg font-semibold text-blue-600">{{ number_format($dtrBatch->total_overtime_hours, 2) }}h</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Late Hours</dt>
                                <dd class="mt-1 text-lg font-semibold text-red-600">{{ number_format($dtrBatch->total_late_hours, 2) }}h</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Regular Days</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $dtrBatch->regular_days ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $dtrBatch->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($dtrBatch->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($dtrBatch->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Payroll</dt>
                                <dd class="mt-1">
                                    @if($dtrBatch->payroll_id)
                                        <span class="text-indigo-600">{{ $dtrBatch->period_label ?? 'Payroll #' . $dtrBatch->payroll_id }}</span>
                                    @else
                                        <span class="text-gray-400">No Payroll Assigned</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Daily Time Records -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Daily Time Records</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Detailed breakdown of daily attendance for the selected period.
                    </p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break Out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regular Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($periodDates as $dayData)
                            <tr class="hover:bg-gray-50 {{ $dayData['is_weekend'] ? 'bg-gray-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($dayData['date'])->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="font-medium {{ $dayData['is_weekend'] ? 'text-orange-600' : 'text-gray-900' }}">
                                        {{ $dayData['day_name'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dayData['time_in'])
                                        {{ \Carbon\Carbon::parse($dayData['time_in'])->format('g:i A') }}
                                    @else
                                        <span class="text-gray-400">--</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dayData['break_start'])
                                        {{ \Carbon\Carbon::parse($dayData['break_start'])->format('g:i A') }}
                                    @else
                                        <span class="text-gray-400">--</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dayData['break_end'])
                                        {{ \Carbon\Carbon::parse($dayData['break_end'])->format('g:i A') }}
                                    @else
                                        <span class="text-gray-400">--</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dayData['time_out'])
                                        {{ \Carbon\Carbon::parse($dayData['time_out'])->format('g:i A') }}
                                    @else
                                        <span class="text-gray-400">--</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dayData['regular_hours'] > 0)
                                        <span class="font-medium text-green-600">{{ number_format($dayData['regular_hours'], 2) }}h</span>
                                    @else
                                        <span class="text-gray-400">0h</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dayData['overtime_hours'] > 0)
                                        <span class="font-medium text-blue-600">{{ number_format($dayData['overtime_hours'], 2) }}h</span>
                                    @else
                                        <span class="text-gray-400">0h</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                    @if($dayData['remarks'])
                                        <span class="truncate">{{ $dayData['remarks'] }}</span>
                                    @elseif($dayData['is_weekend'])
                                        <span class="text-orange-600 text-xs">Weekend</span>
                                    @elseif(!$dayData['time_in'] && !$dayData['time_out'])
                                        <span class="text-red-600 text-xs">No Record</span>
                                    @else
                                        <span class="text-gray-400">--</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Related Time Logs (if any exist separately) -->
            @if($timeLogs->count() > 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Related Individual Time Logs</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Individual time log entries that were created separately from this DTR batch.
                    </p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($timeLogs as $timeLog)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($timeLog->log_date)->format('M d, Y') }}
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($timeLog->log_date)->format('l') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $timeLog->time_in ? \Carbon\Carbon::parse($timeLog->time_in)->format('g:i A') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $timeLog->time_out ? \Carbon\Carbon::parse($timeLog->time_out)->format('g:i A') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ number_format($timeLog->total_hours, 2) }}h</span>
                                        @if($timeLog->overtime_hours > 0)
                                            <span class="text-xs text-blue-600">OT: {{ number_format($timeLog->overtime_hours, 2) }}h</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="capitalize">{{ $timeLog->log_type }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                    {{ $timeLog->remarks ?: '--' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
