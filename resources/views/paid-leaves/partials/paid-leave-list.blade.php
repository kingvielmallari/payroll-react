@if($paidLeaves->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($paidLeaves as $paidLeave)
                <tr class="paid-leave-row hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
                    data-paid-leave-id="{{ $paidLeave->id }}"
                    data-reference="{{ $paidLeave->reference_number }}"
                    data-employee="{{ $paidLeave->employee->full_name }}"
                    data-status="{{ $paidLeave->status }}"
                    oncontextmenu="showPaidLeaveContextMenu(event, this)"
                    onclick="window.location.href='{{ route('paid-leaves.show', $paidLeave) }}'">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $paidLeave->reference_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $paidLeave->employee->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $paidLeave->employee->employee_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $paidLeave->leave_type_display }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="font-medium">
                            @if($paidLeave->start_date->format('Y-m-d') === $paidLeave->end_date->format('Y-m-d'))
                                {{ $paidLeave->start_date->format('M d, Y') }}
                            @elseif($paidLeave->start_date->format('Y-m') === $paidLeave->end_date->format('Y-m'))
                                {{ $paidLeave->start_date->format('M d') }}-{{ $paidLeave->end_date->format('d, Y') }}
                            @else
                                {{ $paidLeave->start_date->format('M d, Y') }} - {{ $paidLeave->end_date->format('M d, Y') }}
                            @endif
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $paidLeave->total_days }} {{ Str::plural('day', $paidLeave->total_days) }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ₱{{ number_format($paidLeave->total_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            {!! $paidLeave->status_badge !!}
                            @if($paidLeave->status === 'approved' && $paidLeave->approved_date)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $paidLeave->approved_date->format('M d, Y') }}
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No paid leaves found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or create a new paid leave request.</p>
        @can('create paid leaves')
        <div class="mt-6">
            <a href="{{ route('paid-leaves.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Paid Leave Request
            </a>
        </div>
        @endcan
    </div>
@endif