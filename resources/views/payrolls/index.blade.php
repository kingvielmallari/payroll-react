<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ __('Payroll Management') }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Manage all payrolls across different schedules
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <!-- Filter Inputs and Action Buttons in 1 Row -->
                    <div class="flex flex-wrap items-end gap-4 mb-4 w-full">
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700">Pay Schedule</label>
                            <select name="pay_schedule" id="pay_schedule" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm payroll-filter focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Schedules</option>
                                <option value="daily" {{ request('pay_schedule') == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ request('pay_schedule') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="semi_monthly" {{ request('pay_schedule') == 'semi_monthly' ? 'selected' : '' }}>Semi Monthly</option>
                                <option value="monthly" {{ request('pay_schedule') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm payroll-filter focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="type" id="type" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm payroll-filter focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types</option>
                                <option value="automated" {{ request('type') == 'automated' ? 'selected' : '' }}>Automated</option>
                                <option value="manual" {{ request('type') == 'manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[220px]">
                            <label class="block text-sm font-medium text-gray-700">Pay Period</label>
                            <select name="pay_period" id="pay_period" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm payroll-filter focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">All Periods</option>
                                <!-- Pay periods will be populated dynamically based on schedule selection -->
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="button" id="reset_filters" class="inline-flex items-center px-4 h-10 bg-gray-600 border border-transparent rounded-md text-white text-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Reset Filters
                            </button>
                            <button type="button" id="generate_summary" class="inline-flex items-center px-4 h-10 bg-green-600 border border-transparent rounded-md text-white text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Generate Payroll Summary
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payrolls Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Payrolls</h3>
                        <div class="text-sm text-gray-600">
                            <div>Showing {{ $payrolls->count() }} of {{ $payrolls->total() }} payrolls</div>
                            <div class="text-xs text-blue-600 mt-1">
                                <strong>Tip:</strong> Right-click on any payroll row to access View, Edit, Process, and Delete actions.
                            </div>
                        </div>
                    </div>

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
                                   onclick="window.open('@if($payroll->payroll_type === 'automated' && $payroll->payrollDetails->count() === 1){{ route('payrolls.automation.show', ['schedule' => $payroll->pay_schedule, 'employee' => $payroll->payrollDetails->first()->employee_id]) }}@else{{ route('payrolls.show', $payroll) }}@endif', '_blank')"
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
                                            @if($payroll->status == 'approved' && $payroll->approved_at)
                                                {{ $payroll->approved_at->format('M d, Y') }}
                                            @elseif($payroll->status == 'processing' && $payroll->processed_at)
                                                {{ $payroll->processed_at->format('M d, Y') }}
                                            @else
                                                {{ $payroll->created_at->format('M d, Y') }}
                                            @endif
                                        </div>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1 w-fit
                                            @if($payroll->status == 'paid')
                                                bg-green-100 text-green-800
                                            @elseif($payroll->status == 'approved')
                                                bg-green-100 text-green-800
                                            @elseif($payroll->status == 'processing')
                                                bg-yellow-100 text-yellow-800
                                           
                                            @else
                                                bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($payroll->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $payrolls->links() }}
                    </div>
                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No payrolls found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            No payrolls match your current filter criteria. Try adjusting your filters or create a new payroll.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="fixed bg-white rounded-md shadow-xl border border-gray-200 py-1 z-50 hidden min-w-52 backdrop-blur-sm transition-all duration-150 transform opacity-0 scale-95">
        <div id="contextMenuHeader" class="px-3 py-2 border-b border-gray-100 bg-gray-50 rounded-t-md">
            <div class="text-sm font-medium text-gray-900" id="contextMenuPayroll"></div>
            <div class="text-xs text-gray-500" id="contextMenuPeriod"></div>
        </div>
        <div class="py-1">
            <a href="#" id="contextMenuView" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150">
                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                View Details
            </a>
            <a href="#" id="contextMenuEdit" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150" style="display: none;">
                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Payroll
            </a>
            <a href="#" id="contextMenuProcess" class="flex items-center px-3 py-2 text-sm text-green-600 hover:bg-green-50 hover:text-green-700 transition-colors duration-150" style="display: none;">
                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Process Payroll
            </a>
            <a href="#" id="contextMenuApprove" class="flex items-center px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 hover:text-purple-700 transition-colors duration-150" style="display: none;">
                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Approve Payroll
            </a>
            <a href="#" id="contextMenuSendPayroll" class="flex items-center px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 transition-colors duration-150" style="display: none;">
                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Send Payroll
            </a>
            <div class="border-t border-gray-100 my-1"></div>
            <a href="#" id="contextMenuDelete" class="flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors duration-150">
                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete Payroll
            </a>
        </div>
    </div>

    <script>
        let contextMenu = document.getElementById('contextMenu');
        let currentPayrollId = null;
        let currentPayrollStatus = null;
        
        // Hide context menu when clicking outside
        document.addEventListener('click', function(event) {
            contextMenu.classList.add('hidden');
            contextMenu.classList.remove('opacity-100', 'scale-100');
            contextMenu.classList.add('opacity-0', 'scale-95');
        });

        function showContextMenu(event, payrollId, payrollNumber, period, status, payrollType, paySchedule, employeeId) {
            event.preventDefault();
            event.stopPropagation();
            
            currentPayrollId = payrollId;
            currentPayrollStatus = status;
            
            // Update header info
            document.getElementById('contextMenuPayroll').textContent = payrollNumber;
            document.getElementById('contextMenuPeriod').textContent = period;
            
            // Set up action URLs
            let baseUrl = '{{ route("payrolls.index") }}';
            
            // Use new automation URL for automated payrolls with single employee
            if (payrollType === 'automated' && employeeId) {
                document.getElementById('contextMenuView').href = '{{ url("/payrolls/automation") }}/' + paySchedule + '/' + employeeId;
            } else {
                document.getElementById('contextMenuView').href = baseUrl + '/' + payrollId;
            }
            
            document.getElementById('contextMenuEdit').href = baseUrl + '/' + payrollId + '/edit';
            
            // Show/hide actions based on status and permissions
            showHideContextMenuItems(status);
            
            // Position and show menu
            let x = event.pageX;
            let y = event.pageY;
            
            // Adjust position if menu would go off screen
            let menuWidth = 208; // min-w-52 = 13rem = 208px
            let menuHeight = 280; // approximate height
            
            if (x + menuWidth > window.innerWidth) {
                x = window.innerWidth - menuWidth - 10;
            }
            
            if (y + menuHeight > window.innerHeight) {
                y = window.innerHeight - menuHeight - 10;
            }
            
            contextMenu.style.left = x + 'px';
            contextMenu.style.top = y + 'px';
            contextMenu.classList.remove('hidden');
            
            // Animate in
            setTimeout(() => {
                contextMenu.classList.remove('opacity-0', 'scale-95');
                contextMenu.classList.add('opacity-100', 'scale-100');
            }, 10);
        }
        
        function showHideContextMenuItems(status) {
            // Reset all items to hidden
            document.getElementById('contextMenuEdit').style.display = 'none';
            document.getElementById('contextMenuProcess').style.display = 'none';
            document.getElementById('contextMenuApprove').style.display = 'none';
            document.getElementById('contextMenuSendPayroll').style.display = 'none';
            document.getElementById('contextMenuDelete').style.display = 'none';
            
            // Show Edit if payroll can be edited and user has permission
            @can('edit payrolls')
            if (status === 'draft') {
                document.getElementById('contextMenuEdit').style.display = 'flex';
            }
            @endcan
            
            // Show Process if payroll is draft and user has permission
            @can('process payrolls')
            if (status === 'draft') {
                document.getElementById('contextMenuProcess').style.display = 'flex';
            }
            @endcan
            
            // Show Approve if payroll is processing and user has permission
            @can('approve payrolls')
            if (status === 'processing') {
                document.getElementById('contextMenuApprove').style.display = 'flex';
            }
            @endcan
            
            // Show Send Payroll if payroll is approved and user has permission
            @can('email all payslips')
            if (status === 'approved') {
                document.getElementById('contextMenuSendPayroll').style.display = 'flex';
            }
            @endcan
            
            // Show Delete if user has permission (temporarily enabled for all users)
            // @can('delete payrolls')
            if (status === 'draft' || status === 'processing' || status === 'approved') {
                document.getElementById('contextMenuDelete').style.display = 'flex';
            }
            // @endcan
        }
        
        // Handle process action
        document.getElementById('contextMenuProcess').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Submit this payroll for processing?')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("payrolls.index") }}/' + currentPayrollId + '/process';
                
                let csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
        
        // Handle approve action
        document.getElementById('contextMenuApprove').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Approve this payroll?')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("payrolls.index") }}/' + currentPayrollId + '/approve';
                
                let csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
        
        // Handle send payroll action
        document.getElementById('contextMenuSendPayroll').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Send payslips to all employees via email?')) {
                fetch('{{ route("payslips.email-all", ":payrollId") }}'.replace(':payrollId', currentPayrollId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payslips sent successfully to all employees!');
                    } else {
                        alert('Error sending payslips: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    alert('Error sending payslips. Please try again.');
                    console.error('Error:', error);
                });
            }
        });
        
        // Handle delete action
        document.getElementById('contextMenuDelete').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this payroll? This action cannot be undone.')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("payrolls.index") }}/' + currentPayrollId;
                
                let csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                let methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Live filtering functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.payroll-filter');
            const payScheduleSelect = document.getElementById('pay_schedule');
            const payPeriodSelect = document.getElementById('pay_period');

            // Function to update pay periods based on selected schedule
            function updatePayPeriods(schedule) {
                // Clear existing options
                payPeriodSelect.innerHTML = '<option value="">All Periods</option>';
                
                if (!schedule) return;

                // Fetch pay periods for the selected schedule
                fetch(`{{ route('payrolls.index') }}?action=get_periods&schedule=${schedule}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.periods) {
                        data.periods.forEach(period => {
                            const option = document.createElement('option');
                            option.value = period.value;
                            option.textContent = period.label;
                            payPeriodSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching periods:', error));
            }

            // Function to apply filters and reload page
            function applyFilters() {
                const params = new URLSearchParams();
                
                // Keep any existing parameters except page
                const currentParams = new URLSearchParams(window.location.search);
                filterSelects.forEach(select => {
                    if (select.value) {
                        params.set(select.name, select.value);
                    }
                });

                // Copy over existing parameters that aren't filters
                for (const [key, value] of currentParams) {
                    if (!['pay_schedule', 'status', 'type', 'pay_period', 'page'].includes(key)) {
                        params.set(key, value);
                    }
                }

                // Reload page with new filters
                window.location.href = `{{ route('payrolls.index') }}?${params.toString()}`;
            }

            // Add event listeners for live filtering
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    if (this.id === 'pay_schedule') {
                        // For pay schedule changes, first update periods then apply filters
                        updatePayPeriods(this.value);
                        // Apply filters after a short delay to allow periods to load
                        setTimeout(() => {
                            applyFilters();
                        }, 300);
                    } else {
                        // For other filters, apply immediately
                        applyFilters();
                    }
                });
            });

            // Initialize pay periods on page load
            if (payScheduleSelect.value) {
                updatePayPeriods(payScheduleSelect.value);
                // Set the selected pay period if it exists in URL
                const urlParams = new URLSearchParams(window.location.search);
                const selectedPeriod = urlParams.get('pay_period');
                if (selectedPeriod) {
                    setTimeout(() => {
                        payPeriodSelect.value = selectedPeriod;
                    }, 500); // Wait for periods to load
                }
            }

            // Generate Payroll Summary functionality
            document.getElementById('generate_summary').addEventListener('click', function() {
                // Show the export modal
                document.getElementById('exportModal').classList.remove('hidden');
            });

            // Modal functionality
            document.getElementById('closeModal').addEventListener('click', function() {
                document.getElementById('exportModal').classList.add('hidden');
            });

            // Close modal when clicking outside
            document.getElementById('exportModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });

            // PDF Export
            document.getElementById('exportPDF').addEventListener('click', function() {
                generateSummary('pdf');
                document.getElementById('exportModal').classList.add('hidden');
            });

            // Excel Export
            document.getElementById('exportExcel').addEventListener('click', function() {
                generateSummary('excel');
                document.getElementById('exportModal').classList.add('hidden');
            });

            // Function to generate summary
            function generateSummary(format) {
                const currentFilters = new URLSearchParams(window.location.search);
                
                // Add export format to parameters
                currentFilters.set('export', format);
                currentFilters.set('action', 'generate_summary');

                // Create form and submit for file download
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("payrolls.generate-summary") }}';
                
                // Add CSRF token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                // Add all current filter parameters
                for (const [key, value] of currentFilters) {
                    if (key !== 'page') { // Exclude pagination
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        form.appendChild(input);
                    }
                }

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }

            // Reset filters functionality
            document.getElementById('reset_filters').addEventListener('click', function() {
                window.location.href = '{{ route("payrolls.index") }}';
            });
        });
    </script>

    <!-- Export Format Modal -->
    <div id="exportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Choose Export Format</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Select the format for your payroll summary export:
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="exportPDF" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 mb-3">
                        <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                        </svg>
                        Export as PDF
                    </button>
                    <button id="exportExcel" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300 mb-3">
                        <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm4 2a1 1 0 000 2h4a1 1 0 100-2H8zm0 3a1 1 0 000 2h4a1 1 0 100-2H8zm0 3a1 1 0 000 2h4a1 1 0 100-2H8z" clip-rule="evenodd"></path>
                        </svg>
                        Export as Excel
                    </button>
                    <button id="closeModal" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
