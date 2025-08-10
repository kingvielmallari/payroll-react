<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Payroll: {{ $payroll->payroll_number }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.show', $payroll) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Cancel
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('payrolls.update', $payroll) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Payroll Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payroll Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="payroll_type" class="block text-sm font-medium text-gray-700">Payroll Type</label>
                                <select name="payroll_type" id="payroll_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="regular" {{ $payroll->payroll_type == 'regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="bonus" {{ $payroll->payroll_type == 'bonus' ? 'selected' : '' }}>Bonus</option>
                                    <option value="adjustment" {{ $payroll->payroll_type == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                                </select>
                                @error('payroll_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="pay_date" class="block text-sm font-medium text-gray-700">Pay Date</label>
                                <input type="date" name="pay_date" id="pay_date" value="{{ $payroll->pay_date->format('Y-m-d') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('pay_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" id="description" rows="3" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="Optional description for this payroll">{{ $payroll->description }}</textarea>
                                @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Payroll Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Payroll Details</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Days Worked
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            OT Hours
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Allowances
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Bonuses
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Other Deductions
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Net Pay
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payroll->payrollDetails as $detail)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $detail->employee->first_name }} {{ $detail->employee->last_name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ $detail->employee->employee_number }} - {{ $detail->employee->position->title ?? 'No Position' }}
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        Basic: ₱{{ number_format($detail->employee->basic_salary, 2) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="payroll_details[{{ $detail->id }}][employee_id]" value="{{ $detail->employee_id }}">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" step="0.5" min="0" max="31" 
                                                   name="payroll_details[{{ $detail->id }}][days_worked]"
                                                   value="{{ $detail->days_worked }}"
                                                   class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                   data-employee-id="{{ $detail->employee_id }}"
                                                   onchange="calculatePayroll({{ $detail->employee_id }})">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" step="0.25" min="0" max="200" 
                                                   name="payroll_details[{{ $detail->id }}][overtime_hours]"
                                                   value="{{ $detail->overtime_hours }}"
                                                   class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                   data-employee-id="{{ $detail->employee_id }}"
                                                   onchange="calculatePayroll({{ $detail->employee_id }})">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" step="0.01" min="0" 
                                                   name="payroll_details[{{ $detail->id }}][allowances]"
                                                   value="{{ $detail->allowances }}"
                                                   class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                   data-employee-id="{{ $detail->employee_id }}"
                                                   onchange="calculatePayroll({{ $detail->employee_id }})">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" step="0.01" min="0" 
                                                   name="payroll_details[{{ $detail->id }}][bonuses]"
                                                   value="{{ $detail->bonuses }}"
                                                   class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                   data-employee-id="{{ $detail->employee_id }}"
                                                   onchange="calculatePayroll({{ $detail->employee_id }})">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" step="0.01" min="0" 
                                                   name="payroll_details[{{ $detail->id }}][other_deductions]"
                                                   value="{{ $detail->other_deductions }}"
                                                   class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                   data-employee-id="{{ $detail->employee_id }}"
                                                   onchange="calculatePayroll({{ $detail->employee_id }})">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-blue-600" id="net-pay-{{ $detail->employee_id }}">
                                                ₱{{ number_format($detail->net_pay, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Gross: <span id="gross-pay-{{ $detail->employee_id }}">₱{{ number_format($detail->gross_pay, 2) }}</span><br>
                                                Deductions: <span id="total-deductions-{{ $detail->employee_id }}">₱{{ number_format($detail->total_deductions, 2) }}</span>
                                            </div>
                                            
                                            <!-- Hidden fields for calculated values -->
                                            <input type="hidden" name="payroll_details[{{ $detail->id }}][regular_pay]" id="regular-pay-{{ $detail->employee_id }}" value="{{ $detail->regular_pay }}">
                                            <input type="hidden" name="payroll_details[{{ $detail->id }}][overtime_pay]" id="overtime-pay-{{ $detail->employee_id }}" value="{{ $detail->overtime_pay }}">
                                            <input type="hidden" name="payroll_details[{{ $detail->id }}][gross_pay]" id="gross-pay-hidden-{{ $detail->employee_id }}" value="{{ $detail->gross_pay }}">
                                            <input type="hidden" name="payroll_details[{{ $detail->id }}][total_deductions]" id="total-deductions-hidden-{{ $detail->employee_id }}" value="{{ $detail->total_deductions }}">
                                            <input type="hidden" name="payroll_details[{{ $detail->id }}][net_pay]" id="net-pay-hidden-{{ $detail->employee_id }}" value="{{ $detail->net_pay }}">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('payrolls.show', $payroll) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Update Payroll
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Store employee data for JavaScript calculations -->
    <script>
        const employees = @json($payroll->payrollDetails->map(function($detail) {
            return array(
                'id' => $detail->employee_id,
                'basic_salary' => $detail->employee->basic_salary,
                'hourly_rate' => $detail->employee->basic_salary / 22 / 8, // Daily salary / 8 hours
            );
        })->keyBy('id')->toArray());

        function calculatePayroll(employeeId) {
            const employee = employees[employeeId];
            if (!employee) return;

            // Get input values
            const daysWorked = parseFloat(document.querySelector(`input[name*="[days_worked]"][data-employee-id="${employeeId}"]`).value) || 0;
            const overtimeHours = parseFloat(document.querySelector(`input[name*="[overtime_hours]"][data-employee-id="${employeeId}"]`).value) || 0;
            const allowances = parseFloat(document.querySelector(`input[name*="[allowances]"][data-employee-id="${employeeId}"]`).value) || 0;
            const bonuses = parseFloat(document.querySelector(`input[name*="[bonuses]"][data-employee-id="${employeeId}"]`).value) || 0;
            const otherDeductions = parseFloat(document.querySelector(`input[name*="[other_deductions]"][data-employee-id="${employeeId}"]`).value) || 0;

            // Calculate regular pay (proportional to days worked, assuming 22 working days per month)
            const regularPay = (employee.basic_salary / 22) * daysWorked;
            
            // Calculate overtime pay (1.5x hourly rate)
            const overtimePay = employee.hourly_rate * 1.5 * overtimeHours;
            
            // Calculate gross pay
            const grossPay = regularPay + overtimePay + allowances + bonuses;
            
            // Calculate government contributions (simplified)
            const sssContribution = grossPay * 0.045; // 4.5% SSS
            const philHealthContribution = grossPay * 0.025; // 2.5% PhilHealth
            const pagibigContribution = Math.min(grossPay * 0.02, 100); // 2% Pag-IBIG, max 100
            
            // Calculate withholding tax (simplified calculation)
            let withholdingTax = 0;
            const monthlyTaxableIncome = grossPay - sssContribution - philHealthContribution - pagibigContribution;
            if (monthlyTaxableIncome > 20833) {
                withholdingTax = (monthlyTaxableIncome - 20833) * 0.2;
            }
            
            const totalDeductions = sssContribution + philHealthContribution + pagibigContribution + withholdingTax + otherDeductions;
            const netPay = grossPay - totalDeductions;

            // Update display
            document.getElementById(`gross-pay-${employeeId}`).textContent = `₱${grossPay.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById(`total-deductions-${employeeId}`).textContent = `₱${totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById(`net-pay-${employeeId}`).textContent = `₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            // Update hidden fields
            document.getElementById(`regular-pay-${employeeId}`).value = regularPay.toFixed(2);
            document.getElementById(`overtime-pay-${employeeId}`).value = overtimePay.toFixed(2);
            document.getElementById(`gross-pay-hidden-${employeeId}`).value = grossPay.toFixed(2);
            document.getElementById(`total-deductions-hidden-${employeeId}`).value = totalDeductions.toFixed(2);
            document.getElementById(`net-pay-hidden-${employeeId}`).value = netPay.toFixed(2);

            // Update totals
            updateTotals();
        }

        function updateTotals() {
            let totalGross = 0;
            let totalDeductions = 0;
            let totalNet = 0;

            Object.keys(employees).forEach(employeeId => {
                const gross = parseFloat(document.getElementById(`gross-pay-hidden-${employeeId}`).value) || 0;
                const deductions = parseFloat(document.getElementById(`total-deductions-hidden-${employeeId}`).value) || 0;
                const net = parseFloat(document.getElementById(`net-pay-hidden-${employeeId}`).value) || 0;

                totalGross += gross;
                totalDeductions += deductions;
                totalNet += net;
            });

            document.getElementById('total-gross-display').textContent = `₱${totalGross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('total-deductions-display').textContent = `₱${totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('total-net-display').textContent = `₱${totalNet.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
    </script>
</x-app-layout>
