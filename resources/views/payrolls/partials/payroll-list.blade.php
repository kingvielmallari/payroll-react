@if($payrolls->count() > 0)
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                    Payroll Number
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                    Period
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                    Employee
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                    Status
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($payrolls as $payroll)
            <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
               oncontextmenu="showContextMenu(event, '{{ $payroll->id }}', '{{ $payroll->payroll_number }}', '{{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}', '{{ $payroll->status }}', '{{ $payroll->payroll_type }}', '{{ $payroll->pay_schedule }}', '{{ $payroll->payrollDetails->count() === 1 ? $payroll->payrollDetails->first()->employee_id : '' }}')"
               onclick="window.open('{{ route('payrolls.automation.show', ['schedule' => $payroll->pay_schedule, 'id' => $payroll->id]) }}', '_blank')"
               title="Click to open in new tab, Right-click for actions">
               
                <!-- Payroll Number Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ $payroll->payroll_number }}</div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1 w-fit
                        {{ $payroll->payroll_type == 'automated' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $payroll->payroll_type)) }}
                    </span>
                </td>
                
                <!-- Period Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 font-medium">
                        {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}
                    </div>
                    <div class="text-xs text-gray-500">Pay Date: {{ $payroll->pay_date->format('M d, Y') }}</div>
                </td>
                
                <!-- Employee Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($payroll->payroll_details_count <= 3)
                        @foreach($payroll->payrollDetails as $detail)
                            <div class="text-sm font-medium text-gray-900">{{ $detail->employee->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $detail->employee->employee_number }}</div>
                            @if(!$loop->last)
                                <div class="my-1"></div>
                            @endif
                        @endforeach
                    @else
                        @foreach($payroll->payrollDetails->take(2) as $detail)
                            <div class="text-sm font-medium text-gray-900">{{ $detail->employee->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $detail->employee->employee_number }}</div>
                            @if(!$loop->last)
                                <div class="my-1"></div>
                            @endif
                        @endforeach
                        <div class="text-xs text-blue-600 mt-2">
                            +{{ $payroll->payroll_details_count - 2 }} more employees
                        </div>
                    @endif
                </td>
                
                <!-- Status Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        @if($payroll->is_paid && $payroll->marked_paid_at)
                            {{ $payroll->marked_paid_at->format('M d, Y') }}
                        @elseif($payroll->status == 'approved' && $payroll->approved_at)
                            {{ $payroll->approved_at->format('M d, Y') }}
                        @elseif($payroll->status == 'processing' && $payroll->processed_at)
                            {{ $payroll->processed_at->format('M d, Y') }}
                        @else
                            {{ $payroll->created_at->format('M d, Y') }}
                        @endif
                    </div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1 w-fit
                        @if($payroll->is_paid)
                            bg-green-100 text-green-800
                        @elseif($payroll->status == 'approved')
                            bg-blue-100 text-blue-800
                        @elseif($payroll->status == 'processing')
                            bg-yellow-100 text-yellow-800
                       
                        @else
                            bg-gray-100 text-gray-800
                        @endif">
                        {{ $payroll->is_paid ? 'Paid' : ucfirst($payroll->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif