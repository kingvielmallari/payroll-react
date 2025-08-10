<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">No Work/Suspended Settings</h1>
            <a href="{{ route('settings.no-work.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Add New Setting
            </a>
        </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @forelse($suspensions as $status => $statusSuspensions)
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="bg-gray-50 px-6 py-3">
                <h3 class="text-lg font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $status) }}</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Range</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Affected</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($statusSuspensions as $suspension)
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            data-context-menu
                            oncontextmenu="showNoWorkContextMenu(event, '{{ $suspension->id }}', '{{ addslashes($suspension->name) }}', '{{ $suspension->type }}', '{{ $status }}')">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $suspension->name }}</div>
                                @if($suspension->description)
                                    <div class="text-sm text-gray-500">{{ $suspension->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $suspension->type) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ is_string($suspension->date_from) ? \Carbon\Carbon::parse($suspension->date_from)->format('M j, Y') : $suspension->date_from->format('M j, Y') }}
                                    @if($suspension->date_to)
                                        - {{ is_string($suspension->date_to) ? \Carbon\Carbon::parse($suspension->date_to)->format('M j, Y') : $suspension->date_to->format('M j, Y') }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">
                                    @if($suspension->affects_all_employees)
                                        All Employees
                                    @else
                                        @php
                                            $affected = [];
                                            if($suspension->affectedDepartments->count()) $affected[] = $suspension->affectedDepartments->count() . ' Dept(s)';
                                            if($suspension->affectedPositions->count()) $affected[] = $suspension->affectedPositions->count() . ' Position(s)';
                                            if($suspension->affectedEmployees->count()) $affected[] = $suspension->affectedEmployees->count() . ' Employee(s)';
                                        @endphp
                                        {{ implode(', ', $affected) ?: 'None' }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">No no work/suspended settings found.</p>
        </div>
    @endforelse
</div>

<script src="{{ asset('js/settings-context-menu.js') }}"></script>
<script>
function showNoWorkContextMenu(event, suspensionId, suspensionName, suspensionType, suspensionStatus) {
    const config = {
        id: suspensionId,
        name: suspensionName,
        subtitle: suspensionType.replace('_', ' ').toUpperCase(),
        viewText: 'View Setting',
        editText: 'Edit Setting',
        deleteText: 'Delete Setting',
        viewUrl: `{{ route('settings.no-work.index') }}/${suspensionId}`,
        editUrl: `{{ route('settings.no-work.index') }}/${suspensionId}/edit`,
        deleteUrl: `{{ route('settings.no-work.index') }}/${suspensionId}`,
        isActive: true, // No work settings don't have active/inactive status
        canDelete: true,
        canToggle: false, // No toggle for no-work settings
        deleteConfirmMessage: 'Are you sure you want to delete this setting?'
    };
    
    showSettingsContextMenu(event, config);
}
</script>
    </div>
</div>
</x-app-layout>
