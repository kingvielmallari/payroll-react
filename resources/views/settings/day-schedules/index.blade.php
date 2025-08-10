<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Day Schedule Management') }}
            </h2>
            <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Day Schedule
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

                    <!-- Day Schedules Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="py-2 px-4 border-b text-left">ID</th>
                                    <th class="py-2 px-4 border-b text-left">Schedule Name</th>
                                    <th class="py-2 px-4 border-b text-left">Working Days</th>
                                    <th class="py-2 px-4 border-b text-left">Status</th>
                                    <th class="py-2 px-4 border-b text-left">Created</th>
                                    <th class="py-2 px-4 border-b text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($daySchedules as $schedule)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-4 border-b">{{ $schedule->id }}</td>
                                        <td class="py-2 px-4 border-b font-medium">{{ $schedule->name }}</td>
                                        <td class="py-2 px-4 border-b">
                                            <div class="flex flex-wrap gap-1">
                                                @if($schedule->monday) <span class="px-1 py-0 text-xs bg-blue-100 text-blue-800 rounded">Mon</span> @endif
                                                @if($schedule->tuesday) <span class="px-1 py-0 text-xs bg-blue-100 text-blue-800 rounded">Tue</span> @endif
                                                @if($schedule->wednesday) <span class="px-1 py-0 text-xs bg-blue-100 text-blue-800 rounded">Wed</span> @endif
                                                @if($schedule->thursday) <span class="px-1 py-0 text-xs bg-blue-100 text-blue-800 rounded">Thu</span> @endif
                                                @if($schedule->friday) <span class="px-1 py-0 text-xs bg-blue-100 text-blue-800 rounded">Fri</span> @endif
                                                @if($schedule->saturday) <span class="px-1 py-0 text-xs bg-blue-100 text-blue-800 rounded">Sat</span> @endif
                                                @if($schedule->sunday) <span class="px-1 py-0 text-xs bg-blue-100 text-blue-800 rounded">Sun</span> @endif
                                            </div>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            <span class="px-2 py-1 text-xs rounded {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-4 border-b">{{ $schedule->created_at->format('M d, Y') }}</td>
                                        <td class="py-2 px-4 border-b">
                                            <button onclick="openEditModal({{ $schedule->id }})" 
                                                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-sm mr-2">
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('day-schedules.destroy', $schedule) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        onclick="return confirm('Are you sure you want to delete this day schedule?')"
                                                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-sm">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-4 px-4 text-center text-gray-500">
                                            No day schedules found. <button onclick="openAddModal()" class="text-blue-500 hover:underline">Add the first day schedule</button>
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
    <div id="dayScheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add Day Schedule</h3>
                
                <form id="dayScheduleForm" method="POST">
                    @csrf
                    <div id="methodField"></div>
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Schedule Name</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required 
                               placeholder="e.g., Monday to Friday"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Working Days</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center">
                                <input type="checkbox" id="monday" name="monday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Monday</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="tuesday" name="tuesday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Tuesday</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="wednesday" name="wednesday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Wednesday</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="thursday" name="thursday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Thursday</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="friday" name="friday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Friday</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="saturday" name="saturday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Saturday</span>
                            </label>
                            <label class="flex items-center col-span-2">
                                <input type="checkbox" id="sunday" name="sunday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Sunday</span>
                            </label>
                        </div>
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
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Day Schedule';
            document.getElementById('dayScheduleForm').action = '{{ route("day-schedules.store") }}';
            document.getElementById('methodField').innerHTML = '';
            document.getElementById('name').value = '';
            
            // Uncheck all days
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].forEach(day => {
                document.getElementById(day).checked = false;
            });
            
            document.getElementById('is_active').checked = true;
            document.getElementById('dayScheduleModal').classList.remove('hidden');
        }

        function openEditModal(scheduleId) {
            // Fetch schedule data
            fetch(`/day-schedules/${scheduleId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').textContent = 'Edit Day Schedule';
                    document.getElementById('dayScheduleForm').action = `/day-schedules/${scheduleId}`;
                    document.getElementById('methodField').innerHTML = '@method("PUT")';
                    document.getElementById('name').value = data.name;
                    
                    // Set day checkboxes
                    ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].forEach(day => {
                        document.getElementById(day).checked = data[day] || false;
                    });
                    
                    document.getElementById('is_active').checked = data.is_active;
                    document.getElementById('dayScheduleModal').classList.remove('hidden');
                });
        }

        function closeModal() {
            document.getElementById('dayScheduleModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('dayScheduleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</x-app-layout>
