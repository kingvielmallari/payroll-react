<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                DTR (Daily Time Record) Management
            </h2>
            <div class="flex space-x-2">
                @can('create time logs')
                <a href="{{ route('time-logs.create-bulk') }}" 
                   class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Bulk Time Entry
                </a>
                @endcan
                @can('import time logs')
                <a href="{{ route('time-logs.import-form') }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Import DTR
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Warning Message -->
            @if (session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('warning') }}</span>
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total DTR Batches</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ number_format($totalDTRBatches) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Employees with DTR</dt>
                                    <dd class="text-lg font-medium text-blue-600">{{ number_format($totalEmployeesWithDTR) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Regular Hours</dt>
                                    <dd class="text-lg font-medium text-green-600">{{ number_format($totalRegularHours, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter DTR Batches</h3>
                    <form method="GET" action="{{ route('time-logs.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
                            <select name="employee_id" id="employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700">Department</label>
                            <select name="department_id" id="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Period Start</label>
                            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">Period End</label>
                            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-end space-x-2">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                                Filter
                            </button>
                            <a href="{{ route('time-logs.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition duration-200">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DTR Batches Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">DTR Batch Records</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Complete list of all DTR batch entries in the system. Right-click on any row for actions.
                    </p>
                </div>
                
                @if($dtrBatches->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regular Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regular Days</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payroll</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($dtrBatches as $dtrBatch)
                            <tr class="hover:bg-gray-50 cursor-pointer context-menu-row" 
                                data-dtr-id="{{ $dtrBatch->id }}"
                                data-employee-name="{{ $dtrBatch->first_name }} {{ $dtrBatch->last_name }}"
                                data-period="{{ \Carbon\Carbon::parse($dtrBatch->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($dtrBatch->period_end)->format('M d, Y') }}"
                                onclick="window.location.href='{{ route('time-logs.dtr-batch', $dtrBatch->id) }}'">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $dtrBatch->first_name }} {{ $dtrBatch->last_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $dtrBatch->employee_number }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $dtrBatch->department_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($dtrBatch->period_start)->format('M d') }} - 
                                        {{ \Carbon\Carbon::parse($dtrBatch->period_end)->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($dtrBatch->period_start)->diffInDays(\Carbon\Carbon::parse($dtrBatch->period_end)) + 1 }} days
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="font-medium">{{ number_format($dtrBatch->total_regular_hours, 2) }}h</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dtrBatch->total_overtime_hours > 0)
                                        <span class="font-medium text-blue-600">{{ number_format($dtrBatch->total_overtime_hours, 2) }}h</span>
                                    @else
                                        <span class="text-gray-400">0h</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="font-medium">{{ $dtrBatch->regular_days ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($dtrBatch->payroll_id)
                                        <span class="text-indigo-600 hover:text-indigo-900">
                                            {{ $dtrBatch->period_label ?? 'Payroll #' . $dtrBatch->payroll_id }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">No Payroll</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $dtrBatch->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($dtrBatch->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($dtrBatch->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        {{ $dtrBatches->links('pagination::simple-tailwind') }}
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ $dtrBatches->firstItem() }}</span> to <span class="font-medium">{{ $dtrBatches->lastItem() }}</span> of <span class="font-medium">{{ $dtrBatches->total() }}</span> results
                            </p>
                        </div>
                        <div>
                            {{ $dtrBatches->links() }}
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No DTR batches found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating DTR batches for employees.</p>
                    <div class="mt-6">
                        @can('create time logs')
                        <a href="{{ route('time-logs.create-bulk') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Create Bulk Time Logs
                        </a>
                        @endcan
                    </div>
                </div>
                @endif
            </div>

            <!-- Context Menu -->
            <div id="contextMenu" class="hidden fixed bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                <button onclick="viewDTRBatch()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Details
                </button>
                @can('delete time logs')
                <button onclick="deleteDTRBatch()" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
                @endcan
                <button onclick="viewPayroll()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="viewPayrollBtn">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                    View Payroll
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentDTRId = null;
        let currentRow = null;

        // Context menu functionality
        document.addEventListener('contextmenu', function(e) {
            const row = e.target.closest('.context-menu-row');
            if (row) {
                e.preventDefault();
                currentDTRId = row.dataset.dtrId;
                currentRow = row;
                
                const menu = document.getElementById('contextMenu');
                menu.style.display = 'block';
                menu.style.left = e.pageX + 'px';
                menu.style.top = e.pageY + 'px';
                
                // Hide or show payroll button based on payroll existence
                const hasPayroll = row.querySelector('td:nth-child(7) .text-indigo-600');
                const viewPayrollBtn = document.getElementById('viewPayrollBtn');
                viewPayrollBtn.style.display = hasPayroll ? 'block' : 'none';
            }
        });

        // Hide context menu when clicking elsewhere
        document.addEventListener('click', function() {
            document.getElementById('contextMenu').style.display = 'none';
        });

        // Prevent context menu from closing when clicking on it
        document.getElementById('contextMenu').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        function viewDTRBatch() {
            if (currentDTRId) {
                window.location.href = `{{ route('time-logs.index') }}/dtr-batch/${currentDTRId}`;
            }
            document.getElementById('contextMenu').style.display = 'none';
        }

        function deleteDTRBatch() {
            if (currentDTRId && currentRow) {
                const employeeName = currentRow.dataset.employeeName;
                const period = currentRow.dataset.period;
                
                if (confirm(`Are you sure you want to delete the DTR batch for ${employeeName} (${period})?`)) {
                    // Create a form and submit for deletion
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `{{ route('time-logs.index') }}/dtr-batch/${currentDTRId}`;
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    
                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';
                    
                    form.appendChild(csrfToken);
                    form.appendChild(methodField);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            document.getElementById('contextMenu').style.display = 'none';
        }

        function viewPayroll() {
            if (currentDTRId) {
                window.location.href = `{{ route('time-logs.index') }}/dtr-batch/${currentDTRId}/payroll`;
            }
            document.getElementById('contextMenu').style.display = 'none';
        }

        // Prevent row click when right-clicking
        document.querySelectorAll('.context-menu-row').forEach(row => {
            row.addEventListener('contextmenu', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</x-app-layout>
