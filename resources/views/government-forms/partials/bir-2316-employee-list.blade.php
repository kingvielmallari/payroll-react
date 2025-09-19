@if($employees->count() > 0)
    <!-- Pagination Controls -->
    <div class="flex justify-between items-center mb-4 px-6 py-3 bg-gray-50 border-b">
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <label for="per_page" class="text-sm font-medium text-gray-700">Records per page:</label>
                <select name="per_page" id="per_page" onchange="updatePerPage(this.value)" 
                        class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
        <div class="text-sm text-gray-700">
            Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} results
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($employees as $employee)
                    <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
                       oncontextmenu="showContextMenu(event, '{{ $employee->employee_number }}', '{{ $employee->employee_number }}', '{{ $employee->first_name }} {{ $employee->last_name }}', '{{ $year }}')"
                       title="Right-click for download actions">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $employee->employee_number }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="text-sm font-medium text-gray-900">{{ $employee->position->title ?? 'N/A' }}</div>
                            <div class="text-sm text-gray-500">{{ $employee->department->name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="text-sm font-medium text-gray-900">Regular</div>
                            <div class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $employees->links() }}
    </div>
@else
    <div class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No active employees found</h3>
        <p class="mt-1 text-sm text-gray-500">
            There are no active employees to generate BIR Forms for.
        </p>
    </div>
@endif