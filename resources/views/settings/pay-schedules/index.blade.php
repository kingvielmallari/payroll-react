<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Pay Schedule Settings</h1>
            <div class="text-sm text-gray-600">
                Configure the 3 default payment schedules
            </div>
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

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cut-off Start</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cut-off End</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    function getOrdinal($number) {
                        if ($number % 100 >= 11 && $number % 100 <= 13) {
                            return $number . 'th';
                        }
                        switch ($number % 10) {
                            case 1: return $number . 'st';
                            case 2: return $number . 'nd';
                            case 3: return $number . 'rd';
                            default: return $number . 'th';
                        }
                    }
                @endphp
                @forelse($schedules as $schedule)
                    <tr class="hover:bg-gray-50 cursor-pointer {{ !$schedule->is_active ? 'opacity-50 bg-gray-50' : '' }}" 
                        data-context-menu
                        oncontextmenu="showPayScheduleContextMenu(event, {{ $schedule->id }}, {{ json_encode($schedule->name) }}, {{ json_encode($schedule->cutoff_description ?? 'N/A') }}, {{ $schedule->is_active ? 'true' : 'false' }}, {{ $schedule->is_system_default ?? 'false' ? 'true' : 'false' }})">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium {{ !$schedule->is_active ? 'text-gray-400' : 'text-gray-900' }}">{{ $schedule->name ?? $schedule->cutoff_description }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm {{ !$schedule->is_active ? 'text-gray-400' : 'text-gray-500' }}">
                                @php
                                    $periods = $schedule->cutoff_periods ?? [];
                                @endphp
                                @if($schedule->code === 'weekly' && isset($periods[0]['start_day']))
                                    {{ ucfirst($periods[0]['start_day']) }}
                                @elseif($schedule->code === 'semi_monthly' && isset($periods[0]['start_day']) && isset($periods[1]['start_day']))
                                    <div>{{ getOrdinal($periods[0]['start_day']) }} Day <span class="text-xs text-gray-400">(1st period)</span></div>
                                    <div>{{ getOrdinal($periods[1]['start_day']) }} Day <span class="text-xs text-gray-400">(2nd period)</span></div>
                                @elseif($schedule->code === 'monthly' && isset($periods[0]['start_day']))
                                    {{ getOrdinal($periods[0]['start_day']) }} Day
                                @else
                                    N/A
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm {{ !$schedule->is_active ? 'text-gray-400' : 'text-gray-500' }}">
                                @if($schedule->code === 'weekly' && isset($periods[0]['end_day']))
                                    {{ ucfirst($periods[0]['end_day']) }}
                                @elseif($schedule->code === 'semi_monthly' && isset($periods[0]['end_day']) && isset($periods[1]['end_day']))
                                    <div>{{ getOrdinal($periods[0]['end_day']) }} Day <span class="text-xs text-gray-400">(1st period)</span></div>
                                    <div>{{ getOrdinal($periods[1]['end_day']) }} Day <span class="text-xs text-gray-400">(2nd period)</span></div>
                                @elseif($schedule->code === 'monthly' && isset($periods[0]['end_day']))
                                    {{ getOrdinal($periods[0]['end_day']) }} Day
                                @else
                                    N/A
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm {{ !$schedule->is_active ? 'text-gray-400' : 'text-gray-500' }}">
                                @if($schedule->code === 'weekly' && isset($periods[0]['pay_day']))
                                    {{ ucfirst($periods[0]['pay_day']) }}
                                @elseif($schedule->code === 'semi_monthly' && isset($periods[0]['pay_date']) && isset($periods[1]['pay_date']))
                                    <div>{{ getOrdinal($periods[0]['pay_date']) }} Day <span class="text-xs text-gray-400">(1st period)</span></div>
                                    <div>{{ getOrdinal($periods[1]['pay_date']) }} Day <span class="text-xs text-gray-400">(2nd period)</span></div>
                                @elseif($schedule->code === 'monthly' && isset($periods[0]['pay_date']))
                                    {{ getOrdinal($periods[0]['pay_date']) }} Day
                                @else
                                    N/A
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($schedule->is_system_default)
                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    System Default
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No pay schedules found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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

function showPayScheduleContextMenu(event, scheduleId, scheduleName, scheduleCode, isActive, isSystemDefault) {
    const config = {
        id: scheduleId,
        name: scheduleName,
        subtitle: scheduleCode,
        viewText: 'View Schedule',
        editText: 'Edit Schedule', 
        deleteText: 'Delete Schedule',
        viewUrl: `{{ route('settings.pay-schedules.index') }}/${scheduleId}`,
        editUrl: `{{ route('settings.pay-schedules.index') }}/${scheduleId}/edit`,
        toggleUrl: `{{ route('settings.pay-schedules.index') }}/${scheduleId}/toggle`,
        deleteUrl: `{{ route('settings.pay-schedules.index') }}/${scheduleId}`,
        isActive: isActive,
        canDelete: !isSystemDefault,
        deleteConfirmMessage: 'Are you sure you want to delete this pay schedule?'
    };
    
    showSettingsContextMenu(event, config);
}
</script>
    </div>
</div>
</x-app-layout>
