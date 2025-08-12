<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Cash Advances') }}
            </h2>
            @can('create cash advances')
            <div class="relative">
                <button id="addCashAdvanceBtn" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        oncontextmenu="showAddContextMenu(event)"
                        onclick="window.location.href='{{ route('cash-advances.create') }}'">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Cash Advance
                </button>
                
                <!-- Add Context Menu -->
                <div id="addContextMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-xl border border-gray-200 py-1 z-50 hidden">
                    <a href="{{ route('cash-advances.create') }}" 
                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Submit New Request
                    </a>
                    <a href="{{ route('cash-advances.index', ['status' => 'pending']) }}" 
                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        View Pending Requests
                    </a>
                    @can('approve cash advances')
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="{{ route('cash-advances.index', ['status' => 'approved']) }}" 
                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        View Approved Requests
                    </a>
                    @endcan
                </div>
            </div>
            @endcan
        </div>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('cash-advances.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="fully_paid" {{ request('status') === 'fully_paid' ? 'selected' : '' }}>Fully Paid</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        
                        @can('view cash advances')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee</label>
                            <select name="employee_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endcan

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date To</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div class="flex items-end space-x-2">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Filter
                            </button>
                            <a href="{{ route('cash-advances.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cash Advances List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Cash Advances</h3>
                        <div class="text-sm text-gray-600">
                            <div>Showing {{ $cashAdvances->count() }} of {{ $cashAdvances->total() }} cash advances</div>
                            <div class="text-xs text-blue-600 mt-1">
                                <strong>Tip:</strong> Right-click on any cash advance row to access View, Approve, and Delete actions.
                            </div>
                        </div>
                    </div>

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
                                        Requested Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Approved Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Monthly Deduction
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Outstanding Balance
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($cashAdvances as $cashAdvance)
                                <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
                                   oncontextmenu="showContextMenu(event, '{{ $cashAdvance->id }}', '{{ $cashAdvance->reference_number }}', '{{ $cashAdvance->employee->full_name }}', '{{ $cashAdvance->status }}')"
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
                                        <div class="text-sm text-gray-900">
                                            ₱{{ number_format($cashAdvance->requested_amount, 2) }}
                                        </div>
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
                                                <div class="text-xs text-red-600">
                                                    Total: ₱{{ number_format($cashAdvance->total_amount, 2) }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-sm text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($cashAdvance->installment_amount)
                                            <div class="text-sm text-blue-600">
                                                ₱{{ number_format($cashAdvance->installment_amount, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                for {{ $cashAdvance->installments }} months
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $cashAdvance->requested_date->format('M d, Y') }}
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-6">
                        <div class="text-sm text-gray-700">
                            Showing {{ $cashAdvances->firstItem() }} to {{ $cashAdvances->lastItem() }} 
                            of {{ $cashAdvances->total() }} results
                        </div>
                        <div>
                            {{ $cashAdvances->appends(request()->query())->links() }}
                        </div>
                    </div>
                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No cash advances found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new cash advance request.</p>
                        @can('create cash advances')
                        <div class="mt-6">
                            <a href="{{ route('cash-advances.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Create First Cash Advance
                            </a>
                        </div>
                        @endcan
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="fixed bg-white rounded-md shadow-xl border border-gray-200 py-1 z-50 hidden min-w-52 backdrop-blur-sm transition-all duration-150 transform opacity-0 scale-95">
        <div id="contextMenuHeader" class="px-3 py-2 border-b border-gray-100 bg-gray-50 rounded-t-md">
            <div class="text-sm font-medium text-gray-900" id="contextMenuCashAdvance"></div>
            <div class="text-xs text-gray-500" id="contextMenuEmployee"></div>
        </div>
        
        <div class="py-1">
            <a href="#" id="contextMenuView" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                View Details
            </a>
            
            @can('approve cash advances')
            <a href="#" id="contextMenuApprove" class="flex items-center px-3 py-2 text-sm text-green-700 hover:bg-green-50 hover:text-green-900 transition-colors duration-150" style="display: none;">
                <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Approve
            </a>
            
            <a href="#" id="contextMenuReject" class="flex items-center px-3 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900 transition-colors duration-150" style="display: none;">
                <svg class="w-4 h-4 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Reject
            </a>
            @endcan
            
            @can('delete cash advances')
            <div class="border-t border-gray-100 my-1"></div>
            <a href="#" id="contextMenuDelete" class="flex items-center px-3 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete
            </a>
            @endcan
        </div>
    </div>

    <script>
        let currentCashAdvanceId = null;
        let currentStatus = null;
        
        // Add Cash Advance Context Menu
        function showAddContextMenu(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const addContextMenu = document.getElementById('addContextMenu');
            addContextMenu.classList.toggle('hidden');
        }
        
        // Hide add context menu when clicking elsewhere
        document.addEventListener('click', function(event) {
            const addContextMenu = document.getElementById('addContextMenu');
            const addBtn = document.getElementById('addCashAdvanceBtn');
            
            if (!addBtn.contains(event.target) && !addContextMenu.contains(event.target)) {
                addContextMenu.classList.add('hidden');
            }
        });
        
        // Cash Advance Row Context Menu
        function showContextMenu(event, id, reference, employee, status) {
            event.preventDefault();
            event.stopPropagation();
            
            currentCashAdvanceId = id;
            currentStatus = status;
            
            // Update context menu content
            document.getElementById('contextMenuCashAdvance').textContent = reference;
            document.getElementById('contextMenuEmployee').textContent = employee;
            
            // Update action URLs
            document.getElementById('contextMenuView').href = '{{ route("cash-advances.index") }}/' + id;
            
            // Show/hide actions based on status and permissions
            @can('approve cash advances')
            if (status === 'pending') {
                document.getElementById('contextMenuApprove').style.display = 'flex';
                document.getElementById('contextMenuReject').style.display = 'flex';
            } else {
                document.getElementById('contextMenuApprove').style.display = 'none';
                document.getElementById('contextMenuReject').style.display = 'none';
            }
            @endcan
            
            // Show context menu
            const contextMenu = document.getElementById('contextMenu');
            contextMenu.style.left = event.pageX + 'px';
            contextMenu.style.top = event.pageY + 'px';
            contextMenu.classList.remove('hidden');
            contextMenu.classList.add('opacity-100', 'scale-100');
            contextMenu.classList.remove('opacity-0', 'scale-95');
        }
        
        // Hide context menu when clicking elsewhere
        document.addEventListener('click', function(event) {
            const contextMenu = document.getElementById('contextMenu');
            if (!contextMenu.contains(event.target)) {
                contextMenu.classList.add('opacity-0', 'scale-95');
                contextMenu.classList.remove('opacity-100', 'scale-100');
                setTimeout(() => {
                    contextMenu.classList.add('hidden');
                }, 150);
            }
        });
        
        // Handle approve action
        document.getElementById('contextMenuApprove').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '{{ route("cash-advances.index") }}/' + currentCashAdvanceId + '?action=approve';
        });
        
        // Handle reject action
        document.getElementById('contextMenuReject').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '{{ route("cash-advances.index") }}/' + currentCashAdvanceId + '?action=reject';
        });
        
        // Handle delete action
        @can('delete cash advances')
        document.getElementById('contextMenuDelete').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this cash advance? This action cannot be undone.')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("cash-advances.index") }}/' + currentCashAdvanceId;
                
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
        @endcan
    </script>
</x-app-layout>

