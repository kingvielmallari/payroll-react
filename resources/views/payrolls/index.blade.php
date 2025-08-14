<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                @if(request('schedule'))
                <a href="{{ route('payrolls.index') }}" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Schedule Selection
                </a>
                @endif
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        @if(request('schedule') && isset($scheduleSetting))
                            {{ $scheduleSetting->name }} Payrolls
                        @else
                            {{ __('Payroll Management') }}
                        @endif
                    </h2>
                    @if(request('schedule') && isset($scheduleSetting))
                    <p class="text-sm text-gray-600 mt-1">
                        Manage payrolls for {{ strtolower($scheduleSetting->name) }} pay schedule
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('payrolls.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Types</option>
                                <option value="regular" {{ request('type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="special" {{ request('type') == 'special' ? 'selected' : '' }}>Special</option>
                                <option value="13th_month" {{ request('type') == '13th_month' ? 'selected' : '' }}>13th Month</option>
                                <option value="bonus" {{ request('type') == 'bonus' ? 'selected' : '' }}>Bonus</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="flex items-end space-x-2">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700">Date To</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div class="flex-shrink-0">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md text-white text-sm hover:bg-gray-700">
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Payroll Number
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Period
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Gross (DTR)
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Net
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($payrolls as $payroll)
                                <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
                                   oncontextmenu="showContextMenu(event, '{{ $payroll->id }}', '{{ $payroll->payroll_number }}', '{{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}', '{{ $payroll->status }}')"
                                   onclick="window.location.href='{{ route('payrolls.show', $payroll) }}'"
                                   title="Right-click for actions">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $payroll->payroll_number }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">Pay Date: {{ $payroll->pay_date->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ ucfirst(str_replace('_', ' ', $payroll->payroll_type)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($payroll->payroll_details_count <= 3)
                                            @foreach($payroll->payrollDetails as $detail)
                                                <div class="text-sm">
                                                    <span class="font-medium">{{ $detail->employee->full_name }}</span>
                                                    <span class="text-gray-500 text-xs ml-1">({{ $detail->employee->employee_number }})</span>
                                                </div>
                                            @endforeach
                                        @else
                                            @foreach($payroll->payrollDetails->take(2) as $detail)
                                                <div class="text-sm">
                                                    <span class="font-medium">{{ $detail->employee->full_name }}</span>
                                                    <span class="text-gray-500 text-xs ml-1">({{ $detail->employee->employee_number }})</span>
                                                </div>
                                            @endforeach
                                            <div class="text-xs text-gray-500 mt-1">
                                                +{{ $payroll->payroll_details_count - 2 }} more employees
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-medium text-green-600">₱{{ number_format($payroll->total_gross, 2) }}</div>
                                        <div class="text-xs text-gray-500">Based on DTR</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₱{{ number_format($payroll->total_net, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $payroll->status == 'paid' ? 'bg-green-100 text-green-800' : 
                                               ($payroll->status == 'approved' ? 'bg-blue-100 text-blue-800' : 
                                                ($payroll->status == 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                                 ($payroll->status == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))) }}">
                                            {{ ucfirst($payroll->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $payroll->created_at->format('M d, Y') }}
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
                            @if(request('schedule'))
                                No payrolls found for {{ isset($scheduleSetting) ? strtolower($scheduleSetting->name) : 'this' }} pay schedule.
                                <br>Payrolls will be automatically created when payroll periods start.
                            @else
                                Select a pay schedule above to view payrolls for that frequency.
                            @endif
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

        function showContextMenu(event, payrollId, payrollNumber, period, status) {
            event.preventDefault();
            event.stopPropagation();
            
            currentPayrollId = payrollId;
            currentPayrollStatus = status;
            
            // Update header info
            document.getElementById('contextMenuPayroll').textContent = payrollNumber;
            document.getElementById('contextMenuPeriod').textContent = period;
            
            // Set up action URLs
            let baseUrl = '{{ route("payrolls.index") }}';
            
            document.getElementById('contextMenuView').href = baseUrl + '/' + payrollId;
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
            
            // Show Delete if user has permission
            @can('delete payrolls')
            if (status === 'draft' || status === 'processing' || status === 'approved') {
                document.getElementById('contextMenuDelete').style.display = 'flex';
            }
            @endcan
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
    </script>
</x-app-layout>
