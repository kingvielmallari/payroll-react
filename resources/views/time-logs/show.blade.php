<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Time Log Details
            </h2>
            <div class="flex space-x-2">
                @can('edit time logs')
                @if($timeLog->status !== 'approved')
                <a href="{{ route('time-logs.edit', $timeLog) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit
                </a>
                @endif
                @endcan
                <a href="{{ route('time-logs.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Time Log Details Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Employee Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Employee Information</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employee Name</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $timeLog->employee->first_name }} {{ $timeLog->employee->last_name }}
                                    @if($timeLog->employee->user)
                                        ({{ $timeLog->employee->user->name }})
                                    @endif
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employee Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $timeLog->employee->employee_number }}</p>
                            </div>

                            @if($timeLog->employee->department)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $timeLog->employee->department->name }}</p>
                            </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Log Date</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $timeLog->log_date->format('F d, Y (l)') }}</p>
                            </div>
                        </div>

                        <!-- Time Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Time Information</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Time In</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $timeLog->time_in ? $timeLog->time_in->format('g:i A') : 'Not recorded' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Time Out</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $timeLog->time_out ? $timeLog->time_out->format('g:i A') : 'Not recorded' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Break In</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $timeLog->break_in ? $timeLog->break_in->format('g:i A') : 'Not recorded' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Break Out</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $timeLog->break_out ? $timeLog->break_out->format('g:i A') : 'Not recorded' }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Log Type</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $timeLog->log_type === 'regular' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $timeLog->log_type === 'overtime' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $timeLog->log_type === 'holiday' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $timeLog->log_type === 'rest_day' ? 'bg-purple-100 text-purple-800' : '' }}">
                                        {{ ucfirst($timeLog->log_type) }}
                                    </span>
                                </p>
                            </div>

                            @if($timeLog->remarks)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $timeLog->remarks }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Hours Summary -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Hours Summary</h3>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($timeLog->total_hours, 2) }}</div>
                                <div class="text-sm text-gray-600">Total Hours</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-green-900">{{ number_format($timeLog->regular_hours, 2) }}</div>
                                <div class="text-sm text-green-600">Regular Hours</div>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-blue-900">{{ number_format($timeLog->overtime_hours, 2) }}</div>
                                <div class="text-sm text-blue-600">Overtime Hours</div>
                            </div>
                            <div class="bg-red-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-red-900">{{ number_format($timeLog->late_hours, 2) }}</div>
                                <div class="text-sm text-red-600">Late Hours</div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-yellow-900">{{ number_format($timeLog->undertime_hours, 2) }}</div>
                                <div class="text-sm text-yellow-600">Undertime Hours</div>
                            </div>
                        </div>
                    </div>

                    <!-- Status and Approval Information -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Status Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        {{ $timeLog->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $timeLog->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $timeLog->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($timeLog->status) }}
                                    </span>
                                </p>
                            </div>

                            @if($timeLog->status === 'approved' && $timeLog->approver)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Approved By</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $timeLog->approver->name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Approved At</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $timeLog->approved_at ? $timeLog->approved_at->format('M d, Y g:i A') : 'N/A' }}
                                </p>
                            </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Special Flags</label>
                                <div class="mt-1 space-y-1">
                                    @if($timeLog->is_holiday)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Holiday
                                        </span>
                                    @endif
                                    @if($timeLog->is_rest_day)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Rest Day
                                        </span>
                                    @endif
                                    @if(!$timeLog->is_holiday && !$timeLog->is_rest_day)
                                        <span class="text-sm text-gray-500">None</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    @can('approve time logs')
                    @if($timeLog->status === 'pending')
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                        <div class="flex space-x-3">
                            <form action="{{ route('time-logs.approve', $timeLog) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Are you sure you want to approve this time log?')"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Approve Time Log
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                    @endcan

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
