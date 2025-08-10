<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dynamic Payroll Test - Deduction & Allowance Settings
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Active Deduction Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Active Deduction Settings</h3>
                    
                    @if($deductionSettings->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculation</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate/Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($deductionSettings as $setting)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $setting->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <code class="bg-gray-100 px-2 py-1 rounded">{{ $setting->code }}</code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $setting->type === 'government' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                                {{ ucfirst($setting->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ucfirst(str_replace('_', ' ', $setting->calculation_type)) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($setting->calculation_type === 'percentage')
                                                {{ $setting->rate_percentage }}%
                                            @elseif($setting->calculation_type === 'fixed_amount')
                                                ₱{{ number_format($setting->fixed_amount, 2) }}
                                            @else
                                                Dynamic
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <div class="text-gray-500">No active deduction settings found.</div>
                            <div class="text-sm text-gray-400 mt-2">Configure deduction settings in the admin panel.</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Active Allowance/Bonus Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Active Allowance & Bonus Settings</h3>
                    
                    @if($allowanceSettings->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculation</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate/Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxable</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($allowanceSettings as $setting)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $setting->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <code class="bg-gray-100 px-2 py-1 rounded">{{ $setting->code }}</code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $setting->type === 'allowance' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                                {{ ucfirst($setting->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ucfirst(str_replace('_', ' ', $setting->calculation_type)) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($setting->calculation_type === 'percentage')
                                                {{ $setting->rate_percentage }}%
                                            @elseif($setting->calculation_type === 'fixed_amount')
                                                ₱{{ number_format($setting->fixed_amount, 2) }}
                                            @elseif($setting->calculation_type === 'daily_rate_multiplier')
                                                {{ $setting->multiplier }}x daily rate
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ucfirst(str_replace('_', ' ', $setting->frequency)) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $setting->is_taxable ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $setting->is_taxable ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <div class="text-gray-500">No active allowance/bonus settings found.</div>
                            <div class="text-sm text-gray-400 mt-2">Configure allowance and bonus settings in the admin panel.</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Test Calculation -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Test Payroll Calculation</h3>
                    
                    <form id="testCalculation" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Basic Salary</label>
                                <input type="number" id="basicSalary" value="30000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Days Worked</label>
                                <input type="number" id="daysWorked" value="22" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hours Worked</label>
                                <input type="number" id="hoursWorked" value="176" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <button type="button" onclick="calculatePayroll()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Calculate Test Payroll
                        </button>
                    </form>

                    <div id="calculationResult" class="mt-6 hidden">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Calculation Result</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div id="resultContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculatePayroll() {
            const basicSalary = document.getElementById('basicSalary').value;
            const daysWorked = document.getElementById('daysWorked').value;
            const hoursWorked = document.getElementById('hoursWorked').value;
            
            // Simple client-side calculation for demo
            // In real implementation, this would call the server
            
            let result = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h5 class="font-medium text-green-700">Earnings</h5>
                        <div class="space-y-1 text-sm">
                            <div>Basic Salary: ₱${Number(basicSalary).toLocaleString()}</div>
                            <div>Allowances: (Dynamic from settings)</div>
                            <div>Bonuses: (Dynamic from settings)</div>
                        </div>
                    </div>
                    <div>
                        <h5 class="font-medium text-red-700">Deductions</h5>
                        <div class="space-y-1 text-sm">
                            <div>SSS: (Dynamic from settings)</div>
                            <div>PhilHealth: (Dynamic from settings)</div>
                            <div>Pag-IBIG: (Dynamic from settings)</div>
                            <div>Withholding Tax: (Dynamic from settings)</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <div class="text-sm text-gray-600">
                        <strong>Note:</strong> This is a preview. Actual calculations use dynamic settings configured in the admin panel.
                        The payroll system will automatically apply the correct rates and amounts based on current deduction and allowance settings.
                    </div>
                </div>
            `;
            
            document.getElementById('resultContent').innerHTML = result;
            document.getElementById('calculationResult').classList.remove('hidden');
        }
    </script>
</x-app-layout>
