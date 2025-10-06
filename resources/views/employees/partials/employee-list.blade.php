@if($employees->count() > 0)
    <div>
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Employees </h2>
                <div class="flex items-center bg-blue-50 border-l-4 border-blue-400 rounded p-2">
                    <svg class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-xs text-blue-700"><strong>Tip:</strong> Click on any employee row to view details | Right-click for Edit, Delete and other actions.</span>
                </div>
            </div>
        </div>
        
        <!-- Responsive Card Layout for Mobile -->
        <div class="block md:hidden space-y-4">
            @foreach($employees as $employee)
                <div class="bg-gray-50 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition-colors duration-150" 
                     oncontextmenu="showContextMenu(event, '{{ $employee->employee_number }}', '{{ $employee->full_name }}', '{{ $employee->employee_number }}')"
                     onclick="window.location.href='{{ route('employees.show', $employee) }}'"
                     data-employee-id="{{ $employee->id }}"
                     data-user-role="{{ $employee->user->roles->first()?->name ?? 'Employee' }}"
                     title="Click to view details | Right-click for more actions">
                    <div class="flex items-center mb-3">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-700">
                                    {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $employee->employee_number }}</div>
                        </div>
                        <div class="ml-auto">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $employee->employment_status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $employee->employment_status === 'inactive' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $employee->employment_status === 'terminated' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $employee->employment_status === 'resigned' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($employee->employment_status) }}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-gray-500">Role:</span> {{ $employee->position->title }}<br><span class="text-gray-400 text-xs">{{ $employee->department->name }}</span></div>
                        <div><span class="text-gray-500">Type:</span> {{ ucfirst($employee->employment_type) }}</div>
                        <div><span class="text-gray-500">Hired:</span> {{ $employee->hire_date->format('M d, Y') }}</div>
                        <div>
                            <span class="text-gray-500">Pay:</span> {{ ucwords(str_replace('_', '-', $employee->pay_schedule)) }}<br>
                            <span class="text-gray-600 mr-1 font-medium">
                                @if($employee->pay_schedule === 'weekly')
                                    ₱{{ number_format($employee->weekly_rate ?? 0, 2) }}
                                @elseif($employee->pay_schedule === 'semi_monthly')
                                    ₱{{ number_format($employee->semi_monthly_rate ?? 0, 2) }}
                                @else
                                    ₱{{ number_format($employee->basic_salary, 2) }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Table Layout for Desktop -->
        <div class="hidden md:block">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Employee
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Position
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Employment
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Pay Rate
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($employees as $employee)
                        <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
                            oncontextmenu="showContextMenu(event, '{{ $employee->employee_number }}', '{{ $employee->full_name }}', '{{ $employee->employee_number }}')"
                            onclick="window.location.href='{{ route('employees.show', $employee) }}'"
                            data-employee-id="{{ $employee->id }}"
                            data-user-role="{{ $employee->user->roles->first()?->name ?? 'Employee' }}"
                            title="Click to view details | Right-click for more actions">
                            <td class="px-3 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">
                                                {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $employee->full_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $employee->employee_number }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $employee->position->title }}</div>
                                <div class="text-xs text-gray-500">{{ $employee->department->name }}</div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-900">{{ ucfirst($employee->employment_type) }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $employee->hire_date->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="px-3 py-4 text-sm">
                                <div class="text-gray-900 font-medium">
                                    {{ ucwords(str_replace('_', '-', $employee->pay_schedule)) }}
                                </div>
                                <div class="text-gray-500">
                                    ₱{{ number_format($employee->fixed_rate ?? 0, 2) }}/{{ $employee->rate_type ?? 'hour' }}
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $employee->employment_status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $employee->employment_status === 'inactive' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $employee->employment_status === 'terminated' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $employee->employment_status === 'resigned' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($employee->employment_status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
@else
    <div class="text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">👥</div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No employees found</h3>
        <p class="text-gray-500 mb-4">Get started by adding your first employee.</p>
        @can('create employees')
            <a href="{{ route('employees.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Employee
            </a>
        @endcan
    </div>
@endif