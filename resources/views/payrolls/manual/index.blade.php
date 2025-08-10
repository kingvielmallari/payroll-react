<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Manual Payroll') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">Manually select employees for your payroll</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    View All Payrolls
                </a>
                <a href="{{ route('payrolls.automation.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Automated Payroll
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Create Manual Payroll</h3>
                    </div>

                    <!-- Schedule Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse($scheduleSettings as $schedule)
                            <div class="border border-gray-200 rounded-lg hover:border-green-500 hover:shadow-md transition-all duration-200">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $schedule->name }}</h4>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full
                                            {{ $schedule->code === 'weekly' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $schedule->code === 'semi_monthly' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $schedule->code === 'monthly' ? 'bg-purple-100 text-purple-800' : '' }}">
                                            {{ ucfirst(str_replace('_', ' ', $schedule->code)) }}
                                        </span>
                                    </div>

                                    <!-- Available Employees Count -->
                                    <div class="mb-4">
                                        <div class="flex items-center text-sm text-gray-600 mb-2">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Available Employees: 
                                            <span class="font-medium ml-1">{{ $schedule->total_employees_count ?? 0 }}</span>
                                        </div>
                                        
                                        <div class="flex items-center text-sm text-gray-500 mb-2">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Active: {{ $schedule->active_employees_count ?? 0 }}
                                        </div>

                                        <div class="flex items-center text-sm text-gray-500">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 008.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Inactive: {{ ($schedule->total_employees_count ?? 0) - ($schedule->active_employees_count ?? 0) }}
                                        </div>
                                        
                                        @if(isset($schedule->last_payroll_date))
                                            <div class="flex items-center text-sm text-gray-500 mt-2">
                                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                </svg>
                                                Last payroll: {{ \Carbon\Carbon::parse($schedule->last_payroll_date)->format('M d, Y') }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Next Pay Period Info -->
                                    @if(isset($schedule->next_period))
                                        <div class="bg-gray-50 rounded-md p-3 mb-4">
                                            <h5 class="text-sm font-medium text-gray-900 mb-2">Next Pay Period</h5>
                                            <div class="text-sm text-gray-600">
                                                <div class="flex justify-between mb-1">
                                                    <span>Period:</span>
                                                    <span class="font-medium">
                                                        {{ \Carbon\Carbon::parse($schedule->next_period['start'])->format('M d') }} - 
                                                        {{ \Carbon\Carbon::parse($schedule->next_period['end'])->format('M d, Y') }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span>Pay Date:</span>
                                                    <span class="font-medium">{{ \Carbon\Carbon::parse($schedule->next_period['pay_date'])->format('M d, Y') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Create Button -->
                                    <form action="{{ route('payrolls.manual.create') }}" method="GET" class="w-full">
                                        <input type="hidden" name="schedule" value="{{ $schedule->code }}">
                                        <button type="submit" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-4 rounded transition-colors duration-200
                                            {{ ($schedule->total_employees_count ?? 0) === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                            {{ ($schedule->total_employees_count ?? 0) === 0 ? 'disabled' : '' }}>
                                            <div class="flex items-center justify-center">
                                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010 2h1.586l-2.293 2.293a1 1 0 001.414 1.414L15 8.414V10a1 1 0 002 0V6a1 1 0 00-1-1h-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Create Manual Payroll
                                            </div>
                                        </button>
                                    </form>

                                    @if(($schedule->total_employees_count ?? 0) === 0)
                                        <p class="text-xs text-red-600 mt-2 text-center">No employees found for this schedule</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M8 7V3a2 2 0 012-2h8a2 2 0 012 2v4m0 0V7a2 2 0 012 2v6.586l6.414 6.414a2 2 0 010 2.828l-3.828 3.828a2 2 0 01-2.828 0L26 19.414V13a2 2 0 012-2V7m0 0V3a2 2 0 012-2h8a2 2 0 012 2v4M8 7l4 4m0 0l4-4m0 0V3a2 2 0 012-2h8a2 2 0 012 2v4"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No pay schedules configured</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    You need to configure pay schedules before creating payrolls.
                                </p>
                                <div class="mt-6">
                                    @can('manage settings')
                                        <a href="{{ route('settings.pay-schedules.index') }}" 
                                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            Configure Pay Schedules
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Information Panel -->
                    <div class="mt-8 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">About Manual Payroll</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Manual payroll gives you full control:</p>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        <li>Choose specific employees to include or exclude</li>
                                        <li>Include both active and inactive employees</li>
                                        <li>Set custom pay period dates</li>
                                        <li>Review and adjust all calculations manually</li>
                                        <li>Perfect for special payrolls, bonuses, or adjustments</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Quick Actions</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="{{ route('employees.index') }}" class="flex items-center p-3 bg-white rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-sm transition-all">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Manage Employees</div>
                                    <div class="text-xs text-gray-500">View and edit employee data</div>
                                </div>
                            </a>
                            
                            <a href="{{ route('settings.pay-schedules.index') }}" class="flex items-center p-3 bg-white rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-sm transition-all">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Pay Schedules</div>
                                    <div class="text-xs text-gray-500">Configure payroll schedules</div>
                                </div>
                            </a>

                            <a href="{{ route('payrolls.index') }}" class="flex items-center p-3 bg-white rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-sm transition-all">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Payroll History</div>
                                    <div class="text-xs text-gray-500">View past payrolls</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
