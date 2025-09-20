<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Holiday</h1>
            <a href="{{ route('settings.holidays.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Holidays
            </a>
        </div>

    @if(!$holiday->is_active)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm">This holiday is currently inactive.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.holidays.update', $holiday) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Holiday Name</label>
                    <input type="text" name="name" id="name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('name', $holiday->name) }}" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                    <input type="date" name="date" id="date" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('date', $holiday->date ? $holiday->date->format('Y-m-d') : '') }}" required>
                    @error('date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Holiday Type</label>
                <select name="type" id="type" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Select Type</option>
                    <option value="regular" {{ old('type', $holiday->type) == 'regular' ? 'selected' : '' }}>Regular Holiday</option>
                    <option value="special_non_working" {{ old('type', $holiday->type) == 'special_non_working' ? 'selected' : '' }}>Special Non-Working Holiday</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Pay Settings -->
            <div class="mt-8 bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pay Settings</h3>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_paid" id="is_paid" value="1" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                               onchange="toggleHolidayPaySettings()" {{ old('is_paid', $holiday->is_paid ?? true) ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-700">Paid Holiday</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Check if employees will receive pay for this holiday</p>
                </div>

                <div id="holiday_pay_settings" style="display: block;">
                    <div>
                        <label for="pay_applicable_to" class="block text-sm font-medium text-gray-700 mb-2">Applicable To</label>
                        <select name="pay_applicable_to" id="pay_applicable_to" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                onchange="handleHolidayApplicabilityChange()">
                            <option value="all" {{ old('pay_applicable_to', $holiday->pay_applicable_to ?? 'all') == 'all' ? 'selected' : '' }}>All Employees</option>
                            <option value="with_benefits" {{ old('pay_applicable_to', $holiday->pay_applicable_to ?? 'all') == 'with_benefits' ? 'selected' : '' }}>Employees with Benefits Only</option>
                            <option value="without_benefits" {{ old('pay_applicable_to', $holiday->pay_applicable_to ?? 'all') == 'without_benefits' ? 'selected' : '' }}>Employees without Benefits Only</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            <span id="applicability_note">When "with benefits" is selected, employees can still log time in/out. When "without benefits" is selected, time in/out will be disabled for bulk time logs.</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.holidays.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Update Holiday
                </button>
            </div>
        </form>
    </div>
</div>
    </div>
</div>

<script>
    function toggleHolidayPaySettings() {
        const isPaidChecked = document.getElementById('is_paid').checked;
        const paySettings = document.getElementById('holiday_pay_settings');
        const payApplicableToSelect = document.getElementById('pay_applicable_to');
        
        if (isPaidChecked) {
            paySettings.style.display = 'block';
            payApplicableToSelect.disabled = false;
            // Set default value if none selected
            if (!payApplicableToSelect.value) {
                payApplicableToSelect.value = 'all';
            }
        } else {
            paySettings.style.display = 'none';
            payApplicableToSelect.disabled = true;
            payApplicableToSelect.value = ''; // Clear the value when not paid
        }
    }

    function handleHolidayApplicabilityChange() {
        const applicableTo = document.getElementById('pay_applicable_to').value;
        const note = document.getElementById('applicability_note');
        
        if (applicableTo === 'with_benefits') {
            note.innerHTML = 'Employees with benefits will receive paid holiday. Time in/out logging remains enabled.';
        } else if (applicableTo === 'without_benefits') {
            note.innerHTML = 'Employees without benefits will receive paid holiday. Time in/out logging will be disabled for bulk time logs.';
        } else if (applicableTo === 'all') {
            note.innerHTML = 'All employees will receive paid holiday. Time in/out behavior depends on employee benefit status.';
        } else {
            note.innerHTML = 'Please select who this paid holiday applies to.';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleHolidayPaySettings();
        handleHolidayApplicabilityChange();
    });
</script>
</x-app-layout>
