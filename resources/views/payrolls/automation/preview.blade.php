<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Payroll Preview') }} - {{ $selectedSchedule->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Draft payroll calculations for {{ ucfirst(str_replace('_', ' ', $selectedSchedule->code)) }} schedule - Not yet saved to database
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.automation.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Schedules
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Period Info and Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Payroll Period Information</h3>
                            <p class="text-sm text-blue-600 mt-1">
                                <strong>Period:</strong> 
                                {{ \Carbon\Carbon::parse($currentPeriod['start'])->format('M d') }} - 
                                {{ \Carbon\Carbon::parse($currentPeriod['end'])->format('M d, Y') }}
                                (Pay Date: {{ \Carbon\Carbon::parse($currentPeriod['pay_date'])->format('M d, Y') }})
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                Employees: {{ count($payrollPreviews) }} | Total Gross: ₱{{ number_format($totalGross, 2) }} | Total Net: ₱{{ number_format($totalNet, 2) }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <form method="POST" action="{{ route('payrolls.automation.submit', $scheduleCode) }}" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" 
                                        onclick="return confirm('Submit these payrolls for processing? This will save them to the database with current settings snapshots.')">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Submit for Processing
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Preview List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Payroll Preview 
                            <span class="text-sm font-normal text-gray-500">
                                ({{ count($payrollPreviews) }} employees)
                            </span>
                        </h3>
                        <div class="text-sm bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full">
                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Draft Mode - Dynamic Calculations
                        </div>
                    </div>

                    @if (count($payrollPreviews) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Salary</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours/Days</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($payrollPreviews as $index => $preview)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $preview['employee']->first_name }} {{ $preview['employee']->last_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $preview['employee']->employee_id }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₱{{ number_format($preview['basic_salary'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($preview['hours_worked'], 1) }}h<br>
                                        <span class="text-xs text-gray-500">{{ $preview['days_worked'] }} days</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">₱{{ number_format($preview['gross_pay'], 2) }}</div>
                                        @if ($preview['allowances'] > 0 || $preview['bonuses'] > 0)
                                        <div class="text-xs text-gray-500">
                                            @if ($preview['allowances'] > 0) +₱{{ number_format($preview['allowances'], 2) }} allowances @endif
                                            @if ($preview['bonuses'] > 0) +₱{{ number_format($preview['bonuses'], 2) }} bonuses @endif
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₱{{ number_format($preview['total_deductions'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-green-600">
                                            ₱{{ number_format($preview['net_pay'], 2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button type="button" 
                                                onclick="toggleDetails('details-{{ $index }}')"
                                                class="text-blue-600 hover:text-blue-900 text-xs">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <tr id="details-{{ $index }}" class="hidden bg-gray-50">
                                    <td colspan="7" class="px-6 py-4">
                                        <div class="grid grid-cols-3 gap-4 text-xs">
                                            <!-- Earnings Breakdown -->
                                            <div>
                                                <h4 class="font-semibold text-gray-700 mb-2">Earnings</h4>
                                                <div class="space-y-1">
                                                    <div class="flex justify-between">
                                                        <span>Basic Pay:</span>
                                                        <span>₱{{ number_format($preview['gross_pay'] - $preview['allowances'] - $preview['bonuses'] - $preview['overtime_pay'], 2) }}</span>
                                                    </div>
                                                    @if ($preview['overtime_pay'] > 0)
                                                    <div class="flex justify-between">
                                                        <span>Overtime:</span>
                                                        <span>₱{{ number_format($preview['overtime_pay'], 2) }}</span>
                                                    </div>
                                                    @endif
                                                    @if (!empty($preview['allowances_breakdown']) && is_array($preview['allowances_breakdown']))
                                                        @foreach ($preview['allowances_breakdown'] as $allowance)
                                                        <div class="flex justify-between">
                                                            <span>{{ $allowance['name'] ?? 'Allowance' }}:</span>
                                                            <span>₱{{ number_format($allowance['amount'] ?? 0, 2) }}</span>
                                                        </div>
                                                        @endforeach
                                                    @endif
                                                    @if (!empty($preview['bonuses_breakdown']) && is_array($preview['bonuses_breakdown']))
                                                        @foreach ($preview['bonuses_breakdown'] as $bonus)
                                                        <div class="flex justify-between">
                                                            <span>{{ $bonus['name'] ?? 'Bonus' }}:</span>
                                                            <span>₱{{ number_format($bonus['amount'] ?? 0, 2) }}</span>
                                                        </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Deductions Breakdown -->
                                            <div>
                                                <h4 class="font-semibold text-gray-700 mb-2">Deductions</h4>
                                                <div class="space-y-1">
                                                    @if (!empty($preview['deductions_breakdown']) && is_array($preview['deductions_breakdown']))
                                                        @foreach ($preview['deductions_breakdown'] as $deduction)
                                                        <div class="flex justify-between">
                                                            <span>{{ $deduction['name'] ?? 'Deduction' }}:</span>
                                                            <span>₱{{ number_format($deduction['amount'] ?? 0, 2) }}</span>
                                                        </div>
                                                        @endforeach
                                                    @else
                                                        <div class="text-gray-500">No deductions</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Summary -->
                                            <div>
                                                <h4 class="font-semibold text-gray-700 mb-2">Summary</h4>
                                                <div class="space-y-1">
                                                    <div class="flex justify-between font-semibold">
                                                        <span>Gross Pay:</span>
                                                        <span>₱{{ number_format($preview['gross_pay'], 2) }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>Total Deductions:</span>
                                                        <span>₱{{ number_format($preview['total_deductions'], 2) }}</span>
                                                    </div>
                                                    <hr class="border-gray-300">
                                                    <div class="flex justify-between font-bold text-green-600">
                                                        <span>Net Pay:</span>
                                                        <span>₱{{ number_format($preview['net_pay'], 2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-left text-sm font-medium text-gray-900">
                                        TOTALS ({{ count($payrollPreviews) }} employees)
                                    </td>
                                    <td class="px-6 py-3 text-left text-sm font-bold text-gray-900">
                                        ₱{{ number_format($totalGross, 2) }}
                                    </td>
                                    <td class="px-6 py-3 text-left text-sm font-bold text-gray-900">
                                        ₱{{ number_format($totalDeductions, 2) }}
                                    </td>
                                    <td class="px-6 py-3 text-left text-sm font-bold text-green-600">
                                        ₱{{ number_format($totalNet, 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No active employees</h3>
                        <p class="mt-1 text-sm text-gray-500">No active employees found for this pay schedule.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Information Box -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="flex-shrink-0 h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Draft Mode Information</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Payroll calculations are <strong>dynamic</strong> and will update automatically when system settings change</li>
                                <li>Data is <strong>not saved</strong> to the database yet - this is just a preview</li>
                                <li>Click <strong>"Submit for Processing"</strong> to save payrolls with current calculation snapshots</li>
                                <li>Once submitted, payroll data will be fixed and won't change even if system settings are updated</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDetails(detailsId) {
            const detailsRow = document.getElementById(detailsId);
            if (detailsRow.classList.contains('hidden')) {
                detailsRow.classList.remove('hidden');
            } else {
                detailsRow.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
