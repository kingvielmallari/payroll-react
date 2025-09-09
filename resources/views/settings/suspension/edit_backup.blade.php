<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Suspension Setting</h1>
            <div class="flex space-x-3">
                <a href="{{ route('settings.suspension.show', $suspension) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    View Details
                </a>
                <a href="{{ route('settings.suspension.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Back to Suspension Settings
                </a>
            </div>
        </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.suspension.update', $suspension) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Setting Name</label>
                    <input type="text" name="name" id="name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('name', $suspension->name) }}" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                    <input type="text" name="code" id="code" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('code', $suspension->code) }}" required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $suspension->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Suspension Type</label>
                    <select name="type" id="type" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Type</option>
                        <option value="suspended" {{ old('type', $suspension->type) == 'suspended' ? 'selected' : '' }}>Full Suspension</option>
                        <option value="partial_suspension" {{ old('type', $suspension->type) == 'partial_suspension' ? 'selected' : '' }}>Partial Suspension</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                    <select name="reason" id="reason" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Reason</option>
                        <option value="weather" {{ old('reason', $suspension->reason) == 'weather' ? 'selected' : '' }}>Weather</option>
                        <option value="system_maintenance" {{ old('reason', $suspension->reason) == 'system_maintenance' ? 'selected' : '' }}>System Maintenance</option>
                        <option value="emergency" {{ old('reason', $suspension->reason) == 'emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="government_order" {{ old('reason', $suspension->reason) == 'government_order' ? 'selected' : '' }}>Government Order</option>
                        <option value="other" {{ old('reason', $suspension->reason) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="detailed_reason" class="block text-sm font-medium text-gray-700 mb-2">Detailed Reason</label>
                <textarea name="detailed_reason" id="detailed_reason" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('detailed_reason', $suspension->detailed_reason) }}</textarea>
                @error('detailed_reason')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" name="date_from" id="date_from" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('date_from', $suspension->date_from ? $suspension->date_from->format('Y-m-d') : '') }}" 
                           onchange="handleDateFromChange()" required>
                    @error('date_from')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" name="date_to" id="date_to" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('date_to', $suspension->date_to ? $suspension->date_to->format('Y-m-d') : '') }}" required>
                    @error('date_to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="time_from" class="block text-sm font-medium text-gray-700 mb-2">Time From (Optional)</label>
                    <input type="time" name="time_from" id="time_from" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('time_from', $suspension->time_from ? \Carbon\Carbon::createFromFormat('H:i:s', $suspension->time_from)->format('H:i') : '') }}">
                    @error('time_from')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="time_to" class="block text-sm font-medium text-gray-700 mb-2">Time To (Optional)</label>
                    <input type="time" name="time_to" id="time_to" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('time_to', $suspension->time_to ? \Carbon\Carbon::createFromFormat('H:i:s', $suspension->time_to)->format('H:i') : '') }}">
                    @error('time_to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Select Status</option>
                    <option value="draft" {{ old('status', $suspension->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="active" {{ old('status', $suspension->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ old('status', $suspension->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ old('status', $suspension->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.suspension.show', $suspension) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Update Suspension Setting
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
function handleDateFromChange() {
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    // Always update date_to when date_from changes
    if (dateFromInput.value) {
        dateToInput.value = dateFromInput.value;
    }
}
</script>
</x-app-layout>
