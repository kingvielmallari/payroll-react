<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Deduction/Tax Setting</h1>
            <a href="{{ route('settings.deductions.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Deductions
            </a>
        </div>

    @if(!$deduction->is_active)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm">This deduction setting is currently inactive.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.deductions.update', $deduction) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Deduction Name</label>
                    <input type="text" name="name" id="name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('name', $deduction->name) }}" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $deduction->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Deduction Type</label>
                    <select name="type" id="type" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Type</option>
                        <option value="government" {{ old('type', $deduction->type) == 'government' ? 'selected' : '' }}>Government</option>
                        <option value="loan" {{ old('type', $deduction->type) == 'loan' ? 'selected' : '' }}>Loan</option>
                        <option value="custom" {{ old('type', $deduction->type) == 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" id="category" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Category</option>
                        <option value="mandatory" {{ old('category', $deduction->category) == 'mandatory' ? 'selected' : '' }}>Mandatory</option>
                        <option value="voluntary" {{ old('category', $deduction->category) == 'voluntary' ? 'selected' : '' }}>Voluntary</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="calculation_type" class="block text-sm font-medium text-gray-700 mb-2">Calculation Type</label>
                <select name="calculation_type" id="calculation_type" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Select Calculation Type</option>
                    <option value="percentage" {{ old('calculation_type', $deduction->calculation_type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed_amount" {{ old('calculation_type', $deduction->calculation_type) == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount</option>
                    <option value="sss_table" data-deduction="sss" {{ old('calculation_type', $deduction->calculation_type) == 'bracket' && old('tax_table_type', $deduction->tax_table_type) == 'sss' ? 'selected' : '' }}>SSS Table</option>
                    <option value="philhealth_table" data-deduction="philhealth" {{ old('calculation_type', $deduction->calculation_type) == 'bracket' && old('tax_table_type', $deduction->tax_table_type) == 'philhealth' ? 'selected' : '' }}>PhilHealth Table</option>
                    <option value="pagibig_table" data-deduction="pagibig" {{ old('calculation_type', $deduction->calculation_type) == 'bracket' && old('tax_table_type', $deduction->tax_table_type) == 'pagibig' ? 'selected' : '' }}>Pag-IBIG Table</option>
                    <option value="withholding_tax_table" data-deduction="withholding_tax" {{ old('calculation_type', $deduction->calculation_type) == 'bracket' && old('tax_table_type', $deduction->tax_table_type) == 'withholding_tax' ? 'selected' : '' }}>Withholding Tax Table</option>
                </select>
                @error('calculation_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                
                <!-- Hidden field to store current deduction info for JavaScript -->
                <input type="hidden" id="current_deduction_name" value="{{ strtolower($deduction->name) }}">
                <input type="hidden" id="current_tax_table_type" value="{{ $deduction->tax_table_type }}">
            </div>

            <!-- Hidden field to store the actual tax table type -->
            <input type="hidden" name="tax_table_type" id="hidden_tax_table_type" value="{{ old('tax_table_type', $deduction->tax_table_type) }}">

            <!-- View Tax Table Button (shown for table types) -->
            <div class="mt-4" id="view_table_section" style="display: none;">
                <button type="button" id="view_tax_table_btn" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View Tax Table Guide
                </button>
            </div>

            <!-- Share with Employer Section -->
            <div class="mt-6" id="share_employer_section" style="display: none;">
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="share_with_employer" id="share_with_employer" value="1" 
                               {{ old('share_with_employer', $deduction->share_with_employer) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <label for="share_with_employer" class="ml-2 block text-sm font-medium text-blue-800">
                            Share with Employer
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-blue-700">
                        When checked, only the employee share will be deducted from salary. 
                        When unchecked, both employee and employer shares will be deducted from employee salary.
                    </p>
                    <div class="mt-2 text-xs text-blue-600">
                        <strong>Note:</strong> This applies to SSS, PhilHealth, and Pag-IBIG. Withholding Tax is never shared with employer.
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div id="percentage_field" style="display: none;">
                    <label for="rate_percentage" class="block text-sm font-medium text-gray-700 mb-2">Rate Percentage (%)</label>
                    <input type="number" name="rate_percentage" id="rate_percentage" step="0.01" min="0" max="100"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('rate_percentage', $deduction->rate_percentage) }}">
                    @error('rate_percentage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="fixed_amount_field" style="display: none;">
                    <label for="fixed_amount" class="block text-sm font-medium text-gray-700 mb-2">Fixed Amount</label>
                    <input type="number" name="fixed_amount" id="fixed_amount" step="0.01" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('fixed_amount', $deduction->fixed_amount) }}">
                    @error('fixed_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Pay Basis Section -->
            <div class="mt-6">
                <label for="pay_basis" class="block text-sm font-medium text-gray-700 mb-2">Pay Basis - Select where to deduct from:</label>
                <select name="pay_basis" id="pay_basis" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @php
                        $currentPayBasis = '';
                        if(old('pay_basis') || 
                           old('apply_to_basic_pay', $deduction->apply_to_basic_pay) || 
                           old('apply_to_gross_pay', $deduction->apply_to_gross_pay) ||
                           old('apply_to_taxable_income', $deduction->apply_to_taxable_income) ||
                           old('apply_to_net_pay', $deduction->apply_to_net_pay)) {
                            if(old('pay_basis')) {
                                $currentPayBasis = old('pay_basis');
                            } elseif(old('apply_to_basic_pay', $deduction->apply_to_basic_pay)) {
                                $currentPayBasis = 'basic_pay';
                            } elseif(old('apply_to_gross_pay', $deduction->apply_to_gross_pay)) {
                                $currentPayBasis = 'gross_pay';
                            } elseif(old('apply_to_taxable_income', $deduction->apply_to_taxable_income)) {
                                $currentPayBasis = 'taxable_income';
                            } elseif(old('apply_to_net_pay', $deduction->apply_to_net_pay)) {
                                $currentPayBasis = 'net_pay';
                            }
                        }
                    @endphp
                    
                    <option value="">Select Pay Basis</option>
                    <option value="basic_pay" {{ $currentPayBasis === 'basic_pay' ? 'selected' : '' }}>
                        Basic Pay - rate × hours/days worked
                    </option>
                    <option value="gross_pay" {{ $currentPayBasis === 'gross_pay' ? 'selected' : '' }}>
                        Gross Pay - basic + OT + holiday pay + allowances + bonus (taxable)
                    </option>
                    <option value="taxable_income" {{ $currentPayBasis === 'taxable_income' ? 'selected' : '' }}>
                        Taxable Income - gross pay - (SSS + PhilHealth + Pag-IBIG)
                    </option>
                    <option value="net_pay" {{ $currentPayBasis === 'net_pay' ? 'selected' : '' }}>
                        Net Pay - gross pay - all deductions
                    </option>
                </select>
                
                <div class="mt-2 text-sm text-gray-500">
                    <p><strong>Basic Pay:</strong> Used for leave calculations, 13th month pay, and compliance reporting</p>
                    <p><strong>Gross Pay:</strong> Used for BIR reporting and government contributions</p>
                    <p><strong>Taxable Income:</strong> Used for BIR withholding tax calculations</p>
                    <p><strong>Net Pay:</strong> Used for final take-home pay calculations (rarely used for deductions)</p>
                </div>
                
                @error('pay_basis')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                
                <!-- Hidden fields for backward compatibility -->
                <input type="hidden" name="apply_to_basic_pay" id="hidden_apply_to_basic_pay" value="0">
                <input type="hidden" name="apply_to_gross_pay" id="hidden_apply_to_gross_pay" value="0">
                <input type="hidden" name="apply_to_taxable_income" id="hidden_apply_to_taxable_income" value="0">
                <input type="hidden" name="apply_to_net_pay" id="hidden_apply_to_net_pay" value="0">
            </div>

            <div class="mt-6">
                <label for="benefit_eligibility" class="block text-sm font-medium text-gray-700 mb-2">Apply To</label>
                <select name="benefit_eligibility" id="benefit_eligibility" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="both" {{ old('benefit_eligibility', $deduction->benefit_eligibility ?? 'both') == 'both' ? 'selected' : '' }}>
                        Both (With Benefits & Without Benefits)
                    </option>
                    <option value="with_benefits" {{ old('benefit_eligibility', $deduction->benefit_eligibility ?? 'both') == 'with_benefits' ? 'selected' : '' }}>
                        Only Employees With Benefits
                    </option>
                    <option value="without_benefits" {{ old('benefit_eligibility', $deduction->benefit_eligibility ?? 'both') == 'without_benefits' ? 'selected' : '' }}>
                        Only Employees Without Benefits
                    </option>
                </select>
                @error('benefit_eligibility')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Choose which employees this deduction/tax setting applies to based on their benefit status.</p>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.deductions.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Update Deduction
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tax Table Modal -->
<div id="taxTableModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 1000;">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Tax Table Guide</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal()">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="max-h-96 overflow-y-auto">
                <!-- Content will be loaded here -->
            </div>
            <div class="mt-4 flex justify-end">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" onclick="closeModal()">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to filter calculation type options based on current deduction
function filterCalculationTypeOptions() {
    const calculationTypeSelect = document.getElementById('calculation_type');
    const currentDeductionName = document.getElementById('current_deduction_name').value.toLowerCase();
    const currentTaxTableType = document.getElementById('current_tax_table_type').value;
    
    // Get all options
    const allOptions = calculationTypeSelect.querySelectorAll('option');
    
    // Always show these basic options
    const alwaysVisible = ['', 'percentage', 'fixed_amount', 'bracket'];
    
    // Determine which specific table should be visible based on deduction
    let allowedTableType = null;
    
    if (currentDeductionName.includes('sss') || currentTaxTableType === 'sss') {
        allowedTableType = 'sss_table';
    } else if (currentDeductionName.includes('philhealth') || currentTaxTableType === 'philhealth') {
        allowedTableType = 'philhealth_table';
    } else if (currentDeductionName.includes('pagibig') || currentDeductionName.includes('pag-ibig') || currentTaxTableType === 'pagibig') {
        allowedTableType = 'pagibig_table';
    } else if (currentDeductionName.includes('withholding') || currentDeductionName.includes('tax') || currentTaxTableType === 'withholding_tax') {
        allowedTableType = 'withholding_tax_table';
    }
    
    // Show/hide options
    allOptions.forEach(option => {
        const optionValue = option.value;
        const isTableOption = optionValue.includes('_table');
        
        if (alwaysVisible.includes(optionValue)) {
            // Always show basic options
            option.style.display = '';
        } else if (isTableOption && optionValue === allowedTableType) {
            // Show only the relevant tax table
            option.style.display = '';
        } else if (isTableOption) {
            // Hide other tax tables
            option.style.display = 'none';
        } else {
            // Show other options
            option.style.display = '';
        }
    });
}

// Call the filter function on page load
document.addEventListener('DOMContentLoaded', function() {
    filterCalculationTypeOptions();
    
    // Setup pay basis select dropdown handler
    const payBasisSelect = document.getElementById('pay_basis');
    payBasisSelect.addEventListener('change', function() {
        updatePayBasisHiddenFields(this.value);
    });
    
    // Initialize hidden fields based on current selection
    if (payBasisSelect.value) {
        updatePayBasisHiddenFields(payBasisSelect.value);
    }
    
    // Setup form submission handler
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        // Debug: Log the values being submitted
        const calculationType = document.getElementById('calculation_type');
        console.log('Submitting calculation_type:', calculationType.value);
        console.log('Tax table type:', document.getElementById('hidden_tax_table_type').value);
    });
});

function updatePayBasisHiddenFields(selectedValue) {
    // Reset all hidden fields
    document.getElementById('hidden_apply_to_basic_pay').value = '0';
    document.getElementById('hidden_apply_to_gross_pay').value = '0';
    document.getElementById('hidden_apply_to_taxable_income').value = '0';
    document.getElementById('hidden_apply_to_net_pay').value = '0';
    
    // Set the selected one to 1
    switch(selectedValue) {
        case 'basic_pay':
            document.getElementById('hidden_apply_to_basic_pay').value = '1';
            break;
        case 'gross_pay':
            document.getElementById('hidden_apply_to_gross_pay').value = '1';
            break;
        case 'taxable_income':
            document.getElementById('hidden_apply_to_taxable_income').value = '1';
            break;
        case 'net_pay':
            document.getElementById('hidden_apply_to_net_pay').value = '1';
            break;
    }
}

document.getElementById('calculation_type').addEventListener('change', function() {
    const value = this.value;
    const percentageField = document.getElementById('percentage_field');
    const fixedAmountField = document.getElementById('fixed_amount_field');
    const viewTableSection = document.getElementById('view_table_section');
    const shareEmployerSection = document.getElementById('share_employer_section');
    const hiddenTaxTableType = document.getElementById('hidden_tax_table_type');
    
    // Hide all fields first
    percentageField.style.display = 'none';
    fixedAmountField.style.display = 'none';
    viewTableSection.style.display = 'none';
    shareEmployerSection.style.display = 'none';
    
    // Show relevant field and set tax table type
    if (value === 'percentage') {
        percentageField.style.display = 'block';
        hiddenTaxTableType.value = '';
    } else if (value === 'fixed_amount') {
        fixedAmountField.style.display = 'block';
        hiddenTaxTableType.value = '';
    } else if (value === 'sss_table') {
        viewTableSection.style.display = 'block';
        shareEmployerSection.style.display = 'block';
        hiddenTaxTableType.value = 'sss';
        // Update the actual calculation_type for form submission
        this.setAttribute('data-actual-type', 'bracket');
    } else if (value === 'philhealth_table') {
        viewTableSection.style.display = 'block';
        shareEmployerSection.style.display = 'block';
        hiddenTaxTableType.value = 'philhealth';
        this.setAttribute('data-actual-type', 'bracket');
    } else if (value === 'pagibig_table') {
        viewTableSection.style.display = 'block';
        shareEmployerSection.style.display = 'block';
        hiddenTaxTableType.value = 'pagibig';
        this.setAttribute('data-actual-type', 'bracket');
    } else if (value === 'withholding_tax_table') {
        viewTableSection.style.display = 'block';
        // Note: No share_employer_section for withholding tax
        hiddenTaxTableType.value = 'withholding_tax';
        this.setAttribute('data-actual-type', 'bracket');
    }
});

// View Tax Table button
document.getElementById('view_tax_table_btn').addEventListener('click', function() {
    const calculationType = document.getElementById('calculation_type').value;
    let taxTableType = '';
    
    // Determine tax table type from calculation type
    if (calculationType === 'sss_table') taxTableType = 'sss';
    else if (calculationType === 'philhealth_table') taxTableType = 'philhealth';
    else if (calculationType === 'pagibig_table') taxTableType = 'pagibig';
    else if (calculationType === 'withholding_tax_table') taxTableType = 'withholding_tax';
    
    if (!taxTableType) {
        alert('Please select a tax table calculation type first.');
        return;
    }
    showTaxTableModal(taxTableType);
});

function showTaxTableModal(tableType) {
    const modal = document.getElementById('taxTableModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    let title = '';
    let content = '';
    
    switch(tableType) {
        case 'sss':
            title = 'SSS Contribution Table 2025';
            content = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Salary Range</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">MSC</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">EE Share</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ER Share</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr><td class="px-2 py-1 text-xs">4,250 - 4,749.99</td><td class="px-2 py-1 text-xs">4,500.00</td><td class="px-2 py-1 text-xs">202.50</td><td class="px-2 py-1 text-xs">427.50</td><td class="px-2 py-1 text-xs">630.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">4,750 - 5,249.99</td><td class="px-2 py-1 text-xs">5,000.00</td><td class="px-2 py-1 text-xs">225.00</td><td class="px-2 py-1 text-xs">475.00</td><td class="px-2 py-1 text-xs">700.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">5,250 - 5,749.99</td><td class="px-2 py-1 text-xs">5,500.00</td><td class="px-2 py-1 text-xs">247.50</td><td class="px-2 py-1 text-xs">522.50</td><td class="px-2 py-1 text-xs">770.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">5,750 - 6,249.99</td><td class="px-2 py-1 text-xs">6,000.00</td><td class="px-2 py-1 text-xs">270.00</td><td class="px-2 py-1 text-xs">570.00</td><td class="px-2 py-1 text-xs">840.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">6,250 - 6,749.99</td><td class="px-2 py-1 text-xs">6,500.00</td><td class="px-2 py-1 text-xs">292.50</td><td class="px-2 py-1 text-xs">617.50</td><td class="px-2 py-1 text-xs">910.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">6,750 - 7,249.99</td><td class="px-2 py-1 text-xs">7,000.00</td><td class="px-2 py-1 text-xs">315.00</td><td class="px-2 py-1 text-xs">665.00</td><td class="px-2 py-1 text-xs">980.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">7,250 - 7,749.99</td><td class="px-2 py-1 text-xs">7,500.00</td><td class="px-2 py-1 text-xs">337.50</td><td class="px-2 py-1 text-xs">712.50</td><td class="px-2 py-1 text-xs">1,050.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">7,750 - 8,249.99</td><td class="px-2 py-1 text-xs">8,000.00</td><td class="px-2 py-1 text-xs">360.00</td><td class="px-2 py-1 text-xs">760.00</td><td class="px-2 py-1 text-xs">1,120.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">8,250 - 8,749.99</td><td class="px-2 py-1 text-xs">8,500.00</td><td class="px-2 py-1 text-xs">382.50</td><td class="px-2 py-1 text-xs">807.50</td><td class="px-2 py-1 text-xs">1,190.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">8,750 - 9,249.99</td><td class="px-2 py-1 text-xs">9,000.00</td><td class="px-2 py-1 text-xs">405.00</td><td class="px-2 py-1 text-xs">855.00</td><td class="px-2 py-1 text-xs">1,260.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">9,250 - 9,749.99</td><td class="px-2 py-1 text-xs">9,500.00</td><td class="px-2 py-1 text-xs">427.50</td><td class="px-2 py-1 text-xs">902.50</td><td class="px-2 py-1 text-xs">1,330.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">9,750 - 10,249.99</td><td class="px-2 py-1 text-xs">10,000.00</td><td class="px-2 py-1 text-xs">450.00</td><td class="px-2 py-1 text-xs">950.00</td><td class="px-2 py-1 text-xs">1,400.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">10,250 - 10,749.99</td><td class="px-2 py-1 text-xs">10,500.00</td><td class="px-2 py-1 text-xs">472.50</td><td class="px-2 py-1 text-xs">997.50</td><td class="px-2 py-1 text-xs">1,470.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">10,750 - 11,249.99</td><td class="px-2 py-1 text-xs">11,000.00</td><td class="px-2 py-1 text-xs">495.00</td><td class="px-2 py-1 text-xs">1,045.00</td><td class="px-2 py-1 text-xs">1,540.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">11,250 - 11,749.99</td><td class="px-2 py-1 text-xs">11,500.00</td><td class="px-2 py-1 text-xs">517.50</td><td class="px-2 py-1 text-xs">1,092.50</td><td class="px-2 py-1 text-xs">1,610.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">11,750 - 12,249.99</td><td class="px-2 py-1 text-xs">12,000.00</td><td class="px-2 py-1 text-xs">540.00</td><td class="px-2 py-1 text-xs">1,140.00</td><td class="px-2 py-1 text-xs">1,680.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">12,250 - 12,749.99</td><td class="px-2 py-1 text-xs">12,500.00</td><td class="px-2 py-1 text-xs">562.50</td><td class="px-2 py-1 text-xs">1,187.50</td><td class="px-2 py-1 text-xs">1,750.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">12,750 - 13,249.99</td><td class="px-2 py-1 text-xs">13,000.00</td><td class="px-2 py-1 text-xs">585.00</td><td class="px-2 py-1 text-xs">1,235.00</td><td class="px-2 py-1 text-xs">1,820.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">13,250 - 13,749.99</td><td class="px-2 py-1 text-xs">13,500.00</td><td class="px-2 py-1 text-xs">607.50</td><td class="px-2 py-1 text-xs">1,282.50</td><td class="px-2 py-1 text-xs">1,890.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">13,750 - 14,249.99</td><td class="px-2 py-1 text-xs">14,000.00</td><td class="px-2 py-1 text-xs">630.00</td><td class="px-2 py-1 text-xs">1,330.00</td><td class="px-2 py-1 text-xs">1,960.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">14,250 - 14,749.99</td><td class="px-2 py-1 text-xs">14,500.00</td><td class="px-2 py-1 text-xs">652.50</td><td class="px-2 py-1 text-xs">1,377.50</td><td class="px-2 py-1 text-xs">2,030.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">14,750 - 15,249.99</td><td class="px-2 py-1 text-xs">15,000.00</td><td class="px-2 py-1 text-xs">675.00</td><td class="px-2 py-1 text-xs">1,425.00</td><td class="px-2 py-1 text-xs">2,100.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">15,250 - 15,749.99</td><td class="px-2 py-1 text-xs">15,500.00</td><td class="px-2 py-1 text-xs">697.50</td><td class="px-2 py-1 text-xs">1,472.50</td><td class="px-2 py-1 text-xs">2,170.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">15,750 - 16,249.99</td><td class="px-2 py-1 text-xs">16,000.00</td><td class="px-2 py-1 text-xs">720.00</td><td class="px-2 py-1 text-xs">1,520.00</td><td class="px-2 py-1 text-xs">2,240.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">16,250 - 16,749.99</td><td class="px-2 py-1 text-xs">16,500.00</td><td class="px-2 py-1 text-xs">742.50</td><td class="px-2 py-1 text-xs">1,567.50</td><td class="px-2 py-1 text-xs">2,310.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">16,750 - 17,249.99</td><td class="px-2 py-1 text-xs">17,000.00</td><td class="px-2 py-1 text-xs">765.00</td><td class="px-2 py-1 text-xs">1,615.00</td><td class="px-2 py-1 text-xs">2,380.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">17,250 - 17,749.99</td><td class="px-2 py-1 text-xs">17,500.00</td><td class="px-2 py-1 text-xs">787.50</td><td class="px-2 py-1 text-xs">1,662.50</td><td class="px-2 py-1 text-xs">2,450.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">17,750 - 18,249.99</td><td class="px-2 py-1 text-xs">18,000.00</td><td class="px-2 py-1 text-xs">810.00</td><td class="px-2 py-1 text-xs">1,710.00</td><td class="px-2 py-1 text-xs">2,520.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">18,250 - 18,749.99</td><td class="px-2 py-1 text-xs">18,500.00</td><td class="px-2 py-1 text-xs">832.50</td><td class="px-2 py-1 text-xs">1,757.50</td><td class="px-2 py-1 text-xs">2,590.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">18,750 - 19,249.99</td><td class="px-2 py-1 text-xs">19,000.00</td><td class="px-2 py-1 text-xs">855.00</td><td class="px-2 py-1 text-xs">1,805.00</td><td class="px-2 py-1 text-xs">2,660.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">19,250 - 19,749.99</td><td class="px-2 py-1 text-xs">19,500.00</td><td class="px-2 py-1 text-xs">877.50</td><td class="px-2 py-1 text-xs">1,852.50</td><td class="px-2 py-1 text-xs">2,730.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">19,750 - 20,249.99</td><td class="px-2 py-1 text-xs">20,000.00</td><td class="px-2 py-1 text-xs">900.00</td><td class="px-2 py-1 text-xs">1,900.00</td><td class="px-2 py-1 text-xs">2,800.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">20,250 - 20,749.99</td><td class="px-2 py-1 text-xs">20,500.00</td><td class="px-2 py-1 text-xs">922.50</td><td class="px-2 py-1 text-xs">1,947.50</td><td class="px-2 py-1 text-xs">2,870.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">20,750 - 21,249.99</td><td class="px-2 py-1 text-xs">21,000.00</td><td class="px-2 py-1 text-xs">945.00</td><td class="px-2 py-1 text-xs">1,995.00</td><td class="px-2 py-1 text-xs">2,940.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">21,250 - 21,749.99</td><td class="px-2 py-1 text-xs">21,500.00</td><td class="px-2 py-1 text-xs">967.50</td><td class="px-2 py-1 text-xs">2,042.50</td><td class="px-2 py-1 text-xs">3,010.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">21,750 - 22,249.99</td><td class="px-2 py-1 text-xs">22,000.00</td><td class="px-2 py-1 text-xs">990.00</td><td class="px-2 py-1 text-xs">2,090.00</td><td class="px-2 py-1 text-xs">3,080.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">22,250 - 22,749.99</td><td class="px-2 py-1 text-xs">22,500.00</td><td class="px-2 py-1 text-xs">1,012.50</td><td class="px-2 py-1 text-xs">2,137.50</td><td class="px-2 py-1 text-xs">3,150.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">22,750 - 23,249.99</td><td class="px-2 py-1 text-xs">23,000.00</td><td class="px-2 py-1 text-xs">1,035.00</td><td class="px-2 py-1 text-xs">2,185.00</td><td class="px-2 py-1 text-xs">3,220.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">23,250 - 23,749.99</td><td class="px-2 py-1 text-xs">23,500.00</td><td class="px-2 py-1 text-xs">1,057.50</td><td class="px-2 py-1 text-xs">2,232.50</td><td class="px-2 py-1 text-xs">3,290.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">23,750 - 24,249.99</td><td class="px-2 py-1 text-xs">24,000.00</td><td class="px-2 py-1 text-xs">1,080.00</td><td class="px-2 py-1 text-xs">2,280.00</td><td class="px-2 py-1 text-xs">3,360.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">24,250 - 24,749.99</td><td class="px-2 py-1 text-xs">24,500.00</td><td class="px-2 py-1 text-xs">1,102.50</td><td class="px-2 py-1 text-xs">2,327.50</td><td class="px-2 py-1 text-xs">3,430.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">24,750 - 25,249.99</td><td class="px-2 py-1 text-xs">25,000.00</td><td class="px-2 py-1 text-xs">1,125.00</td><td class="px-2 py-1 text-xs">2,375.00</td><td class="px-2 py-1 text-xs">3,500.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">25,250 - 25,749.99</td><td class="px-2 py-1 text-xs">25,500.00</td><td class="px-2 py-1 text-xs">1,147.50</td><td class="px-2 py-1 text-xs">2,422.50</td><td class="px-2 py-1 text-xs">3,570.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">25,750 - 26,249.99</td><td class="px-2 py-1 text-xs">26,000.00</td><td class="px-2 py-1 text-xs">1,170.00</td><td class="px-2 py-1 text-xs">2,470.00</td><td class="px-2 py-1 text-xs">3,640.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">26,250 - 26,749.99</td><td class="px-2 py-1 text-xs">26,500.00</td><td class="px-2 py-1 text-xs">1,192.50</td><td class="px-2 py-1 text-xs">2,517.50</td><td class="px-2 py-1 text-xs">3,710.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">26,750 - 27,249.99</td><td class="px-2 py-1 text-xs">27,000.00</td><td class="px-2 py-1 text-xs">1,215.00</td><td class="px-2 py-1 text-xs">2,565.00</td><td class="px-2 py-1 text-xs">3,780.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">27,250 - 27,749.99</td><td class="px-2 py-1 text-xs">27,500.00</td><td class="px-2 py-1 text-xs">1,237.50</td><td class="px-2 py-1 text-xs">2,612.50</td><td class="px-2 py-1 text-xs">3,850.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">27,750 - 28,249.99</td><td class="px-2 py-1 text-xs">28,000.00</td><td class="px-2 py-1 text-xs">1,260.00</td><td class="px-2 py-1 text-xs">2,660.00</td><td class="px-2 py-1 text-xs">3,920.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">28,250 - 28,749.99</td><td class="px-2 py-1 text-xs">28,500.00</td><td class="px-2 py-1 text-xs">1,282.50</td><td class="px-2 py-1 text-xs">2,707.50</td><td class="px-2 py-1 text-xs">3,990.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">28,750 - 29,249.99</td><td class="px-2 py-1 text-xs">29,000.00</td><td class="px-2 py-1 text-xs">1,305.00</td><td class="px-2 py-1 text-xs">2,755.00</td><td class="px-2 py-1 text-xs">4,060.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">29,250 - 29,749.99</td><td class="px-2 py-1 text-xs">29,500.00</td><td class="px-2 py-1 text-xs">1,327.50</td><td class="px-2 py-1 text-xs">2,802.50</td><td class="px-2 py-1 text-xs">4,130.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">29,750 - 30,249.99</td><td class="px-2 py-1 text-xs">30,000.00</td><td class="px-2 py-1 text-xs">1,350.00</td><td class="px-2 py-1 text-xs">2,850.00</td><td class="px-2 py-1 text-xs">4,200.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">30,250 - 30,749.99</td><td class="px-2 py-1 text-xs">30,500.00</td><td class="px-2 py-1 text-xs">1,372.50</td><td class="px-2 py-1 text-xs">2,897.50</td><td class="px-2 py-1 text-xs">4,270.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">30,750 - 31,249.99</td><td class="px-2 py-1 text-xs">31,000.00</td><td class="px-2 py-1 text-xs">1,395.00</td><td class="px-2 py-1 text-xs">2,945.00</td><td class="px-2 py-1 text-xs">4,340.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">31,250 - 31,749.99</td><td class="px-2 py-1 text-xs">31,500.00</td><td class="px-2 py-1 text-xs">1,417.50</td><td class="px-2 py-1 text-xs">2,992.50</td><td class="px-2 py-1 text-xs">4,410.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">31,750 - 32,249.99</td><td class="px-2 py-1 text-xs">32,000.00</td><td class="px-2 py-1 text-xs">1,440.00</td><td class="px-2 py-1 text-xs">3,040.00</td><td class="px-2 py-1 text-xs">4,480.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">32,250 - 32,749.99</td><td class="px-2 py-1 text-xs">32,500.00</td><td class="px-2 py-1 text-xs">1,462.50</td><td class="px-2 py-1 text-xs">3,087.50</td><td class="px-2 py-1 text-xs">4,550.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">32,750 - 33,249.99</td><td class="px-2 py-1 text-xs">33,000.00</td><td class="px-2 py-1 text-xs">1,485.00</td><td class="px-2 py-1 text-xs">3,135.00</td><td class="px-2 py-1 text-xs">4,620.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">33,250 - 33,749.99</td><td class="px-2 py-1 text-xs">33,500.00</td><td class="px-2 py-1 text-xs">1,507.50</td><td class="px-2 py-1 text-xs">3,182.50</td><td class="px-2 py-1 text-xs">4,690.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">33,750 - 34,249.99</td><td class="px-2 py-1 text-xs">34,000.00</td><td class="px-2 py-1 text-xs">1,530.00</td><td class="px-2 py-1 text-xs">3,230.00</td><td class="px-2 py-1 text-xs">4,760.00</td></tr>
                            <tr><td class="px-2 py-1 text-xs">34,750 - Over</td><td class="px-2 py-1 text-xs">35,000.00</td><td class="px-2 py-1 text-xs">1,575.00</td><td class="px-2 py-1 text-xs">3,325.00</td><td class="px-2 py-1 text-xs">4,900.00</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-4 p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-800"><strong>Note:</strong> MSC = Monthly Salary Credit, EE = Employee, ER = Employer</p>
                    </div>
                </div>
            `;
            break;
            
        case 'philhealth':
            title = 'PhilHealth Contribution Table 2024-2025';
            content = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Salary Range</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee Share</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employer Share</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr><td class="px-3 py-2">₱1,500 and below</td><td class="px-3 py-2">1%</td><td class="px-3 py-2">2%</td></tr>
                            <tr><td class="px-3 py-2">Over ₱1,500</td><td class="px-3 py-2">2%</td><td class="px-3 py-2">2%</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-4 p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-800"><strong>Note:</strong> Maximum salary cap is ₱60,000 per month</p>
                    </div>
                </div>
            `;
            break;
            
        case 'pagibig':
            title = 'Pag-IBIG Contribution Table 2024-2025';
            content = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Salary Range</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee Share</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employer Share</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr><td class="px-3 py-2">₱1,500 and below</td><td class="px-3 py-2">1%</td><td class="px-3 py-2">2%</td></tr>
                            <tr><td class="px-3 py-2">Over ₱1,500</td><td class="px-3 py-2">2%</td><td class="px-3 py-2">2%</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-4 p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-800"><strong>Note:</strong> Maximum contribution is ₱200.00 for employee and ₱200.00 for employer</p>
                    </div>
                </div>
            `;
            break;
            
        case 'withholding_tax':
            title = 'BIR Withholding Tax Table (Semi-Monthly) 2023 onwards';
            content = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Compensation Range</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tax Rate</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Formula</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr><td class="px-3 py-2">₱10,417 and below</td><td class="px-3 py-2">0%</td><td class="px-3 py-2">₱0.00</td></tr>
                            <tr><td class="px-3 py-2">₱10,417 - ₱16,666</td><td class="px-3 py-2">15%</td><td class="px-3 py-2">0.00 +15% over ₱10,417</td></tr>
                            <tr><td class="px-3 py-2">₱16,667 - ₱33,332</td><td class="px-3 py-2">20%</td><td class="px-3 py-2">₱937.50 +20% over ₱16,667</td></tr>
                            <tr><td class="px-3 py-2">₱33,333 - ₱83,332</td><td class="px-3 py-2">25%</td><td class="px-3 py-2">₱4,270.70 +25% over ₱33,333</td></tr>
                            <tr><td class="px-3 py-2">₱83,333 - ₱333,332</td><td class="px-3 py-2">30%</td><td class="px-3 py-2">₱16,770.70 +30% over ₱83,333</td></tr>
                            <tr><td class="px-3 py-2">₱333,333 and above</td><td class="px-3 py-2">35%</td><td class="px-3 py-2">₱91,770.70 +35% over ₱333,333</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-4 p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-800"><strong>Note:</strong> This applies to taxable income (gross pay minus SSS, PhilHealth, and Pag-IBIG contributions)</p>
                    </div>
                </div>
            `;
            break;
    }
    
    modalTitle.textContent = title;
    modalContent.innerHTML = content;
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('taxTableModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('taxTableModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Trigger on page load
document.getElementById('calculation_type').dispatchEvent(new Event('change'));
</script>
    </div>
</div>
</x-app-layout>
