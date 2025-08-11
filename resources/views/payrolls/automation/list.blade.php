<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Automated Payrolls') }} - {{ $selectedSchedule->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    View and manage automated payrolls for {{ ucfirst(str_replace('_', ' ', $selectedSchedule->code)) }} schedule
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.automation.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Schedules
                </a>
                <a href="{{ route('payrolls.automation.create', ['schedule' => $scheduleCode, 'action' => 'create']) }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                    Generate New Payroll
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Payrolls List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Automated Payrolls 
                                <span class="text-sm font-normal text-gray-500">
                                    ({{ $payrolls->total() }} total payrolls)
                                </span>
                            </h3>
                            {{-- @isset($currentPeriod)
                            <p class="text-sm text-blue-600 mt-1">
                                <strong>Current Period:</strong> 
                                {{ \Carbon\Carbon::parse($currentPeriod['start'])->format('M d') }} - 
                                {{ \Carbon\Carbon::parse($currentPeriod['end'])->format('M d, Y') }}
                                (Pay Date: {{ \Carbon\Carbon::parse($currentPeriod['pay_date'])->format('M d, Y') }})
                            </p>
                            @endisset --}}
                        </div>
                        <div class="text-sm text-gray-600">
                            <div class="text-xs text-blue-600">
                                <strong>Tip:</strong> Right-click on any payroll row to access View, Edit, Process, and Delete actions.
                            </div>
                        </div>
                    </div>

                    @if($payrolls->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payroll</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employees</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Net</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payrolls as $payroll)
                                        <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
                                           oncontextmenu="showContextMenu(event, '{{ $payroll->id }}', '{{ $payroll->payroll_number }}', '{{ \Carbon\Carbon::parse($payroll->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($payroll->period_end)->format('M d, Y') }}', '{{ $payroll->status }}')"
                                           onclick="window.location.href='{{ route('payrolls.show', $payroll) }}'"
                                           title="Right-click for actions">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $payroll->payroll_number }}</div>
                                                <div class="text-sm text-gray-500">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Automated
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ \Carbon\Carbon::parse($payroll->period_start)->format('M d') }} - 
                                                    {{ \Carbon\Carbon::parse($payroll->period_end)->format('M d, Y') }}
                                                </div>
                                                <div class="text-sm text-gray-500">Pay: {{ \Carbon\Carbon::parse($payroll->pay_date)->format('M d, Y') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $payroll->payroll_details_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₱{{ number_format($payroll->total_net ?? 0, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $payroll->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $payroll->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $payroll->status === 'processed' ? 'bg-green-100 text-green-800' : '' }}">
                                                    {{ ucfirst($payroll->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $payroll->created_at->format('M d, Y') }}
                                                <div class="text-xs text-gray-400">{{ $payroll->created_at->format('g:i A') }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $payrolls->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 12h6m-6 4h6m2 5l7-7 7 7M9 20h6m-7 4h7m6-4V8a2 2 0 012-2h6a2 2 0 012 2v4m-3 4a2 2 0 01-2 2H9a2 2 0 01-2-2v-4z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No automated payrolls found</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No automated payrolls have been created for the {{ $selectedSchedule->name }} schedule yet.
                            </p>
                            <div class="mt-6">
                                <a href="{{ route('payrolls.automation.create', ['schedule' => $scheduleCode, 'action' => 'create']) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Generate Your First Automated Payroll
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Schedule Information -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">{{ $selectedSchedule->name }} Schedule - Current Period</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>This page shows automated payrolls for the current {{ $selectedSchedule->name }} period only. Each payroll represents an individual employee and is generated based on the schedule settings and DTR data.</p>
                            {{-- @isset($currentPeriod)
                            <p class="mt-1">
                                <strong>Current Period:</strong> 
                                {{ \Carbon\Carbon::parse($currentPeriod['start'])->format('M d') }} - 
                                {{ \Carbon\Carbon::parse($currentPeriod['end'])->format('M d, Y') }}
                            </p>
                            @endisset --}}
                            <div class="mt-2">
                                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                                    {{ ucfirst(str_replace('_', ' ', $selectedSchedule->code)) }}
                                </span>
                                @isset($currentPeriod)
                                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium ml-1">
                                    Current Period Only
                                </span>
                                @endisset
                            </div>
                        </div>
                    </div>
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
            let baseUrl = '{{ url("/payrolls") }}';
            
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
            
            // Show Delete if user has permission
            @can('delete payrolls')
            if (status === 'draft' || status === 'processing') {
                document.getElementById('contextMenuDelete').style.display = 'flex';
            }
            @endcan
            
            // Show Delete for approved payrolls if user has special permission
            @can('delete approved payrolls')
            if (status === 'approved') {
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
                form.action = '{{ url("/payrolls") }}/' + currentPayrollId + '/process';
                
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
                form.action = '{{ url("/payrolls") }}/' + currentPayrollId + '/approve';
                
                let csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
        
        // Handle delete action
        document.getElementById('contextMenuDelete').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this payroll? This action cannot be undone.')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ url("/payrolls") }}/' + currentPayrollId;
                
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
