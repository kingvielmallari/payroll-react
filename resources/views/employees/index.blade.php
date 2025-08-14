<x-app-layout>
   

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Quick Stats -->
       
            
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-48">
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                   placeholder="Name, Employee #, Email" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div class="flex-1 min-w-40">
                            <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                            <select name="department" id="department" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ request('department') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="employment_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="employment_status" id="employment_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Status</option>
                                <option value="active" {{ request('employment_status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('employment_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="terminated" {{ request('employment_status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                <option value="resigned" {{ request('employment_status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="sort_name" class="block text-sm font-medium text-gray-700">Sort Name</label>
                            <select name="sort_name" id="sort_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Default</option>
                                <option value="asc" {{ request('sort_name') == 'asc' ? 'selected' : '' }}>A-Z</option>
                                <option value="desc" {{ request('sort_name') == 'desc' ? 'selected' : '' }}>Z-A</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="sort_hire_date" class="block text-sm font-medium text-gray-700">Employment</label>
                            <select name="sort_hire_date" id="sort_hire_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Default</option>
                                <option value="desc" {{ request('sort_hire_date') == 'desc' ? 'selected' : '' }}>New-Old</option>
                                <option value="asc" {{ request('sort_hire_date') == 'asc' ? 'selected' : '' }}>Old-New</option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Filter
                            </button>
                            <a href="{{ route('employees.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Clear
                            </a>
                        </div>
                    </form>
                    
                    <!-- Records per page selector -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <label for="per_page" class="text-sm font-medium text-gray-700">Records per page:</label>
                            <select name="per_page" id="per_page" onchange="updatePerPage(this.value)" 
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-gray-500">
                                Total: {{ $employees->total() }} employees
                            </div>
                            <a href="{{ route('employees.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Add Employee
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($employees->count() > 0)

                        <!-- Employees Header and Tip -->
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-900">Employees </h2>
                            <div class="flex items-center bg-blue-50 border-l-4 border-blue-400 rounded p-2">
                                <svg class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-xs text-blue-700"><strong>Tip:</strong> Click on any employee row to view details | Right-click for Edit, Delete and other actions.</span>
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
                                            <span class="text-gray-600 font-medium">
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
                                            Status
                                        </th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Pay Rate
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
                                            <td class="px-3 py-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $employee->employment_status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $employee->employment_status === 'inactive' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $employee->employment_status === 'terminated' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $employee->employment_status === 'resigned' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                    {{ ucfirst($employee->employment_status) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-sm">
                                                <div class="text-gray-900 font-medium">
                                                    {{ ucwords(str_replace('_', '-', $employee->pay_schedule)) }}
                                                </div>
                                                <div class="text-gray-500">
                                                    @if($employee->pay_schedule === 'weekly')
                                                        ₱{{ number_format($employee->weekly_rate ?? 0, 2) }}
                                                    @elseif($employee->pay_schedule === 'semi_monthly')
                                                        ₱{{ number_format($employee->semi_monthly_rate ?? 0, 2) }}
                                                    @else
                                                        ₱{{ number_format($employee->basic_salary, 2) }}
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
                            <div class="flex items-center justify-between mb-5">
                                <div class="text-sm text-gray-700">
                                    Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} employees
                                </div>
                                <div class="text-sm text-gray-500">
                                    Page {{ $employees->currentPage() }} of {{ $employees->lastPage() }}
                                </div>
                            </div>
                            {{ $employees->links() }}
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
                </div>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="fixed bg-white rounded-md shadow-xl border border-gray-200 py-1 z-50 hidden min-w-48 backdrop-blur-sm transition-all duration-150 transform opacity-0 scale-95">
        <div id="contextMenuHeader" class="px-3 py-2 border-b border-gray-100 bg-gray-50 rounded-t-md">
            <div class="text-sm font-medium text-gray-900" id="contextMenuName"></div>
            <div class="text-xs text-gray-500" id="contextMenuEmpId"></div>
        </div>
        <div class="py-1">
            <a href="#" id="contextMenuView" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                View Details
            </a>
            @can('edit employees')
            <a href="#" id="contextMenuEdit" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit Employee
            </a>
            @endcan
            @can('view employees')
            <div class="border-t border-gray-100 my-1"></div>
            <a href="#" id="contextMenuViewPayroll" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                View Payroll History
            </a>
            @endcan
            @can('delete employees')
            <div class="border-t border-gray-100 my-1"></div>
            <a href="#" id="contextMenuDelete" class="flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete Employee
            </a>
            @endcan
        </div>
    </div>

    <script>
        let currentEmployeeId = null;
        let contextMenu = document.getElementById('contextMenu');
        
        // Pre-generate route templates using URL helper
        const baseUrl = "{{ url('/employees') }}";
        const payrollHistoryTemplate = "{{ route('payrolls.index') }}?employee_number=";

        function showContextMenu(event, employeeNumber, employeeName, employeeDisplayNumber) {
            event.preventDefault();
            event.stopPropagation();
            
            currentEmployeeId = employeeNumber;
            
            // Get the clicked row/card to check user role
            const clickedElement = event.target.closest('[data-employee-id]');
            const userRole = clickedElement ? clickedElement.getAttribute('data-user-role') : '';
            
            // Update context menu header
            document.getElementById('contextMenuName').textContent = employeeName;
            document.getElementById('contextMenuEmpId').textContent = employeeDisplayNumber;
            
            // Convert employee number to lowercase for URLs
            const lowercaseEmployeeNumber = employeeNumber.toLowerCase();
            
            // Update links with proper Laravel routes
            document.getElementById('contextMenuView').href = baseUrl + '/' + lowercaseEmployeeNumber;
            @can('edit employees')
            document.getElementById('contextMenuEdit').href = baseUrl + '/' + lowercaseEmployeeNumber + '/edit';
            @endcan
            @can('view employees')
            document.getElementById('contextMenuViewPayroll').href = payrollHistoryTemplate + employeeNumber;
            @endcan
            
            // Show/hide delete option based on user role
            @can('delete employees')
            const deleteOption = document.getElementById('contextMenuDelete');
            if (userRole === 'System Admin') {
                deleteOption.style.display = 'none';
            } else {
                deleteOption.style.display = 'flex';
            }
            @endcan
            
            // Get exact mouse position
            const mouseX = event.clientX;
            const mouseY = event.clientY;
            
            // Position context menu at mouse cursor initially
            contextMenu.style.left = mouseX + 'px';
            contextMenu.style.top = mouseY + 'px';
            contextMenu.classList.remove('hidden');
            
            // Show with animation
            setTimeout(() => {
                contextMenu.classList.remove('opacity-0', 'scale-95');
                contextMenu.classList.add('opacity-100', 'scale-100');
            }, 10);
            
            // Adjust position to prevent menu from going off-screen
            setTimeout(() => {
                const menuRect = contextMenu.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                
                let adjustedX = mouseX;
                let adjustedY = mouseY;
                
                // Adjust horizontal position if menu goes off right edge
                if (mouseX + menuRect.width > viewportWidth) {
                    adjustedX = mouseX - menuRect.width;
                }
                
                // Adjust vertical position if menu goes off bottom edge  
                if (mouseY + menuRect.height > viewportHeight) {
                    adjustedY = mouseY - menuRect.height;
                }
                
                // Ensure menu doesn't go off left or top edges
                adjustedX = Math.max(0, adjustedX);
                adjustedY = Math.max(0, adjustedY);
                
                contextMenu.style.left = adjustedX + 'px';
                contextMenu.style.top = adjustedY + 'px';
            }, 1);
        }

        // Helper function to hide context menu with animation
        function hideContextMenu() {
            contextMenu.classList.remove('opacity-100', 'scale-100');
            contextMenu.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                contextMenu.classList.add('hidden');
            }, 150);
        }

        // Hide context menu when clicking elsewhere or pressing Escape
        document.addEventListener('click', function(event) {
            if (!contextMenu.contains(event.target)) {
                hideContextMenu();
            }
        });

        // Hide context menu on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideContextMenu();
            }
        });

        // Hide context menu on scroll
        document.addEventListener('scroll', function() {
            hideContextMenu();
        }, true); // Use capture to catch all scroll events

        // Hide context menu on window resize
        window.addEventListener('resize', function() {
            hideContextMenu();
        });

        // Handle delete action
        @can('delete employees')
        document.getElementById('contextMenuDelete').addEventListener('click', function(event) {
            event.preventDefault();
            
            if (currentEmployeeId && confirm('Are you sure you want to delete this employee?')) {
                // Create and submit delete form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = baseUrl + '/' + currentEmployeeId.toLowerCase();
                
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
            
            hideContextMenu();
        });
        @endcan

        // Prevent default context menu on right-click
        document.addEventListener('contextmenu', function(event) {
            if (event.target.closest('[data-employee-id]')) {
                event.preventDefault();
            }
        });

        // Handle per page selection
        function updatePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.delete('page'); // Reset to first page
            window.location.href = url.toString();
        }
    </script>
</x-app-layout>
