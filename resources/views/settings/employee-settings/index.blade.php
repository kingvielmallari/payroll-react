<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee Configuration') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings.employee.update') }}" class="space-y-6">
                @csrf

                <!-- Employee Number Configuration -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Number Configuration</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="employee_number_prefix" class="block text-sm font-medium text-gray-700 mb-2">
                                    Employee Number Prefix
                                </label>
                                <input type="text" name="employee_number_prefix" id="employee_number_prefix" 
                                       value="{{ old('employee_number_prefix', $settings['employee_number_prefix']) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="EMP" maxlength="10">
                                <p class="mt-1 text-xs text-gray-500">Custom prefix for employee numbers (e.g., EMP, CUSTOM, etc.)</p>
                                <p class="mt-1 text-xs text-gray-400">Format will be: PREFIX-YEAR-NUMBER (e.g., {{ old('employee_number_prefix', $settings['employee_number_prefix'] ?? 'EMP') }}-{{ date('Y') }}-0001)</p>
                                @error('employee_number_prefix')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="employee_number_start" class="block text-sm font-medium text-gray-700 mb-2">
                                    Starting Number
                                </label>
                                <input type="number" name="employee_number_start" id="employee_number_start" min="1"
                                       value="{{ old('employee_number_start', $settings['employee_number_start']) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('employee_number_start')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="auto_generate_employee_number" id="auto_generate_employee_number" 
                                       value="1" {{ old('auto_generate_employee_number', $settings['auto_generate_employee_number']) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="auto_generate_employee_number" class="ml-2 text-sm text-gray-700">
                                    Auto-generate employee numbers (if unchecked, users can enter manually)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Department Management</h3>
                            <a href="{{ route('departments.create') }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Add New Department
                            </a>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-3">Manage available departments for employee selection:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse($departments as $department)
                                    <div class="flex items-center justify-between bg-white rounded px-3 py-2 border">
                                        <span class="text-sm">{{ $department->name }}</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs px-2 py-1 rounded {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <a href="{{ route('departments.edit', $department) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-xs">Edit</a>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 col-span-full">No departments found. Add the first department to get started.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Position Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Position Management</h3>
                            <a href="{{ route('positions.create') }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Add New Position
                            </a>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-3">Manage available positions for employee selection:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse($positions as $position)
                                    <div class="flex items-center justify-between bg-white rounded px-3 py-2 border">
                                        <div>
                                            <span class="text-sm font-medium">{{ $position->title }}</span>
                                            @if($position->department)
                                                <br><span class="text-xs text-gray-500">{{ $position->department->name }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs px-2 py-1 rounded {{ $position->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $position->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <a href="{{ route('positions.edit', $position) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-xs">Edit</a>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 col-span-full">No positions found. Add the first position to get started.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <!-- Time Schedule Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Time Schedule Management</h3>
                            <button type="button" onclick="openCreateTimeSchedule()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Add New Time Schedule
                            </button>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-3">Manage available time schedules for employee selection:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @forelse($timeSchedules as $schedule)
                                    <div class="flex items-center justify-between bg-white rounded px-3 py-2 border">
                                        <div>
                                            <span class="text-sm font-medium">{{ $schedule->name }}</span>
                                            <br><span class="text-xs text-gray-500">{{ $schedule->time_range }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs px-2 py-1 rounded {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <button type="button" onclick="editTimeSchedule({{ $schedule->id }})" 
                                                    class="text-blue-600 hover:text-blue-800 text-xs">Edit</button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 col-span-full">No time schedules found. Add the first time schedule to get started.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Day Schedule Management -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Day Schedule Management</h3>
                            <button type="button" onclick="openCreateDaySchedule()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Add New Day Schedule
                            </button>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-3">Manage available day schedules for employee selection:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @forelse($daySchedules as $schedule)
                                    <div class="flex items-center justify-between bg-white rounded px-3 py-2 border">
                                        <div>
                                            <span class="text-sm font-medium">{{ $schedule->name }}</span>
                                            <br><span class="text-xs text-gray-500">{{ $schedule->days_display }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs px-2 py-1 rounded {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <button type="button" onclick="editDaySchedule({{ $schedule->id }})" 
                                                    class="text-blue-600 hover:text-blue-800 text-xs">Edit</button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 col-span-full">No day schedules found. Add the first day schedule to get started.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div> --}}

                <!-- Field Requirements -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Field Requirements</h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="require_department" id="require_department" 
                                       value="1" {{ old('require_department', $settings['require_department']) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="require_department" class="ml-2 text-sm text-gray-700">
                                    Require Department selection
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="require_position" id="require_position" 
                                       value="1" {{ old('require_position', $settings['require_position']) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="require_position" class="ml-2 text-sm text-gray-700">
                                    Require Position selection
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="require_time_schedule" id="require_time_schedule" 
                                       value="1" {{ old('require_time_schedule', $settings['require_time_schedule']) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="require_time_schedule" class="ml-2 text-sm text-gray-700">
                                    Require Time Schedule selection
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Save Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Time Schedule Modal (Create/Edit) -->
    <div id="timeScheduleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="timeScheduleModalTitle">Add Time Schedule</h3>
                <form id="timeScheduleForm">
                    <input type="hidden" id="timeScheduleId" name="id">
                    <div class="space-y-4">
                        <div>
                            <label for="timeScheduleName" class="block text-sm font-medium text-gray-700">Schedule Name</label>
                            <input type="text" id="timeScheduleName" name="name" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="timeIn" class="block text-sm font-medium text-gray-700">Time In</label>
                                <input type="time" id="timeIn" name="time_in" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="timeOut" class="block text-sm font-medium text-gray-700">Time Out</label>
                                <input type="time" id="timeOut" name="time_out" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="breakStart" class="block text-sm font-medium text-gray-700">Break Start</label>
                                <input type="time" id="breakStart" name="break_start" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="breakEnd" class="block text-sm font-medium text-gray-700">Break End</label>
                                <input type="time" id="breakEnd" name="break_end" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeTimeScheduleModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Day Schedule Modal (Create/Edit) -->
    <div id="dayScheduleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="dayScheduleModalTitle">Add Day Schedule</h3>
                <form id="dayScheduleForm">
                    <input type="hidden" id="dayScheduleId" name="id">
                    <div class="space-y-4">
                        <div>
                            <label for="dayScheduleName" class="block text-sm font-medium text-gray-700">Schedule Name</label>
                            <input type="text" id="dayScheduleName" name="name" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Working Days</label>
                            <div class="space-y-2">
                                <label class="flex items-center"><input type="checkbox" name="monday" id="monday" class="mr-2"> Monday</label>
                                <label class="flex items-center"><input type="checkbox" name="tuesday" id="tuesday" class="mr-2"> Tuesday</label>
                                <label class="flex items-center"><input type="checkbox" name="wednesday" id="wednesday" class="mr-2"> Wednesday</label>
                                <label class="flex items-center"><input type="checkbox" name="thursday" id="thursday" class="mr-2"> Thursday</label>
                                <label class="flex items-center"><input type="checkbox" name="friday" id="friday" class="mr-2"> Friday</label>
                                <label class="flex items-center"><input type="checkbox" name="saturday" id="saturday" class="mr-2"> Saturday</label>
                                <label class="flex items-center"><input type="checkbox" name="sunday" id="sunday" class="mr-2"> Sunday</label>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeDayScheduleModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Time Schedule Functions
        function openCreateTimeSchedule() {
            document.getElementById('timeScheduleModalTitle').textContent = 'Add Time Schedule';
            document.getElementById('timeScheduleForm').reset();
            document.getElementById('timeScheduleId').value = '';
            document.getElementById('timeScheduleModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function editTimeSchedule(id) {
            // Fetch schedule data and populate form
            // Implementation would require an AJAX call to get schedule details
            document.getElementById('timeScheduleModalTitle').textContent = 'Edit Time Schedule';
            document.getElementById('timeScheduleId').value = id;
            document.getElementById('timeScheduleModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeTimeScheduleModal() {
            document.getElementById('timeScheduleModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Day Schedule Functions
        function openCreateDaySchedule() {
            document.getElementById('dayScheduleModalTitle').textContent = 'Add Day Schedule';
            document.getElementById('dayScheduleForm').reset();
            document.getElementById('dayScheduleId').value = '';
            document.getElementById('dayScheduleModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function editDaySchedule(id) {
            // Fetch schedule data and populate form
            // Implementation would require an AJAX call to get schedule details
            document.getElementById('dayScheduleModalTitle').textContent = 'Edit Day Schedule';
            document.getElementById('dayScheduleId').value = id;
            document.getElementById('dayScheduleModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeDayScheduleModal() {
            document.getElementById('dayScheduleModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Form Submissions
        document.getElementById('timeScheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Handle time schedule save
            // Implementation would submit via AJAX
            alert('Time schedule save functionality to be implemented');
        });

        document.getElementById('dayScheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Handle day schedule save
            // Implementation would submit via AJAX
            alert('Day schedule save functionality to be implemented');
        });
    </script>
</x-app-layout>
