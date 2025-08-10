<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Allowance & Bonus Settings</h1>
            <a href="{{ route('settings.allowances.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Add New Allowance/Bonus
            </a>
        </div>

    @if(session('success'))
        <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @forelse($settings as $type => $typeSettings)
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="bg-gray-50 px-6 py-3">
                <h3 class="text-lg font-medium text-gray-900 capitalize">{{ $type }}s</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculation</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($typeSettings as $setting)
                        <tr class="hover:bg-gray-50 cursor-pointer {{ !$setting->is_active ? 'opacity-50 bg-gray-50' : '' }}"
                            data-context-menu
                            oncontextmenu="showAllowanceContextMenu(event, {{ $setting->id }}, {{ json_encode($setting->name) }}, {{ json_encode($setting->category) }}, {{ $setting->is_active ? 'true' : 'false' }})">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ !$setting->is_active ? 'text-gray-400' : 'text-gray-900' }}">{{ $setting->name }}</div>
                                <div class="text-xs {{ !$setting->is_active ? 'text-gray-300' : 'text-gray-500' }}">{{ $setting->description ?? 'No description' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ !$setting->is_active ? 'text-gray-400' : 'text-gray-500' }} capitalize">{{ str_replace('_', ' ', $setting->category) }}</div>
                                <div class="text-xs {{ !$setting->is_active ? 'text-gray-300' : 'text-gray-400' }}">{{ ucfirst(str_replace('_', ' ', $setting->frequency)) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ !$setting->is_active ? 'text-gray-400' : 'text-gray-500' }} capitalize">{{ str_replace('_', ' ', $setting->calculation_type) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                @if($setting->calculation_type == 'percentage')
                                    <div class="text-sm font-medium {{ !$setting->is_active ? 'text-gray-400' : 'text-blue-600' }}">{{ number_format($setting->rate_percentage, 2) }}%</div>
                                @elseif($setting->calculation_type == 'fixed_amount')
                                    <div class="text-sm font-medium {{ !$setting->is_active ? 'text-gray-400' : 'text-green-600' }}">₱{{ number_format($setting->fixed_amount, 2) }}</div>
                                @elseif($setting->calculation_type == 'daily_rate_multiplier')
                                    <div class="text-sm font-medium {{ !$setting->is_active ? 'text-gray-400' : 'text-purple-600' }}">{{ number_format($setting->multiplier, 2) }}x</div>
                                @else
                                    <div class="text-sm {{ !$setting->is_active ? 'text-gray-300' : 'text-gray-400' }}">Variable</div>
                                @endif
                                @if($setting->minimum_amount || $setting->maximum_amount)
                                    <div class="text-xs {{ !$setting->is_active ? 'text-gray-300' : 'text-gray-400' }}">
                                        @if($setting->minimum_amount && $setting->maximum_amount)
                                            Min: ₱{{ number_format($setting->minimum_amount, 0) }}, Max: ₱{{ number_format($setting->maximum_amount, 0) }}
                                        @elseif($setting->minimum_amount)
                                            Min: ₱{{ number_format($setting->minimum_amount, 0) }}
                                        @elseif($setting->maximum_amount)
                                            Max: ₱{{ number_format($setting->maximum_amount, 0) }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $setting->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $setting->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">No allowance/bonus settings found.</p>
        </div>
    @endforelse
</div>

<script src="{{ asset('js/settings-context-menu.js') }}"></script>
<script>
// Auto-hide success messages after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('successMessage');
    
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s ease-out';
            successMessage.style.opacity = '0';
            setTimeout(() => successMessage.remove(), 500);
        }, 3000);
    }
});

function showAllowanceContextMenu(event, allowanceId, allowanceName, category, isActive) {
    const config = {
        id: allowanceId,
        name: allowanceName,
        subtitle: category.replace('_', ' ').toUpperCase(),
        viewText: 'View Allowance',
        editText: 'Edit Allowance',
        deleteText: 'Delete Allowance',
        viewUrl: `{{ route('settings.allowances.index') }}/${allowanceId}`,
        editUrl: `{{ route('settings.allowances.index') }}/${allowanceId}/edit`,
        toggleUrl: `{{ route('settings.allowances.index') }}/${allowanceId}/toggle`,
        deleteUrl: `{{ route('settings.allowances.index') }}/${allowanceId}`,
        isActive: isActive,
        canDelete: true,
        deleteConfirmMessage: 'Are you sure you want to delete this allowance setting?'
    };
    
    showSettingsContextMenu(event, config);
}
</script>
    </div>
</div>
</x-app-layout>
