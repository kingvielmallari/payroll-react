<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create No-Work Setting</h1>
            <a href="{{ route('settings.no-work.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to No-Work Settings
            </a>
        </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.no-work.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Setting Name</label>
                    <input type="text" name="name" id="name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('name') }}" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                    <input type="text" name="code" id="code" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('code') }}" required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">No-Work Type</label>
                    <select name="type" id="type" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Type</option>
                        <option value="absence" {{ old('type') == 'absence' ? 'selected' : '' }}>Absence</option>
                        <option value="tardiness" {{ old('type') == 'tardiness' ? 'selected' : '' }}>Tardiness</option>
                        <option value="undertime" {{ old('type') == 'undertime' ? 'selected' : '' }}>Undertime</option>
                        <option value="no_time_in" {{ old('type') == 'no_time_in' ? 'selected' : '' }}>No Time In</option>
                        <option value="no_time_out" {{ old('type') == 'no_time_out' ? 'selected' : '' }}>No Time Out</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" id="category" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Category</option>
                        <option value="deduction" {{ old('category') == 'deduction' ? 'selected' : '' }}>Deduction</option>
                        <option value="warning" {{ old('category') == 'warning' ? 'selected' : '' }}>Warning Only</option>
                        <option value="disciplinary" {{ old('category') == 'disciplinary' ? 'selected' : '' }}>Disciplinary Action</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="penalty_type" class="block text-sm font-medium text-gray-700 mb-2">Penalty Type</label>
                    <select name="penalty_type" id="penalty_type" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">No Penalty</option>
                        <option value="fixed_amount" {{ old('penalty_type') == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount</option>
                        <option value="percentage_of_daily_rate" {{ old('penalty_type') == 'percentage_of_daily_rate' ? 'selected' : '' }}>Percentage of Daily Rate</option>
                        <option value="hours_deduction" {{ old('penalty_type') == 'hours_deduction' ? 'selected' : '' }}>Hours Deduction</option>
                    </select>
                    @error('penalty_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="penalty_value_field" style="display: none;">
                    <label for="penalty_value" class="block text-sm font-medium text-gray-700 mb-2">Penalty Value</label>
                    <input type="number" name="penalty_value" id="penalty_value" step="0.01" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('penalty_value') }}">
                    <p class="mt-1 text-xs text-gray-500" id="penalty_help_text"></p>
                    @error('penalty_value')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="grace_period_minutes" class="block text-sm font-medium text-gray-700 mb-2">Grace Period (Minutes)</label>
                    <input type="number" name="grace_period_minutes" id="grace_period_minutes" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('grace_period_minutes', 0) }}">
                    <p class="mt-1 text-xs text-gray-500">Time allowance before penalty applies</p>
                    @error('grace_period_minutes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="occurrence_threshold" class="block text-sm font-medium text-gray-700 mb-2">Occurrence Threshold</label>
                    <input type="number" name="occurrence_threshold" id="occurrence_threshold" min="1"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('occurrence_threshold', 1) }}">
                    <p class="mt-1 text-xs text-gray-500">Number of occurrences before penalty applies</p>
                    @error('occurrence_threshold')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="requires_approval" id="requires_approval" value="1" 
                           {{ old('requires_approval', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="requires_approval" class="ml-2 block text-sm text-gray-700">Requires Supervisor Approval</label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="send_notification" id="send_notification" value="1" 
                           {{ old('send_notification', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="send_notification" class="ml-2 block text-sm text-gray-700">Send Notification to Employee</label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.no-work.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Create Setting
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('penalty_type').addEventListener('change', function() {
    const value = this.value;
    const penaltyField = document.getElementById('penalty_value_field');
    const helpText = document.getElementById('penalty_help_text');
    
    if (value === '') {
        penaltyField.style.display = 'none';
    } else {
        penaltyField.style.display = 'block';
        
        switch (value) {
            case 'fixed_amount':
                helpText.textContent = 'Fixed amount to deduct (e.g., 100.00)';
                break;
            case 'percentage_of_daily_rate':
                helpText.textContent = 'Percentage of daily rate to deduct (e.g., 10.00 for 10%)';
                break;
            case 'hours_deduction':
                helpText.textContent = 'Number of hours to deduct from pay';
                break;
            default:
                helpText.textContent = '';
        }
    }
});

// Trigger on page load
document.getElementById('penalty_type').dispatchEvent(new Event('change'));
</script>
    </div>
</div>
</x-app-layout>
