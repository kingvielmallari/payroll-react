<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                DTR (Daily Time Record) Management
            </h2>
            <div class="flex space-x-2">
                @can('import time logs')
                <a href="{{ route('time-logs.import-form') }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Import DTR
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Warning Message (for partial import success) -->
            @if (session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('warning') }}</span>
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Import Errors -->
            @if (session('import_errors') && count(session('import_errors')) > 0)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Import Errors ({{ count(session('import_errors')) }} errors)</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p class="mb-2">The following errors occurred during import:</p>
                                <div class="max-h-48 overflow-y-auto">
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach(session('import_errors') as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- DTR System Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">New DTR Management System Available!</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>We've introduced a new DTR system that allows you to:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Select specific payroll periods</li>
                                <li>Generate printable DTR forms like the official government format</li>
                                <li>Save DTR records to the database for proper record keeping</li>
                                <li>Create both cutoff period and full month DTR versions</li>
                                <li>Export to PDF for distribution to employees</li>
                            </ul>
                            <div class="mt-3">
                                <a href="{{ route('dtr.index') }}" 
                                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Try New DTR System
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DTR Period Information -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 overflow-hidden shadow-sm sm:rounded-lg">&n             <div class="p-6 text-white">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ $currentPeriod['period_label'] }}</div>
                            <div class="text-blue-100 text-sm">Current Payroll Period</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-semibold">{{ $payrollSettings->frequency === 'semi_monthly' ? 'Semi-Monthly' : 'Monthly' }} Payroll</div>
                            <div class="text-blue-100 text-sm">{{ $currentPeriod['pay_label'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-semibold">{{ $employees->count() }} Active Employees</div>
                            <div class="text-blue-100 text-sm">Ready for DTR Entry</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DTR Instructions -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Simple DTR System:</strong> Select an employee to manage their DTR for the current period ({{ $currentPeriod['period_label'] }}). 
                            You can also <strong>Import Excel files</strong> with the format: Employee Number, Email, Date, Time In, Time Out, Break In, Break Out.
                            <br><small class="text-yellow-600 mt-1 block">Missing break times will automatically deduct 1 hour from total working hours.</small>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Employees Grid for DTR Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($employees as $employee)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600">
                                            {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $employee->employee_number }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="text-xs text-gray-600 mb-1">Department</div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ $employee->department->name ?? 'No Department' }}
                            </div>
                        </div>

                        @php
                            $employeeTimeLogs = $employee->timeLogs()
                                ->whereBetween('log_date', [$currentPeriod['start_date']->format('Y-m-d'), $currentPeriod['end_date']->format('Y-m-d')])
                                ->get();
                            $daysPresent = $employeeTimeLogs->where('time_in', '!=', null)->count();
                            $totalDays = $currentPeriod['start_date']->diffInDays($currentPeriod['end_date']) + 1;
                            $totalHours = $employeeTimeLogs->sum('total_hours');
                        @endphp

                        <div class="grid grid-cols-3 gap-3 mb-4 text-center">
                            <div class="bg-green-50 p-2 rounded">
                                <div class="text-lg font-bold text-green-600">{{ $daysPresent }}</div>
                                <div class="text-xs text-green-700">Present</div>
                            </div>
                            <div class="bg-red-50 p-2 rounded">
                                <div class="text-lg font-bold text-red-600">{{ $totalDays - $daysPresent }}</div>
                                <div class="text-xs text-red-700">Absent</div>
                            </div>
                            <div class="bg-blue-50 p-2 rounded">
                                <div class="text-lg font-bold text-blue-600">{{ number_format($totalHours, 1) }}h</div>
                                <div class="text-xs text-blue-700">Total</div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <div class="text-xs text-gray-500">
                                DTR Progress: {{ $daysPresent }}/{{ $totalDays }} days
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $totalDays > 0 ? ($daysPresent / $totalDays) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex space-x-2">
                            <a href="{{ route('time-logs.show-dtr', $employee) }}" 
                               class="flex-1 inline-flex justify-center items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Standard DTR
                            </a>
                            <a href="{{ route('time-logs.simple-dtr', $employee) }}" 
                               class="flex-1 inline-flex justify-center items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12,6 12,12 16,14"></polyline>
                                </svg>
                                Simple DTR
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No active employees</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by adding employees to the system.</p>
                    </div>
                </div>
                @endforelse
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @can('create time logs')
                        <a href="{{ route('time-logs.create-bulk') }}" 
                           class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm bg-white text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Bulk Time Entry
                        </a>
                        @endcan
                        
                        @can('import time logs')
                        <a href="{{ route('time-logs.import-form') }}" 
                           class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm bg-white text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Import DTR Data
                        </a>
                        @endcan
                        
                        @can('import time logs')
                        <a href="{{ route('time-logs.export-template') }}" 
                           class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm bg-white text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download Template
                        </a>
                        @endcan
                        
                        <a href="#" 
                           class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm bg-white text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            DTR Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
