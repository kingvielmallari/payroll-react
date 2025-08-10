<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Payslip Details
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $payrollDetail->employee->first_name }} {{ $payrollDetail->employee->last_name }} - 
                    {{ $payrollDetail->payroll->period_start->format('F d') }} to {{ $payrollDetail->payroll->period_end->format('F d, Y') }}
                </p>
            </div>
            <div class="flex space-x-2">
                @can('email payslips')
                <form method="POST" action="{{ route('payslips.email', $payrollDetail) }}" class="inline">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            onclick="return confirm('Send payslip to {{ $payrollDetail->employee->user->email ?? 'employee' }}?')">
                        Email Payslip
                    </button>
                </form>
                @endcan
                
                @can('download payslips')
                <a href="{{ route('payslips.download', $payrollDetail) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Download PDF
                </a>
                @endcan
                
                @can('view payrolls')
                <a href="{{ route('payrolls.show', $payrollDetail->payroll) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to Payroll
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Employee Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Employee Number</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->employee_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Employee Name</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->first_name }} {{ $payrollDetail->employee->last_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Department</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->department->name ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Position</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->position->title ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Payroll Number</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->payroll->payroll_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Pay Period</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->payroll->period_start->format('F d') }} - {{ $payrollDetail->payroll->period_end->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Pay Date</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->payroll->pay_date->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Basic Salary</dt>
                                    <dd class="text-sm text-gray-900">₱{{ number_format($payrollDetail->basic_salary, 2) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-green-50 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-2xl font-bold text-green-600">₱{{ number_format($payrollDetail->gross_pay, 2) }}</div>
                        <div class="text-sm text-green-800">Gross Pay</div>
                    </div>
                </div>
                
                <div class="bg-red-50 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-2xl font-bold text-red-600">₱{{ number_format($payrollDetail->total_deductions, 2) }}</div>
                        <div class="text-sm text-red-800">Total Deductions</div>
                    </div>
                </div>
                
                <div class="bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-2xl font-bold text-blue-600">₱{{ number_format($payrollDetail->net_pay, 2) }}</div>
                        <div class="text-sm text-blue-800">Net Pay</div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payroll Breakdown</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours/Days</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Earnings Section -->
                                <tr class="bg-green-50">
                                    <td colspan="3" class="px-6 py-3 text-sm font-bold text-green-800">EARNINGS</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Regular Pay</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ number_format($payrollDetail->regular_hours, 1) }} hrs</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->regular_pay, 2) }}</td>
                                </tr>
                                @if($payrollDetail->overtime_pay > 0)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Overtime Pay</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ number_format($payrollDetail->overtime_hours, 1) }} hrs</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->overtime_pay, 2) }}</td>
                                </tr>
                                @endif
                                @if($payrollDetail->holiday_pay > 0)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Holiday Pay</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->holiday_pay, 2) }}</td>
                                </tr>
                                @endif
                                @if($payrollDetail->allowances > 0)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Allowances</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->allowances, 2) }}</td>
                                </tr>
                                @endif
                                @if($payrollDetail->bonuses > 0)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Bonuses</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->bonuses, 2) }}</td>
                                </tr>
                                @endif
                                <tr class="bg-green-100 font-bold">
                                    <td class="px-6 py-4 text-sm text-green-800">TOTAL GROSS PAY</td>
                                    <td class="px-6 py-4 text-sm text-green-800">-</td>
                                    <td class="px-6 py-4 text-sm text-green-800 text-right">₱{{ number_format($payrollDetail->gross_pay, 2) }}</td>
                                </tr>

                                <!-- Deductions Section -->
                                <tr class="bg-red-50">
                                    <td colspan="3" class="px-6 py-3 text-sm font-bold text-red-800">DEDUCTIONS</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">SSS Contribution</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->sss_contribution, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">PhilHealth Contribution</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->philhealth_contribution, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Pag-IBIG Contribution</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->pagibig_contribution, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Withholding Tax</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->withholding_tax, 2) }}</td>
                                </tr>
                                @if($payrollDetail->other_deductions > 0)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">Other Deductions</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($payrollDetail->other_deductions, 2) }}</td>
                                </tr>
                                @endif
                                <tr class="bg-red-100 font-bold">
                                    <td class="px-6 py-4 text-sm text-red-800">TOTAL DEDUCTIONS</td>
                                    <td class="px-6 py-4 text-sm text-red-800">-</td>
                                    <td class="px-6 py-4 text-sm text-red-800 text-right">₱{{ number_format($payrollDetail->total_deductions, 2) }}</td>
                                </tr>

                                <!-- Net Pay -->
                                <tr class="bg-blue-100 font-bold text-lg">
                                    <td class="px-6 py-4 text-blue-800">NET PAY</td>
                                    <td class="px-6 py-4 text-blue-800">-</td>
                                    <td class="px-6 py-4 text-blue-800 text-right">₱{{ number_format($payrollDetail->net_pay, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Government Contributions -->
            <div class="bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Government Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">SSS Number</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->sss_number ?? 'Not Set' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">PhilHealth Number</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->philhealth_number ?? 'Not Set' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Pag-IBIG Number</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->pagibig_number ?? 'Not Set' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">TIN</dt>
                                    <dd class="text-sm text-gray-900">{{ $payrollDetail->employee->tin_number ?? 'Not Set' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
