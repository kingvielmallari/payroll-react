<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Suspension Days</h1>
            <a href="{{ route('settings.suspension.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Add Suspension Day
            </a>
        </div>

    @if(session('success'))
        <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($suspensions->flatten()->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Range</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($suspensions->flatten() as $suspension)
                        @php
                            $isActive = $suspension->status === 'active';
                            $rowClasses = $isActive 
                                ? 'hover:bg-gray-50 cursor-pointer' 
                                : 'bg-gray-100 text-gray-500 cursor-pointer';
                            $textClasses = $isActive 
                                ? 'text-gray-900' 
                                : 'text-gray-500';
                        @endphp
                        <tr class="{{ $rowClasses }}"
                            data-context-menu
                            oncontextmenu="showNoWorkContextMenu(event, '{{ $suspension->id }}', '{{ addslashes($suspension->name) }}', '{{ $suspension->type }}', '{{ $suspension->status }}')">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ $textClasses }}">{{ $suspension->name }}</div>
                                @if($suspension->description)
                                    <div class="text-sm {{ $isActive ? 'text-gray-500' : 'text-gray-400' }}">{{ $suspension->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ $textClasses }} capitalize">
                                    @switch($suspension->reason)
                                        @case('weather')
                                            Weather
                                            @break
                                        @case('system_maintenance')
                                            System Maintenance
                                            @break
                                        @case('emergency')
                                            Emergency
                                            @break
                                        @case('government_order')
                                            Government Order
                                            @break
                                        @case('other')
                                            Other
                                            @break
                                        @default
                                            {{ ucfirst(str_replace('_', ' ', $suspension->reason)) }}
                                    @endswitch
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ $textClasses }}">
                                    @php
                                        $dateFrom = is_string($suspension->date_from) ? \Carbon\Carbon::parse($suspension->date_from) : $suspension->date_from;
                                        $dateTo = is_string($suspension->date_to) ? \Carbon\Carbon::parse($suspension->date_to) : $suspension->date_to;
                                    @endphp
                                    
                                    {{ $dateFrom->format('M j, Y') }}
                                    @if($suspension->date_to && !$dateFrom->isSameDay($dateTo))
                                        - {{ $dateTo->format('M j, Y') }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm {{ $isActive ? 'text-gray-500' : 'text-gray-400' }}">
                                    @if($suspension->pay_applicable_to)
                                        @switch($suspension->pay_applicable_to)
                                            @case('all')
                                                All Employees
                                                @break
                                            @case('with_benefits')
                                                Employees with Benefits
                                                @break
                                            @case('without_benefits')
                                                Employees without Benefits
                                                @break
                                            @default
                                                {{ ucfirst(str_replace('_', ' ', $suspension->pay_applicable_to)) }}
                                        @endswitch
                                    @else
                                        No Pay Applicable
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($isActive)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">No no work/suspended settings found.</p>
        </div>
    @endforelse
</div>

<script src="{{ asset('js/settings-context-menu.js') }}"></script>
<script>
function showNoWorkContextMenu(event, suspensionId, suspensionName, suspensionType, suspensionStatus) {
    const baseUrl = '{{ url("settings/suspension") }}';
    const config = {
        id: suspensionId,
        name: suspensionName,
        subtitle: suspensionType.replace('_', ' ').toUpperCase(),
        viewText: 'View Setting',
        editText: 'Edit Setting',
        deleteText: 'Delete Suspension',
        viewUrl: `${baseUrl}/${suspensionId}`,
        editUrl: `${baseUrl}/${suspensionId}/edit`,
        deleteUrl: `${baseUrl}/${suspensionId}`,
        isActive: suspensionStatus === 'active',
        canDelete: true,
        canToggle: true, // Enable toggle for suspension settings
        toggleUrl: `${baseUrl}/${suspensionId}/toggle`,
        deleteConfirmMessage: 'Are you sure you want to delete this setting?'
    };
    
    showSettingsContextMenu(event, config);
}

// Auto-hide success message after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(function() {
            successMessage.style.opacity = '0';
            setTimeout(function() {
                successMessage.remove();
            }, 300); // Wait for fade out transition
        }, 3000);
    }
});
</script>
    </div>
</div>
</x-app-layout>
