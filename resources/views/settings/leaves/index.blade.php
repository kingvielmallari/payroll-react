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
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($leaveSettings as $leave)
                    <tr class="hover:bg-gray-50 cursor-pointer"
                        data-context-menu
                        oncontextmenu="showLeaveContextMenu(event, '{{ $leave->id }}', '{{ addslashes($leave->name) }}', '{{ $leave->code }}', {{ $leave->is_active ? 'true' : 'false' }})">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $leave->name }}</div>
                            <div class="text-sm text-gray-500">{{ $leave->description }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $leave->code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $leave->max_days_per_year ?? 'Unlimited' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $leave->pay_rate_percentage }}%</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $leave->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $leave->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No paid leave settings found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="{{ asset('js/settings-context-menu.js') }}"></script>
<script>
function showLeaveContextMenu(event, leaveId, leaveName, leaveCode, isActive) {
    const config = {
        id: leaveId,
        name: leaveName,
        subtitle: leaveCode,
        viewText: 'View Leave Type',
        editText: 'Edit Leave Type',
        deleteText: 'Delete Leave Type',
        viewUrl: `{{ route('settings.leaves.index') }}/${leaveId}`,
        editUrl: `{{ route('settings.leaves.index') }}/${leaveId}/edit`,
        toggleUrl: `{{ route('settings.leaves.index') }}/${leaveId}/toggle`,
        deleteUrl: `{{ route('settings.leaves.index') }}/${leaveId}`,
        isActive: isActive,
        canDelete: true,
        deleteConfirmMessage: 'Are you sure you want to delete this leave type?'
    };
    
    showSettingsContextMenu(event, config);
}
</script>
    </div>
</div>
</x-app-layout>
