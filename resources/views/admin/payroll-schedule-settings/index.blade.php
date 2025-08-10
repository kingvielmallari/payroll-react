<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Payroll Schedule Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Payroll Schedule Settings List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($payrollSchedules->count() > 0)
                        <!-- Instruction -->
                        <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Tip:</strong> Configure cut-off periods and pay dates for different payment schedules. These settings will be used when creating payrolls.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Responsive Card Layout for Mobile -->
                        <div class="block md:hidden space-y-4">
                            @foreach($payrollSchedules as $schedule)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $schedule->pay_type === 'weekly' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $schedule->pay_type === 'semi_monthly' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $schedule->pay_type === 'monthly' ? 'bg-purple-100 text-purple-800' : '' }}
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $schedule->pay_type)) }}
                                        </span>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div><span class="text-gray-500">Cut-off:</span> {{ $schedule->cutoff_description }}</div>
                                        <div><span class="text-gray-500">Pay Day:</span> {{ $schedule->payday_description }}</div>
                                        @if($schedule->notes)
                                            <div class="text-gray-600 text-xs">{{ Str::limit($schedule->notes, 60) }}</div>
                                        @endif
                                    </div>
                                    <div class="mt-3 flex justify-end">
                                        <a href="{{ route('payroll-schedule-settings.edit', $schedule) }}" 
                                           class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Table Layout for Desktop -->
                        <div class="hidden md:block">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Pay Schedule
                                        </th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cut-off Period
                                        </th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Pay Day
                                        </th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payrollSchedules as $schedule)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-3 py-4">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $schedule->pay_type === 'weekly' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $schedule->pay_type === 'semi_monthly' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $schedule->pay_type === 'monthly' ? 'bg-purple-100 text-purple-800' : '' }}
                                                ">
                                                    {{ ucfirst(str_replace('_', ' ', $schedule->pay_type)) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-900">
                                                {{ $schedule->cutoff_description }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-900">
                                                {{ $schedule->payday_description }}
                                            </td>
                                            <td class="px-3 py-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-sm font-medium">
                                                <a href="{{ route('payroll-schedule-settings.edit', $schedule) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No payroll schedules</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating default payroll schedule settings.</p>
                        </div>
                    @endif

                    <!-- Configuration Guide -->
                    <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Configuration Guide</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                            <div>
                                <h4 class="font-semibold text-blue-800 mb-1">Weekly</h4>
                                <p>Employees are paid every week (typically Friday) for work performed Monday-Sunday</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-green-800 mb-1">Semi-Monthly</h4>
                                <p>Employees are paid twice per month (typically 15th and 30th) for periods 1-15 and 16-end of month</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-purple-800 mb-1">Monthly</h4>
                                <p>Employees are paid once per month (typically last day) for the entire month</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
