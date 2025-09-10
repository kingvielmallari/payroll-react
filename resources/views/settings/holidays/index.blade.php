<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Holiday Settings</h1>
            <a href="{{ route('settings.holidays.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Add New Holiday
            </a>
        </div>

    @if(session('success'))
        <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4">
        <label for="year-filter" class="block text-sm font-medium text-gray-700 mb-2">Filter by Year:</label>
        <select id="year-filter" class="border-gray-300 rounded-md shadow-sm" onchange="filterByYear(this.value)">
            @foreach($years as $year)
                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>
    </div>

    @forelse($holidays as $type => $typeHolidays)
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="bg-gray-50 px-6 py-3">
                <h3 class="text-lg font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $type) }} Holidays</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($typeHolidays as $holiday)
                        <tr class="hover:bg-gray-50 cursor-pointer {{ !$holiday->is_active ? 'opacity-50 bg-gray-50' : '' }}"
                            data-context-menu
                            oncontextmenu="showHolidayContextMenu(event, {{ $holiday->id }}, {{ json_encode($holiday->name) }}, {{ json_encode($type) }}, {{ $holiday->is_active ? 'true' : 'false' }})">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ !$holiday->is_active ? 'text-gray-400' : 'text-gray-900' }}">{{ $holiday->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm {{ !$holiday->is_active ? 'text-gray-400' : 'text-gray-900' }}">
                                    {{ is_string($holiday->date) ? \Carbon\Carbon::parse($holiday->date)->format('M j, Y') : $holiday->date->format('M j, Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $holiday->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $holiday->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">No holidays found for {{ $currentYear }}.</p>
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

function filterByYear(year) {
    window.location.href = `{{ route('settings.holidays.index') }}?year=${year}`;
}

function showHolidayContextMenu(event, holidayId, holidayName, holidayType, isActive) {
    const config = {
        id: holidayId,
        name: holidayName,
        subtitle: holidayType.replace('_', ' ').toUpperCase(),
        viewText: 'View Holiday',
        editText: 'Edit Holiday',
        deleteText: 'Delete Holiday',
        viewUrl: `{{ route('settings.holidays.index') }}/${holidayId}`,
        editUrl: `{{ route('settings.holidays.index') }}/${holidayId}/edit`,
        toggleUrl: `{{ route('settings.holidays.index') }}/${holidayId}/toggle`,
        deleteUrl: `{{ route('settings.holidays.index') }}/${holidayId}`,
        isActive: isActive,
        canDelete: true,
        deleteConfirmMessage: 'Are you sure you want to delete this holiday?'
    };
    
    showSettingsContextMenu(event, config);
}
</script>
    </div>
</div>
</x-app-layout>
