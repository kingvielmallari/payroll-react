<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create Suspension Day</h1>
            <a href="{{ route('settings.suspension.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Suspension Days
            </a>
        </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.suspension.store') }}">
            @csrf

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Suspension Name *</label>
                <input type="text" name="name" id="name" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                       value="{{ old('name') }}" 
                       placeholder="e.g. Typhoon Suspension, System Maintenance"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Suspension Type *</label>
                    <select name="type" id="type" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                            onchange="toggleDateAndTimeFields()" required>
                        <option value="">Select Type</option>
                        <option value="suspended" {{ old('type') == 'suspended' ? 'selected' : '' }}>Full Day Suspension</option>
                        <option value="partial_suspension" {{ old('type') == 'partial_suspension' ? 'selected' : '' }}>Partial Suspension</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
                    <select name="reason" id="reason" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Reason</option>
                        <option value="weather" {{ old('reason') == 'weather' ? 'selected' : '' }}>Weather</option>
                        <option value="system_maintenance" {{ old('reason') == 'system_maintenance' ? 'selected' : '' }}>System Maintenance</option>
                        <option value="emergency" {{ old('reason') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="government_order" {{ old('reason') == 'government_order' ? 'selected' : '' }}>Government Order</option>
                        <option value="other" {{ old('reason') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Suspension Date(s) - Only shown when suspension type is selected -->
            <div id="date_fields" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Suspension Date From *</label>
                    <input type="date" name="date_from" id="date_from" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('date_from') }}"
                           onchange="handleDateFromChange()">
                    @error('date_from')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Suspension Date To <span class="text-gray-500">(Optional)</span></label>
                    <input type="date" name="date_to" id="date_to" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('date_to') }}">
                    <p class="mt-1 text-xs text-gray-500">Leave empty for single day suspension</p>
                    @error('date_to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Time Period (for Partial Suspension) -->
            <div id="time_fields" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                <div>
                    <label for="time_from" class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                    <input type="time" name="time_from" id="time_from" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('time_from') }}">
                    @error('time_from')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="time_to" class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                    <input type="time" name="time_to" id="time_to" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('time_to') }}">
                    @error('time_to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Pay Settings -->
            <div class="mt-8 bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pay Settings</h3>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_paid" id="is_paid" value="1" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                               onchange="togglePaySettings()" {{ old('is_paid') ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-700">Paid Suspension</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Check if employees will still receive pay during suspension</p>
                </div>

                <div id="pay_settings" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="pay_percentage" class="block text-sm font-medium text-gray-700 mb-2">Pay Percentage</label>
                            <select name="pay_percentage" id="pay_percentage" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="100" {{ old('pay_percentage') == '100' ? 'selected' : '' }}>100% (Full Pay)</option>
                                <option value="75" {{ old('pay_percentage') == '75' ? 'selected' : '' }}>75%</option>
                                <option value="50" {{ old('pay_percentage') == '50' ? 'selected' : '' }}>50%</option>
                                <option value="25" {{ old('pay_percentage') == '25' ? 'selected' : '' }}>25%</option>
                            </select>
                        </div>

                        <div>
                            <label for="pay_applicable_to" class="block text-sm font-medium text-gray-700 mb-2">Applicable To</label>
                            <select name="pay_applicable_to" id="pay_applicable_to" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="all" {{ old('pay_applicable_to') == 'all' ? 'selected' : '' }}>All Employees</option>
                                <option value="with_benefits" {{ old('pay_applicable_to') == 'with_benefits' ? 'selected' : '' }}>Employees with Benefits Only</option>
                                <option value="without_benefits" {{ old('pay_applicable_to') == 'without_benefits' ? 'selected' : '' }}>Employees without Benefits Only</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.suspension.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Create Suspension Day
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
function toggleDateAndTimeFields() {
    const typeSelect = document.getElementById('type');
    const dateFields = document.getElementById('date_fields');
    const timeFields = document.getElementById('time_fields');
    const timeFromInput = document.getElementById('time_from');
    const timeToInput = document.getElementById('time_to');
    
    if (typeSelect.value === 'suspended' || typeSelect.value === 'partial_suspension') {
        // Show date fields for both full day and partial suspension
        dateFields.style.display = 'grid';
        
        // Make date fields required when type is selected
        document.getElementById('date_from').required = true;
        document.getElementById('date_to').required = false; // date_to is optional for better UX
        
        if (typeSelect.value === 'partial_suspension') {
            // Show time fields only for partial suspension
            timeFields.style.display = 'grid';
            // Make time fields required for partial suspension
            timeFromInput.required = true;
            timeToInput.required = true;
        } else {
            // Hide time fields for full day suspension and clear their values
            timeFields.style.display = 'none';
            timeFromInput.required = false;
            timeToInput.required = false;
            timeFromInput.value = '';
            timeToInput.value = '';
        }
    } else {
        // Hide both date and time fields when no type is selected
        dateFields.style.display = 'none';
        timeFields.style.display = 'none';
        
        // Remove required attribute when fields are hidden
        document.getElementById('date_from').required = false;
        document.getElementById('date_to').required = false;
        timeFromInput.required = false;
        timeToInput.required = false;
        timeFromInput.value = '';
        timeToInput.value = '';
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

function handleDateFromChange() {
    const typeSelect = document.getElementById('type');
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    // Always update date_to when date_from changes, regardless of suspension type
    if (dateFromInput.value) {
        dateToInput.value = dateFromInput.value;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDateAndTimeFields();
    togglePaySettings();
});
</script>
</x-app-layout>

        
