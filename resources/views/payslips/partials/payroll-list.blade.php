@if($payrolls->count() > 0)
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                    Payslip Number
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
            @php
                $payrollDetail = $payroll->payrollDetails->first();
            @endphp
            <tr class="payslip-row {{ $payroll->status === 'processing' ? 'bg-gray-100 cursor-not-allowed' : 'hover:bg-gray-50 cursor-pointer' }} transition-colors duration-150" 
               data-payroll-id="{{ $payroll->id }}"
               data-payroll-detail-id="{{ $payrollDetail?->id }}"
               data-payroll-number="{{ $payroll->payroll_number }}"
               data-status="{{ $payroll->status }}"
               data-send-status="{{ $payrollDetail && $payrollDetail->payslip_sent ? 'Sent' : '' }}"
               data-send-details="{{ $payrollDetail && $payrollDetail->payslip_sent && $payrollDetail->payslip_last_sent_at ? 'Sent: ' . $payrollDetail->payslip_last_sent_at->format('M j, g:i A') : '' }}"
               @if($payroll->status !== 'processing')
                   onclick="window.open('{{ route('payrolls.payslip', ['payroll' => $payroll->id]) }}', '_blank')"
                   title="Click to view payslip | Right-click for more actions"
               @else
                   title="Payslip is still being processed and cannot be viewed yet"
               @endif>
               
                <!-- Payslip Number Column -->
                <td class="px-6 py-4 whitespace-nowrap {{ $payroll->status === 'processing' ? 'text-gray-500' : '' }}">
                    <div class="flex items-center">
                        <div class="text-sm font-medium {{ $payroll->status === 'processing' ? 'text-gray-500' : 'text-gray-900' }}">{{ $payroll->payroll_number }}</div>
                     
                    </div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1 w-fit
                        {{ $payroll->payroll_type == 'automated' ? ($payroll->status === 'processing' ? 'bg-gray-200 text-gray-600' : 'bg-blue-100 text-blue-800') : ($payroll->status === 'processing' ? 'bg-gray-200 text-gray-600' : 'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst(str_replace('_', ' ', $payroll->payroll_type)) }}
                    </span>
                   
                </td>
                
                <!-- Period Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm {{ $payroll->status === 'processing' ? 'text-gray-500' : 'text-gray-900' }} font-medium">
                        {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}
                    </div>
                    <div class="text-xs {{ $payroll->status === 'processing' ? 'text-gray-400' : 'text-gray-500' }}">Pay Date: {{ $payroll->pay_date->format('M d, Y') }}</div>
                </td>
                
                <!-- Employee Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $employee = $payroll->payrollDetails->first()?->employee;
                    @endphp
                    @if($employee)
                        <div class="text-sm {{ $payroll->status === 'processing' ? 'text-gray-500' : 'text-gray-900' }} font-medium">
                            {{ $employee->full_name }}
                        </div>
                        <div class="text-xs {{ $payroll->status === 'processing' ? 'text-gray-400' : 'text-gray-500' }}">
                            {{ $employee->position->title ?? 'No Position' }}
                        </div>
                    @else
                        <div class="text-sm {{ $payroll->status === 'processing' ? 'text-gray-500' : 'text-gray-900' }}">
                            -
                        </div>
                    @endif
                </td>
                
                <!-- Status Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium {{ $payroll->status === 'processing' ? 'text-gray-500' : 'text-gray-900' }}">
                        @if($payroll->is_paid && $payroll->marked_paid_at)
                            Paid on {{ $payroll->marked_paid_at->format('M d, Y') }}
                        @elseif($payroll->status == 'approved' && $payroll->approved_at)
                            Approved on {{ $payroll->approved_at->format('M d, Y') }}
                        @elseif($payroll->status == 'processing')
                            {{ $payroll->created_at->format('M d, Y') }}
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
                            bg-gray-200 text-gray-600
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
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No payslips found</h3>
        <p class="mt-1 text-sm text-gray-500">
            You don't have any approved payslips yet.
        </p>
    </div>
@endif

<!-- Context Menu -->
<div id="payslipContextMenu" class="fixed bg-white rounded-md shadow-xl border border-gray-200 py-1 z-50 hidden min-w-52 backdrop-blur-sm transition-all duration-150 transform opacity-0 scale-95">
    <div id="contextMenuHeader" class="px-3 py-2 border-b border-gray-100 bg-gray-50 rounded-t-md">
        <div class="text-sm font-medium text-gray-900" id="contextPayslipNumber">Loading...</div>
        <div class="text-xs text-gray-500" id="contextPayslipPeriod"></div>
    </div>
    <div class="py-1">
        <a href="#" id="viewPayslip" class="flex items-center px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150">
            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            View Payslip
        </a>
        <a href="#" id="downloadPayslip" class="flex items-center px-3 py-2 text-sm text-green-600 hover:bg-green-50 hover:text-green-700 transition-colors duration-150">
            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download Payslip
        </a>
        <a href="#" id="sendPayslip" class="flex items-center px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 hover:text-purple-700 transition-colors duration-150">
            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <div>
                <div id="contextPayslipSendText">Send Payslip</div>
                <div class="text-xs text-gray-500" id="contextPayslipSendStatus"></div>
            </div>
        </a>
    </div>
</div>