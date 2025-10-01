<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Paid Leaves') }}
            </h2>
            @can('create paid leaves')
            <div class="relative">
                <button id="addPaidLeaveBtn" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        oncontextmenu="showAddContextMenu(event)"
                        onclick="window.location.href='{{ route('paid-leaves.create') }}'">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Paid Leave
                </button>
                
                <!-- Add Context Menu -->
                <div id="addContextMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-xl border border-gray-200 py-1 z-50 hidden">
                    <a href="{{ route('paid-leaves.create') }}" 
                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Submit New Request
                    </a>
                    <a href="{{ route('paid-leaves.index', ['status' => 'pending']) }}" 
                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        View Pending Requests
                    </a>
                    @can('approve paid leaves')
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="{{ route('paid-leaves.index', ['status' => 'approved']) }}" 
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
                    <!-- Filter Inputs in 1 Row -->
                    <div class="flex flex-wrap items-end gap-4 mb-4 w-full">
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700">Name Search</label>
                            <input type="text" name="name_search" id="name_search" value="{{ request('name_search') }}" 
                                   placeholder="Search employee name..."
                                   class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm paid-leave-filter focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm paid-leave-filter focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700">Leave Type</label>
                            <select name="leave_type" id="leave_type" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm paid-leave-filter focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types</option>
                                <option value="sick_leave" {{ request('leave_type') === 'sick_leave' ? 'selected' : '' }}>Sick Leave</option>
                                <option value="vacation_leave" {{ request('leave_type') === 'vacation_leave' ? 'selected' : '' }}>Vacation Leave</option>
                                <option value="emergency_leave" {{ request('leave_type') === 'emergency_leave' ? 'selected' : '' }}>Emergency Leave</option>
                                <option value="maternity_leave" {{ request('leave_type') === 'maternity_leave' ? 'selected' : '' }}>Maternity Leave</option>
                                <option value="paternity_leave" {{ request('leave_type') === 'paternity_leave' ? 'selected' : '' }}>Paternity Leave</option>
                                <option value="bereavement_leave" {{ request('leave_type') === 'bereavement_leave' ? 'selected' : '' }}>Bereavement Leave</option>
                            </select>
                        </div>

                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" 
                                   class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm paid-leave-filter focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" 
                                   class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm paid-leave-filter focus:border-indigo-500 focus:ring-indigo-500">
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
                                Generate Paid Leave Summary
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Approved Amount</dt>
                                    <dd class="text-lg font-medium text-gray-900">₱{{ number_format($totalApprovedAmount ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Pending Amount</dt>
                                    <dd class="text-lg font-medium text-gray-900">₱{{ number_format($totalPendingAmount ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $totalRequests ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paid Leaves Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Paid Leaves</h3>
                    
                    @if($paidLeaves->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Period</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Days</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($paidLeaves as $paidLeave)
                                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location.href='{{ route('paid-leaves.show', $paidLeave) }}'">
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
                                            {{ $paidLeave->start_date->format('M d, Y') }} - {{ $paidLeave->end_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $paidLeave->total_days }} {{ Str::plural('day', $paidLeave->total_days) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ₱{{ number_format($paidLeave->total_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {!! $paidLeave->status_badge !!}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('paid-leaves.show', $paidLeave) }}" 
                                                   class="text-blue-600 hover:text-blue-900 transition-colors">
                                                    View
                                                </a>
                                                @if($paidLeave->status === 'pending')
                                                    <a href="{{ route('paid-leaves.edit', $paidLeave) }}" 
                                                       class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                                        Edit
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $paidLeaves->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No paid leaves found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a new paid leave request.</p>
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
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit filters on change
            document.querySelectorAll('.paid-leave-filter').forEach(element => {
                element.addEventListener('change', function() {
                    applyFilters();
                });
            });

            // Reset filters
            document.getElementById('reset_filters').addEventListener('click', function() {
                const url = new URL(window.location.href);
                url.search = '';
                window.location.href = url.toString();
            });

            // Context menu functionality
            document.addEventListener('click', function(e) {
                const contextMenu = document.getElementById('addContextMenu');
                if (!e.target.closest('#addPaidLeaveBtn') && !e.target.closest('#addContextMenu')) {
                    contextMenu.classList.add('hidden');
                }
            });

            // Generate summary
            document.getElementById('generate_summary').addEventListener('click', function() {
                // Implement summary generation
                alert('Paid Leave Summary generation will be implemented');
            });
        });

        function showAddContextMenu(event) {
            event.preventDefault();
            const contextMenu = document.getElementById('addContextMenu');
            contextMenu.classList.toggle('hidden');
        }

        function applyFilters() {
            const form = new FormData();
            document.querySelectorAll('.paid-leave-filter').forEach(element => {
                if (element.value) {
                    form.append(element.name, element.value);
                }
            });

            const params = new URLSearchParams(form);
            const url = new URL(window.location.href);
            url.search = params.toString();
            window.location.href = url.toString();
        }
    </script>
</x-app-layout>