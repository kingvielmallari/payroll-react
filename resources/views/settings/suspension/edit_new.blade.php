<x-app-layout>
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Suspension Setting</h1>
            <div class="flex space-x-3">
                <a href="{{ route('settings.suspension.show', $suspension) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm">
                    View Details
                </a>
                <a href="{{ route('settings.suspension.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm">
                    Back to Suspension Settings
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <form method="POST" action="{{ route('settings.suspension.update', $suspension) }}">
                @csrf
                @method('PUT')

                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                    
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
                                   class="w-full bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500" 
                                   value="{{ old('code', $suspension->code) }}" readonly>
                            <p class="mt-1 text-xs text-gray-500">Auto-generated code cannot be modified</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="description" rows="3" 
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Optional description for this suspension setting">{{ old('description', $suspension->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Suspension Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Suspension Type</label>
                            <select name="type" id="type" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="full_day_suspension" {{ old('type', $suspension->type) == 'full_day_suspension' ? 'selected' : '' }}>Full Suspension</option>
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
                        <textarea name="detailed_reason" id="detailed_reason" rows="2" 
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Additional details about the suspension reason">{{ old('detailed_reason', $suspension->detailed_reason) }}</textarea>
                        @error('detailed_reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Date & Time Settings</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                            <label for="time_from" class="block text-sm font-medium text-gray-700 mb-2">Time From <span class="text-gray-500">(Optional)</span></label>
                            <input type="time" name="time_from" id="time_from" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                   value="{{ old('time_from', $suspension->time_from ? \Carbon\Carbon::createFromFormat('H:i:s', $suspension->time_from)->format('H:i') : '') }}">
                            <p class="mt-1 text-xs text-gray-500">For partial suspensions only</p>
                            @error('time_from')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="time_to" class="block text-sm font-medium text-gray-700 mb-2">Time To <span class="text-gray-500">(Optional)</span></label>
                            <input type="time" name="time_to" id="time_to" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                   value="{{ old('time_to', $suspension->time_to ? \Carbon\Carbon::createFromFormat('H:i:s', $suspension->time_to)->format('H:i') : '') }}">
                            <p class="mt-1 text-xs text-gray-500">For partial suspensions only</p>
                            @error('time_to')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Pay Settings</h2>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_paid" id="is_paid" value="1" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                   onchange="togglePaySettings()" {{ old('is_paid', $suspension->is_paid) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm font-medium text-gray-700">Paid Suspension</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500">Check if employees will still receive pay during suspension</p>
                    </div>

                    <div id="pay_settings" style="display: {{ old('is_paid', $suspension->is_paid) ? 'block' : 'none' }};">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="pay_percentage" class="block text-sm font-medium text-gray-700 mb-2">Pay Percentage</label>
                                <select name="pay_percentage" id="pay_percentage" 
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="100" {{ old('pay_percentage', $suspension->pay_percentage) == '100' ? 'selected' : '' }}>100% (Full Pay)</option>
                                    <option value="75" {{ old('pay_percentage', $suspension->pay_percentage) == '75' ? 'selected' : '' }}>75%</option>
                                    <option value="50" {{ old('pay_percentage', $suspension->pay_percentage) == '50' ? 'selected' : '' }}>50%</option>
                                    <option value="25" {{ old('pay_percentage', $suspension->pay_percentage) == '25' ? 'selected' : '' }}>25%</option>
                                </select>
                            </div>

                            <div>
                                <label for="pay_applicable_to" class="block text-sm font-medium text-gray-700 mb-2">Applicable To</label>
                                <select name="pay_applicable_to" id="pay_applicable_to" 
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all" {{ old('pay_applicable_to', $suspension->pay_applicable_to) == 'all' ? 'selected' : '' }}>All Employees</option>
                                    <option value="with_benefits" {{ old('pay_applicable_to', $suspension->pay_applicable_to) == 'with_benefits' ? 'selected' : '' }}>Employees with Benefits Only</option>
                                    <option value="without_benefits" {{ old('pay_applicable_to', $suspension->pay_applicable_to) == 'without_benefits' ? 'selected' : '' }}>Employees without Benefits Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" id="status" 
                                    class="w-48 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="draft" {{ old('status', $suspension->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ old('status', $suspension->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ old('status', $suspension->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', $suspension->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex space-x-3">
                            <a href="{{ route('settings.suspension.show', $suspension) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-md text-sm">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm">
                                Update Suspension Setting
                            </button>
                        </div>
                    </div>
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

function togglePaySettings() {
    const isPaid = document.getElementById('is_paid');
    const paySettings = document.getElementById('pay_settings');
    
    if (isPaid.checked) {
        paySettings.style.display = 'block';
    } else {
        paySettings.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePaySettings();
});
</script>
</x-app-layout>
