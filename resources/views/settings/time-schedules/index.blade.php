<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Time Schedule Management') }}
            </h2>
            <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Time Schedule
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Success/Error Messages -->
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Time Schedules Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="py-2 px-4 border-b text-left">ID</th>
                                    <th class="py-2 px-4 border-b text-left">Schedule Name</th>
                                    <th class="py-2 px-4 border-b text-left">Time In</th>
                                    <th class="py-2 px-4 border-b text-left">Time Out</th>
                                    <th class="py-2 px-4 border-b text-left">Break Hours</th>
                                    <th class="py-2 px-4 border-b text-left">Total Hours</th>
                                    <th class="py-2 px-4 border-b text-left">Status</th>
                                    <th class="py-2 px-4 border-b text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timeSchedules as $schedule)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-4 border-b">{{ $schedule->id }}</td>
                                        <td class="py-2 px-4 border-b font-medium">{{ $schedule->name }}</td>
                                        <td class="py-2 px-4 border-b">{{ $schedule->time_in }}</td>
                                        <td class="py-2 px-4 border-b">{{ $schedule->time_out }}</td>
                                        <td class="py-2 px-4 border-b">{{ $schedule->break_hours ?? 'N/A' }}</td>
                                        <td class="py-2 px-4 border-b">{{ $schedule->total_hours ?? 'N/A' }}</td>
                                        <td class="py-2 px-4 border-b">
                                            <span class="px-2 py-1 text-xs rounded {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            <button onclick="openEditModal({{ $schedule->id }})" 
                                                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-sm mr-2">
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('time-schedules.destroy', $schedule) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        onclick="return confirm('Are you sure you want to delete this time schedule?')"
                                                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-sm">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-4 px-4 text-center text-gray-500">
                                            No time schedules found. <button onclick="openAddModal()" class="text-blue-500 hover:underline">Add the first time schedule</button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="timeScheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add Time Schedule</h3>
                
                <form id="timeScheduleForm" method="POST">
                    @csrf
                    <div id="methodField"></div>
                    <input type="hidden" id="break_option_value" name="break_option" value="none">
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Schedule Name</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required 
                               placeholder="e.g., Morning Shift"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="time_in" class="block text-sm font-medium text-gray-700">Time In</label>
                        <input type="time" 
                               id="time_in" 
                               name="time_in" 
                               required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="time_out" class="block text-sm font-medium text-gray-700">Time Out</label>
                        <input type="time" 
                               id="time_out" 
                               name="time_out" 
                               required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <!-- Break Period Section -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Break Period (choose one option)</label>
                        
                        <!-- Break Period Options -->
                        <div class="space-y-3">
                            <!-- Option 1: Break Duration -->
                            <div class="border rounded-lg p-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" 
                                           name="break_option" 
                                           value="duration" 
                                           id="break_option_duration"
                                           class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Break Duration (flexible timing)</span>
                                </label>
                                <div class="mt-2 ml-6" id="break_duration_section" style="display: none;">
                                    <label for="break_duration_minutes" class="block text-xs text-gray-600">Duration in minutes</label>
                                    <input type="number" 
                                           id="break_duration_minutes" 
                                           name="break_duration_minutes" 
                                           min="0" 
                                           max="480" 
                                           placeholder="60"
                                           class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Employee can take break anytime within shift</p>
                                </div>
                            </div>
                            
                            <!-- Option 2: Fixed Break Times -->
                            <div class="border rounded-lg p-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" 
                                           name="break_option" 
                                           value="fixed" 
                                           id="break_option_fixed"
                                           class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Fixed Break Times</span>
                                </label>
                                <div class="mt-2 ml-6 grid grid-cols-2 gap-3" id="break_times_section" style="display: none;">
                                    <div>
                                        <label for="break_start" class="block text-xs text-gray-600">Break Start</label>
                                        <input type="time" 
                                               id="break_start" 
                                               name="break_start" 
                                               class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="break_end" class="block text-xs text-gray-600">Break End</label>
                                        <input type="time" 
                                               id="break_end" 
                                               name="break_end" 
                                               class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Option 3: No Break -->
                            <div class="border rounded-lg p-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" 
                                           name="break_option" 
                                           value="none" 
                                           id="break_option_none"
                                           class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">No Break Period</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="total_hours" class="block text-sm font-medium text-gray-700">Total Hours (optional)</label>
                        <input type="number" 
                               id="total_hours" 
                               name="total_hours" 
                               step="0.5"
                               min="0"
                               max="24"
                               placeholder="8.0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1" 
                                   checked
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" 
                                onclick="closeModal()"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Break option handling
        document.addEventListener('DOMContentLoaded', function() {
            const breakOptions = document.querySelectorAll('input[name="break_option"]');
            const breakDurationSection = document.getElementById('break_duration_section');
            const breakTimesSection = document.getElementById('break_times_section');
            const breakOptionValue = document.getElementById('break_option_value');
            
            breakOptions.forEach(option => {
                option.addEventListener('change', function() {
                    // Hide all sections first
                    breakDurationSection.style.display = 'none';
                    breakTimesSection.style.display = 'none';
                    
                    // Clear values when switching options
                    document.getElementById('break_duration_minutes').value = '';
                    document.getElementById('break_start').value = '';
                    document.getElementById('break_end').value = '';
                    
                    // Update hidden field value
                    breakOptionValue.value = this.value;
                    
                    // Show appropriate section based on selection
                    if (this.value === 'duration') {
                        breakDurationSection.style.display = 'block';
                    } else if (this.value === 'fixed') {
                        breakTimesSection.style.display = 'block';
                    }
                });
            });
        });
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Time Schedule';
            document.getElementById('timeScheduleForm').action = '{{ route("time-schedules.store") }}';
            document.getElementById('methodField').innerHTML = '';
            resetFormFields();
            document.getElementById('timeScheduleModal').classList.remove('hidden');
        }

        function openEditModal(scheduleId) {
            // Fetch schedule data
            fetch(`/time-schedules/${scheduleId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').textContent = 'Edit Time Schedule';
                    document.getElementById('timeScheduleForm').action = `/time-schedules/${scheduleId}`;
                    document.getElementById('methodField').innerHTML = '@method("PUT")';
                    
                    // Fill basic fields
                    document.getElementById('name').value = data.name;
                    document.getElementById('time_in').value = data.time_in;
                    document.getElementById('time_out').value = data.time_out;
                    document.getElementById('total_hours').value = data.total_hours || '';
                    document.getElementById('is_active').checked = data.is_active;
                    
                    // Handle break options based on existing data
                    if (data.break_duration_minutes) {
                        document.getElementById('break_option_duration').checked = true;
                        document.getElementById('break_option_value').value = 'duration';
                        document.getElementById('break_duration_minutes').value = data.break_duration_minutes;
                        document.getElementById('break_duration_section').style.display = 'block';
                        document.getElementById('break_times_section').style.display = 'none';
                    } else if (data.break_start && data.break_end) {
                        document.getElementById('break_option_fixed').checked = true;
                        document.getElementById('break_option_value').value = 'fixed';
                        document.getElementById('break_start').value = data.break_start;
                        document.getElementById('break_end').value = data.break_end;
                        document.getElementById('break_times_section').style.display = 'block';
                        document.getElementById('break_duration_section').style.display = 'none';
                    } else {
                        document.getElementById('break_option_none').checked = true;
                        document.getElementById('break_option_value').value = 'none';
                        document.getElementById('break_duration_section').style.display = 'none';
                        document.getElementById('break_times_section').style.display = 'none';
                    }
                    
                    document.getElementById('timeScheduleModal').classList.remove('hidden');
                });
        }
        
        function resetFormFields() {
            document.getElementById('name').value = '';
            document.getElementById('time_in').value = '';
            document.getElementById('time_out').value = '';
            document.getElementById('break_duration_minutes').value = '';
            document.getElementById('break_start').value = '';
            document.getElementById('break_end').value = '';
            document.getElementById('total_hours').value = '';
            document.getElementById('is_active').checked = true;
            
            // Reset break options
            document.getElementById('break_option_none').checked = true;
            document.getElementById('break_option_value').value = 'none';
            document.getElementById('break_duration_section').style.display = 'none';
            document.getElementById('break_times_section').style.display = 'none';
        }

        function closeModal() {
            document.getElementById('timeScheduleModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('timeScheduleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Form submission handler
        document.getElementById('timeScheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.action;
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    alert(data.message);
                    if (data.message.includes('successfully')) {
                        closeModal();
                        location.reload();
                    }
                }
                if (data.errors) {
                    let errorMessage = 'Validation errors:\n';
                    Object.values(data.errors).forEach(errors => {
                        errors.forEach(error => {
                            errorMessage += `- ${error}\n`;
                        });
                    });
                    alert(errorMessage);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the time schedule.');
            });
        });
    </script>
</x-app-layout>
