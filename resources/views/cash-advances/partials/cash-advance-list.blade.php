@if($cashAdvances->count() > 0)
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Reference #
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deduction Date
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Approved Amount
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Installment Amount
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Outstanding Balance
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($cashAdvances as $cashAdvance)
            <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
               oncontextmenu="showContextMenu(event, '{{ $cashAdvance->id }}', '{{ $cashAdvance->reference_number }}', '{{ $cashAdvance->employee->full_name }}', '{{ $cashAdvance->status }}', '{{ $cashAdvance->requested_amount }}')"
               onclick="window.location.href='{{ route('cash-advances.show', $cashAdvance) }}'"
               title="Right-click for actions">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        {{ $cashAdvance->reference_number }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        {{ $cashAdvance->employee->full_name }}
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $cashAdvance->employee->employee_number }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($cashAdvance->status === 'approved' && $cashAdvance->first_deduction_date)
                        <div class="text-sm text-gray-900">
                            {{ $cashAdvance->first_deduction_date->format('M d, Y') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Next payroll period
                        </div>
                    @elseif($cashAdvance->status === 'pending')
                        <div class="text-sm text-gray-500">
                            Pending approval
                        </div>
                    @else
                        <div class="text-sm text-gray-500">
                            —
                        </div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($cashAdvance->approved_amount)
                        <div class="text-sm text-gray-900">
                            ₱{{ number_format($cashAdvance->approved_amount, 2) }}
                        </div>
                        @if($cashAdvance->interest_rate > 0)
                            <div class="text-xs text-orange-600">
                                +{{ $cashAdvance->interest_rate }}% interest
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-gray-900">
                            ₱{{ number_format($cashAdvance->requested_amount, 2) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Requested {{ $cashAdvance->requested_date->format('M d, Y') }}
                        </div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($cashAdvance->installment_amount)
                        <div class="text-sm text-blue-600">
                            ₱{{ number_format($cashAdvance->installment_amount, 2) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            for {{ $cashAdvance->installments }} 
                            @if($cashAdvance->deduction_frequency === 'monthly')
                                month{{ $cashAdvance->installments > 1 ? 's' : '' }}
                            @else
                                Pay Period{{ $cashAdvance->installments > 1 ? 's' : '' }}
                            @endif
                        </div>
                    @else
                        <span class="text-sm text-gray-500">—</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($cashAdvance->outstanding_balance > 0)
                        <span class="text-sm text-yellow-600">₱{{ number_format($cashAdvance->outstanding_balance, 2) }}</span>
                    @else
                        <span class="text-sm text-green-600">₱0.00</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @switch($cashAdvance->status)
                        @case('pending')
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                            @break
                        @case('approved')
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Approved
                            </span>
                            @break
                        @case('rejected')
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                Rejected
                            </span>
                            @break
                        @case('fully_paid')
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                Fully Paid
                            </span>
                            @break
                        @case('cancelled')
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Cancelled
                            </span>
                            @break
                    @endswitch
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">No cash advances found</h3>
    <p class="mt-1 text-sm text-gray-500">No cash advances match your current filter criteria.</p>
</div>
@endif