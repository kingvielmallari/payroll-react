<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee Details') }} - {{ $employee->full_name }}
            </h2>
            <div class="flex space-x-2">
                @can('edit employees')
                    <a href="{{ route('employees.edit', $employee->employee_number) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Edit Employee
                    </a>
                @endcan
                <a href="{{ route('employees.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Employees
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Employee Status & Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $employee->full_name }}</h3>
                            <p class="text-lg text-gray-600">{{ $employee->position->title ?? 'N/A' }} - {{ $employee->department->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500">Employee #: {{ $employee->employee_number }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                @if($employee->employment_status === 'active') bg-green-100 text-green-800
                                @elseif($employee->employment_status === 'inactive') bg-yellow-100 text-yellow-800
                                @elseif($employee->employment_status === 'terminated') bg-red-100 text-red-800
                                @elseif($employee->employment_status === 'resigned') bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($employee->employment_status) }}
                            </span>
                            <p class="text-sm text-gray-500 mt-1">{{ ucfirst($employee->employment_type) }}</p>
                            <p class="text-sm text-gray-500">Age: {{ $employee->age }} years</p>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-blue-900">Years of Service</h4>
                            <p class="text-2xl font-bold text-blue-700">{{ $employee->years_of_service }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-green-900">Basic Salary</h4>
                            @if($employee->fixed_rate && $employee->rate_type)
                                @php
                                    // Use current month for MBS calculation to get accurate working days
                                    $currentMonthStart = now()->startOfMonth();
                                    $currentMonthEnd = now()->endOfMonth();
                                @endphp
                                <p class="text-2xl font-bold text-green-700">₱{{ number_format($employee->calculateMonthlyBasicSalary($currentMonthStart, $currentMonthEnd), 2) }}</p>
                                <p class="text-xs text-green-600">(Dynamic from {{ ucfirst(str_replace('_', '-', $employee->rate_type)) }})</p>
                            @else
                                <p class="text-2xl font-bold text-green-700">₱{{ number_format($employee->basic_salary, 2) }}</p>
                            @endif
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-purple-900">This Month's Logs</h4>
                            <p class="text-2xl font-bold text-purple-700">{{ $employee->thisMonthTimeLogs()->count() }}</p>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-orange-900">Leave Requests</h4>
                            <p class="text-2xl font-bold text-orange-700">{{ $employee->leaveRequests()->where('status', 'pending')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Personal Information</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Full Name:</span>
                                <span class="text-sm text-gray-900">{{ $employee->full_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Birth Date:</span>
                                <span class="text-sm text-gray-900">{{ $employee->birth_date?->format('F j, Y') ?? 'N/A' }} ({{ $employee->age }} years old)</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Gender:</span>
                                <span class="text-sm text-gray-900">{{ ucfirst($employee->gender) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Civil Status:</span>
                                <span class="text-sm text-gray-900">{{ ucfirst($employee->civil_status) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Phone:</span>
                                <span class="text-sm text-gray-900">{{ $employee->phone ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Email:</span>
                                <span class="text-sm text-gray-900">{{ $employee->user->email ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Address:</span>
                                <p class="text-sm text-gray-900 mt-1">{{ $employee->address }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Employment Information</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Employee Number:</span>
                                <span class="text-sm text-gray-900">{{ $employee->employee_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Department:</span>
                                <span class="text-sm text-gray-900">{{ $employee->department->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Position:</span>
                                <span class="text-sm text-gray-900">{{ $employee->position->title ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Time Schedule:</span>
                                <span class="text-sm text-gray-900">{{ $employee->timeSchedule->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Day Schedule:</span>
                                <span class="text-sm text-gray-900">{{ $employee->daySchedule->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Hire Date:</span>
                                <span class="text-sm text-gray-900">{{ $employee->hire_date?->format('F j, Y') ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Regularization Date:</span>
                                <span class="text-sm text-gray-900">{{ $employee->regularization_date?->format('F j, Y') ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Employment Type:</span>
                                <span class="text-sm text-gray-900">{{ ucfirst($employee->employment_type) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">User Role:</span>
                                <span class="text-sm text-gray-900">{{ $employee->user?->roles->first()?->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Salary Information</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Basic Salary (Monthly):</span>
                                @if($employee->fixed_rate && $employee->rate_type)
                                    @php
                                        // Use current month for MBS calculation to get accurate working days
                                        $currentMonthStart = now()->startOfMonth();
                                        $currentMonthEnd = now()->endOfMonth();
                                    @endphp
                                    <span class="text-sm font-bold text-green-600">₱{{ number_format($employee->calculateMonthlyBasicSalary($currentMonthStart, $currentMonthEnd), 2) }}</span>
                                    <span class="text-xs text-gray-500 block text-right">
                                        (Calculated from ₱{{ number_format($employee->fixed_rate, 2) }}/{{ ucfirst(str_replace('_', '-', $employee->rate_type)) }})
                                    </span>
                                @else
                                    <span class="text-sm font-bold text-green-600">₱{{ number_format($employee->basic_salary, 2) }}</span>
                                @endif
                            </div>
                            @if($employee->daily_rate)
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Daily Rate:</span>
                                <span class="text-sm text-gray-900">₱{{ number_format($employee->daily_rate, 2) }}</span>
                            </div>
                            @endif
                            @if($employee->hourly_rate)
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Hourly Rate:</span>
                                <span class="text-sm text-gray-900">₱{{ number_format($employee->hourly_rate, 2) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Government IDs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Government IDs</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">SSS Number:</span>
                                <span class="text-sm text-gray-900">{{ $employee->sss_number ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">PhilHealth Number:</span>
                                <span class="text-sm text-gray-900">{{ $employee->philhealth_number ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Pag-IBIG Number:</span>
                                <span class="text-sm text-gray-900">{{ $employee->pagibig_number ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">TIN Number:</span>
                                <span class="text-sm text-gray-900">{{ $employee->tin_number ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Emergency Contact</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Contact Name:</span>
                                <span class="text-sm text-gray-900">{{ $employee->emergency_contact_name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Relationship:</span>
                                <span class="text-sm text-gray-900">{{ $employee->emergency_contact_relationship ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Phone Number:</span>
                                <span class="text-sm text-gray-900">{{ $employee->emergency_contact_phone ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Bank Information</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Bank Name:</span>
                                <span class="text-sm text-gray-900">{{ $employee->bank_name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Account Number:</span>
                                <span class="text-sm text-gray-900">{{ $employee->bank_account_number ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Account Name:</span>
                                <span class="text-sm text-gray-900">{{ $employee->bank_account_name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Time Logs -->
            @if($employee->timeLogs()->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recent Time Logs</h3>
                        @can('time_logs.view')
                        <a href="#" class="text-blue-600 hover:text-blue-900 text-sm">View All Time Logs</a>
                        @endcan
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($employee->timeLogs()->latest('log_date')->limit(10)->get() as $timeLog)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $timeLog->log_date->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $timeLog->time_in?->format('g:i A') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $timeLog->time_out?->format('g:i A') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $timeLog->total_hours ? number_format($timeLog->total_hours, 2) . ' hrs' : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($timeLog->status === 'present') bg-green-100 text-green-800
                                            @elseif($timeLog->status === 'late') bg-yellow-100 text-yellow-800
                                            @elseif($timeLog->status === 'absent') bg-red-100 text-red-800
                                            @elseif($timeLog->status === 'undertime') bg-orange-100 text-orange-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($timeLog->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Leave Requests -->
            @if($employee->leaveRequests()->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recent Leave Requests</h3>
                        @can('leave_requests.view')
                        <a href="#" class="text-blue-600 hover:text-blue-900 text-sm">View All Leave Requests</a>
                        @endcan
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Range</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($employee->leaveRequests()->latest()->limit(5)->get() as $leaveRequest)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ ucfirst(str_replace('_', ' ', $leaveRequest->leave_type)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $leaveRequest->start_date->format('M j') }} - {{ $leaveRequest->end_date->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $leaveRequest->total_days }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($leaveRequest->status === 'approved') bg-green-100 text-green-800
                                            @elseif($leaveRequest->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($leaveRequest->status === 'rejected') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($leaveRequest->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $leaveRequest->created_at->format('M j, Y') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Activity -->
            @if($employee->activities()->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                    
                    <div class="space-y-4">
                        @foreach($employee->activities()->latest()->limit(10)->get() as $activity)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                                <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
