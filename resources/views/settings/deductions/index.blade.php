<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Deduction/Tax Settings</h1>
            <a href="{{ route('settings.deductions.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Add New Deduction
            </a>
        </div>

    @if(session('success'))
        <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @forelse($deductions as $type => $typeDeductions)
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="bg-gray-50 px-6 py-3">
                <h3 class="text-lg font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $type) }}</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>                     
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deduct</th>
                         <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($typeDeductions as $deduction)
                        <tr class="hover:bg-gray-50 cursor-pointer {{ !$deduction->is_active ? 'opacity-50 bg-gray-50' : '' }}" 
                            data-context-menu
                            oncontextmenu="showDeductionContextMenu(event, {{ $deduction->id }}, {{ json_encode($deduction->name) }}, {{ json_encode($type) }}, {{ $deduction->is_active ? 'true' : 'false' }})">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ !$deduction->is_active ? 'text-gray-400' : 'text-gray-900' }}">{{ $deduction->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm {{ !$deduction->is_active ? 'text-gray-400' : 'text-gray-500' }}">{{ $deduction->description ?: 'No description' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ !$deduction->is_active ? 'text-gray-400' : 'text-gray-900' }}">
                                    @if($deduction->calculation_type === 'sss_table' || ($deduction->calculation_type === 'bracket' && $deduction->tax_table_type === 'sss'))
                                        <span class="text-indigo-600 font-medium">SSS Table</span>
                                    @elseif($deduction->calculation_type === 'philhealth_table' || ($deduction->calculation_type === 'bracket' && $deduction->tax_table_type === 'philhealth'))
                                        <span class="text-indigo-600 font-medium">PhilHealth Table</span>
                                    @elseif($deduction->calculation_type === 'pagibig_table' || ($deduction->calculation_type === 'bracket' && $deduction->tax_table_type === 'pagibig'))
                                        <span class="text-indigo-600 font-medium">Pag-IBIG Table</span>
                                    @elseif($deduction->calculation_type === 'withholding_tax_table' || ($deduction->calculation_type === 'bracket' && $deduction->tax_table_type === 'withholding_tax'))
                                        <span class="text-indigo-600 font-medium">BIR Tax Table</span>
                                    @elseif($deduction->calculation_type === 'percentage')
                                        <span class="text-indigo-600 font-medium">Percentage</span>
                                    @elseif($deduction->calculation_type === 'fixed_amount')
                                        <span class="text-indigo-600 font-medium">Fixed Amount</span>
                                    @elseif($deduction->calculation_type === 'bracket')
                                        <span class="text-indigo-600 font-medium">Custom Bracket</span>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </div>
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ !$deduction->is_active ? 'text-gray-400' : 'text-gray-900' }}">
                                    @php
                                        $payBasis = [];
                                        if($deduction->apply_to_basic_pay) $payBasis[] = 'Basic Pay';
                                        if($deduction->apply_to_gross_pay) $payBasis[] = 'Gross Pay';
                                        if($deduction->apply_to_taxable_income) $payBasis[] = 'Taxable Income';
                                        if($deduction->apply_to_net_pay) $payBasis[] = 'Net Pay';
                                        echo !empty($payBasis) ? implode(', ', $payBasis) : 'N/A';
                                    @endphp
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ !$deduction->is_active ? 'text-gray-400' : 'text-gray-900' }}">
                                    @if($deduction->calculation_type === 'percentage' && $deduction->rate_percentage)
                                        {{ $deduction->rate_percentage }}%
                                    @elseif($deduction->calculation_type === 'fixed_amount' && $deduction->fixed_amount)
                                        ₱{{ number_format($deduction->fixed_amount, 2) }}
                                    @elseif(in_array($deduction->calculation_type, ['sss_table', 'philhealth_table', 'pagibig_table', 'withholding_tax_table']) || 
                                            ($deduction->calculation_type === 'bracket' && in_array($deduction->tax_table_type, ['sss', 'philhealth', 'pagibig', 'withholding_tax'])))
                                        <div class="flex items-center space-x-2">
                                            <span class="text-gray-500 italic">Table-based</span>
                                            @if($deduction->share_with_employer && in_array($deduction->tax_table_type, ['sss', 'philhealth', 'pagibig']))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    50% Share
                                                </span>
                                            @endif
                                        </div>
                                    @elseif($deduction->calculation_type === 'bracket')
                                        <span class="text-gray-500 italic">Bracket-based</span>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </div>
                            </td>
                           
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $deduction->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $deduction->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-500">No deductions configured.</p>
        </div>
    @endforelse
</div>

<script src="{{ asset('js/settings-context-menu.js') }}"></script>
<script>
// Auto-hide success/error messages after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s ease-out';
            successMessage.style.opacity = '0';
            setTimeout(() => successMessage.remove(), 500);
        }, 3000);
    }
    
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.transition = 'opacity 0.5s ease-out';
            errorMessage.style.opacity = '0';
            setTimeout(() => errorMessage.remove(), 500);
        }, 3000);
    }
});

function showDeductionContextMenu(event, deductionId, deductionName, deductionType, isActive) {
    const config = {
        id: deductionId,
        name: deductionName,
        subtitle: deductionType.replace('_', ' ').toUpperCase(),
        viewText: 'View Deduction',
        editText: 'Edit Deduction',
        deleteText: 'Delete Deduction',
        viewUrl: `{{ route('settings.deductions.index') }}/${deductionId}`,
        editUrl: `{{ route('settings.deductions.index') }}/${deductionId}/edit`,
        toggleUrl: `{{ route('settings.deductions.index') }}/${deductionId}/toggle`,
        deleteUrl: `{{ route('settings.deductions.index') }}/${deductionId}`,
        isActive: isActive,
        canDelete: true,
        deleteConfirmMessage: 'Are you sure you want to delete this deduction?'
    };
    
    showSettingsContextMenu(event, config);
}
</script>
    </div>
</div>
</x-app-layout>
