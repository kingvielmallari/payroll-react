<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Automated Payroll') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">Automatically include all active employees for the selected pay schedule</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    View All Payrolls
                </a>

            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6 flex flex-row items-center justify-between gap-4">
                        <h3 class="text-lg font-medium text-gray-900">Create Automated Payroll</h3>
                        <div class="flex items-center">
                            <div class="bg-blue-100 border border-blue-300 rounded-lg px-8 py-2 text-right">
                                <span class="text-lg font-bold text-blue-800">{{ now()->format('F d, Y') }}</span>
                                <div class="text-xs text-blue-600 text-center">{{ now()->format('l') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Selection -->
                    <div class="flex justify-center">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl">
                        @forelse($scheduleSettings as $schedule)
                            {{-- Only show cards if schedule is active --}}
                            @if($schedule->is_active)
                            <a href="{{ route('payrolls.automation.create', ['schedule' => $schedule->code]) }}" 
                               class="block border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-lg hover:bg-blue-50 transition-all duration-200 cursor-pointer transform hover:scale-105 h-80 min-h-80 max-h-80 w-full">
                                <div class="py-6 px-8 h-full flex flex-col">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $schedule->name }}</h4>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full
                                            {{ $schedule->code === 'weekly' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $schedule->code === 'semi_monthly' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $schedule->code === 'monthly' ? 'bg-purple-100 text-purple-800' : '' }}">
                                            {{ ucfirst(str_replace('_', ' ', $schedule->code)) }}
                                        </span>
                                    </div>

                                    <!-- Active Employees Count -->
                                    <div class="mb-4">
                                        <div class="flex items-center text-sm text-gray-600 mb-2">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Active Employees: 
                                            <span class="font-medium ml-1">{{ $schedule->active_employees_count ?? 0 }}</span>
                                        </div>
                                        
                                        @if(isset($schedule->last_payroll_period))
                                            <div class="flex items-center text-sm text-gray-500">
                                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                </svg>
                                                Last payroll: {{ $schedule->last_payroll_period }}
                                            </div>
                                        @else
                                            <div class="flex items-center text-sm text-gray-500">
                                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                </svg>
                                                Last payroll: No previous payrolls
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Current Pay Period Info -->
                                    @if(isset($schedule->next_period))
                                        <div class="bg-gray-50 rounded-md p-3 mb-4 flex-grow">
                                            <h5 class="text-sm font-medium text-gray-900 mb-2">Current Pay Period</h5>
                                            <div class="text-sm text-gray-600">
                                                <div class="flex justify-between mb-1">
                                                    <span>Period:</span>
                                                    <span class="font-medium">
                                                        {{ \Carbon\Carbon::parse($schedule->next_period['start'])->format('M d') }} - 
                                                        {{ \Carbon\Carbon::parse($schedule->next_period['end'])->format('d, Y') }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span>Pay Date:</span>
                                                    <span class="font-medium">{{ \Carbon\Carbon::parse($schedule->next_period['pay_date'])->format('M d, Y') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="bg-gray-50 rounded-md p-3 mb-4 flex-grow">
                                            <h5 class="text-sm font-medium text-gray-900 mb-2">Current Pay Period</h5>
                                            <div class="text-sm text-gray-500">
                                                No period information available
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Click to Continue Indicator -->
                                    <div class="text-center mt-auto">
                                        <div class="text-sm text-blue-600 font-medium">
                                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                            Create Payroll Now
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            @if(($schedule->active_employees_count ?? 0) > 0)
                                                Click to create payroll for {{ $schedule->active_employees_count }} active employee{{ $schedule->active_employees_count > 1 ? 's' : '' }}
                                            @else
                                                Click to view payrolls (no active employees)
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                            @endif
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

                        {{-- Check if all schedules were filtered out --}}
                        @if($scheduleSettings->isNotEmpty() && $scheduleSettings->where('is_active', true)->isEmpty())
                            <div class="col-span-full text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 9v3m0 0v3m0-3h3m-3 0H9m3 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No available pay schedules</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    All pay schedules are currently inactive.
                                </p>
                                <div class="mt-4 text-sm text-gray-600">
                                    <p>To create automated payrolls, you need:</p>
                                    <ul class="mt-2 list-disc list-inside space-y-1">
                                        <li>At least one active pay schedule</li>
                                        <li>Employees can be added after creating the schedule</li>
                                    </ul>
                                </div>
                                <div class="mt-6 space-x-3">
                                    @can('manage settings')
                                        <a href="{{ route('settings.pay-schedules.index') }}" 
                                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            Manage Pay Schedules
                                        </a>
                                    @endcan
                                    @can('view employees')
                                        <a href="{{ route('employees.index') }}" 
                                           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Manage Employees
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        @endif
                        </div>
                    </div>

                    <!-- Information Panel -->
                    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">About Automated Payroll</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Manage automated payrolls by schedule:</p>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        <li>Click any schedule to view existing automated payrolls</li>
                                        <li>Generate new payrolls that automatically include all active employees</li>
                                        <li>Pay periods are calculated based on schedule settings</li>
                                        <li>Perfect for regular, recurring payroll processing</li>
                                        <li>Track all automated payrolls by their specific schedule</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
