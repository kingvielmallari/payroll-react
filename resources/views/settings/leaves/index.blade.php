<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Paid Leave Settings</h1>
            <a href="{{ route('settings.leaves.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Add New Leave Type
            </a>
        </div>

    @if(session('success'))
        <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($leaveSettings->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Limit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($leaveSettings as $leave)
                        @php
                            $isActive = $leave->is_active;
                            // Format limit period
                            $limitPeriod = ucfirst($leave->limit_period ?? 'Monthly');
                            // Format applicable to
                            $applicableTo = match($leave->pay_applicable_to ?? $leave->applicable_to ?? 'all') {
                                'with_benefits' => 'With Benefits Only',
                                'without_benefits' => 'Without Benefits Only', 
                                'all', 'both' => 'All Employees',
                                default => 'All Employees'
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 cursor-pointer {{ !$isActive ? 'opacity-50 bg-gray-50' : '' }}"
                            data-context-menu
                            oncontextmenu="showLeaveContextMenu(event, '{{ $leave->id }}', '{{ addslashes($leave->name) }}', '{{ $leave->code }}', '{{ $leave->is_active ? 'true' : 'false' }}')">
                            
                            <!-- Column 1: Name -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ !$isActive ? 'text-gray-400' : 'text-gray-900' }}">{{ $leave->name }}</div>
                                <div class="text-sm {{ !$isActive ? 'text-gray-400' : 'text-gray-500' }}">
                                    {{ $limitPeriod }} {{ $leave->code }} Duration ({{ $leave->total_days ?? 1 }} {{ ($leave->total_days ?? 1) == 1 ? 'day' : 'days' }})
                                </div>
                            </td>

                            <!-- Column 2: Pay Details -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $payDisplay = match($leave->pay_rule ?? 'full') {
                                        'full' => '100%',
                                        'half' => '50%',
                                        default => ($leave->pay_percentage ?? 100) . '%'
                                    };
                                @endphp
                                <div class="text-sm {{ !$isActive ? 'text-gray-400' : 'text-gray-900' }}">
                                    {{ $payDisplay }}
                                </div>
                                <div class="text-xs {{ !$isActive ? 'text-gray-400' : 'text-gray-500' }} mt-1">
                                    {{ $applicableTo }}
                                </div>
                            </td>

                            <!-- Column 3: Limit -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ !$isActive ? 'text-gray-400' : 'text-gray-900' }}">
                                    {{ $leave->limit_quantity ?? 1 }} {{ ($leave->limit_quantity ?? 1) == 1 ? 'Credit' : 'Credits' }}
                                </div>
                                <div class="text-xs {{ !$isActive ? 'text-gray-400' : 'text-gray-500' }} mt-1">
                                    {{ $limitPeriod }}
                                </div>
                            </td>

                            <!-- Column 4: Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">No paid leave settings found.</p>
        </div>
    @endif
</div>

<script src="{{ asset('js/settings-context-menu.js') }}"></script>
<script>
function showLeaveContextMenu(event, leaveId, leaveName, leaveCode, isActive) {
    const baseUrl = '{{ url("settings/leaves") }}';
    const config = {
        id: leaveId,
        name: leaveName,
        subtitle: leaveCode,
        viewText: 'View Leave Type',
        editText: 'Edit Leave Type',
        deleteText: 'Delete Leave Type',
        viewUrl: `${baseUrl}/${leaveId}`,
        editUrl: `${baseUrl}/${leaveId}/edit`,
        deleteUrl: `${baseUrl}/${leaveId}`,
        isActive: isActive,
        canDelete: true,
        canToggle: true, // Enable toggle for leave settings
        toggleUrl: `${baseUrl}/${leaveId}/toggle`,
        deleteConfirmMessage: 'Are you sure you want to delete this leave type?'
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
