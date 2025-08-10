<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create New Allowance/Bonus</h1>
            <a href="{{ route('settings.allowances.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to List
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
        <form method="POST" action="{{ route('settings.allowances.store') }}" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                    <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Type</option>
                        <option value="allowance" {{ old('type') == 'allowance' ? 'selected' : '' }}>Allowance</option>
                        <option value="bonus" {{ old('type') == 'bonus' ? 'selected' : '' }}>Bonus</option>
                        <option value="benefit" {{ old('type') == 'benefit' ? 'selected' : '' }}>Benefit</option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" id="category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Category</option>
                        <option value="regular" {{ old('category') == 'regular' ? 'selected' : '' }}>Regular</option>
                        <option value="conditional" {{ old('category') == 'conditional' ? 'selected' : '' }}>Conditional</option>
                        <option value="one_time" {{ old('category') == 'one_time' ? 'selected' : '' }}>One Time</option>
                    </select>
                    @error('category')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="calculation_type" class="block text-sm font-medium text-gray-700">Calculation Type</label>
                    <select name="calculation_type" id="calculation_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Calculation Type</option>
                        <option value="percentage" {{ old('calculation_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                        <option value="fixed_amount" {{ old('calculation_type') == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount</option>
                        <option value="daily_rate_multiplier" {{ old('calculation_type') == 'daily_rate_multiplier' ? 'selected' : '' }}>Daily Rate Multiplier</option>
                    </select>
                    @error('calculation_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div id="rate_percentage_field" style="display: none;">
                    <label for="rate_percentage" class="block text-sm font-medium text-gray-700">Rate Percentage (%)</label>
                    <input type="number" name="rate_percentage" id="rate_percentage" step="0.01" min="0" max="100" value="{{ old('rate_percentage') }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('rate_percentage')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="fixed_amount_field" style="display: none;">
                    <label for="fixed_amount" class="block text-sm font-medium text-gray-700">Fixed Amount (₱)</label>
                    <input type="number" name="fixed_amount" id="fixed_amount" step="0.01" min="0" value="{{ old('fixed_amount') }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('fixed_amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="daily_rate_multiplier_field" style="display: none;">
                    <label for="daily_rate_multiplier" class="block text-sm font-medium text-gray-700">Daily Rate Multiplier</label>
                    <input type="number" name="daily_rate_multiplier" id="daily_rate_multiplier" step="0.01" min="0" value="{{ old('daily_rate_multiplier') }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('daily_rate_multiplier')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} 
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
            </div>

            <div class="mt-6">
                <label for="benefit_eligibility" class="block text-sm font-medium text-gray-700 mb-2">Apply To</label>
                <select name="benefit_eligibility" id="benefit_eligibility" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="both" {{ old('benefit_eligibility', 'both') == 'both' ? 'selected' : '' }}>
                        Both (With Benefits & Without Benefits)
                    </option>
                    <option value="with_benefits" {{ old('benefit_eligibility') == 'with_benefits' ? 'selected' : '' }}>
                        Only Employees With Benefits
                    </option>
                    <option value="without_benefits" {{ old('benefit_eligibility') == 'without_benefits' ? 'selected' : '' }}>
                        Only Employees Without Benefits
                    </option>
                </select>
                @error('benefit_eligibility')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Choose which employees this allowance/bonus setting applies to based on their benefit status.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('settings.allowances.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Create Allowance/Bonus
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('calculation_type').addEventListener('change', function() {
    const calculationType = this.value;
    
    // Hide all calculation fields
    document.getElementById('rate_percentage_field').style.display = 'none';
    document.getElementById('fixed_amount_field').style.display = 'none';
    document.getElementById('daily_rate_multiplier_field').style.display = 'none';
    
    // Show the relevant field
    if (calculationType === 'percentage') {
        document.getElementById('rate_percentage_field').style.display = 'block';
    } else if (calculationType === 'fixed_amount') {
        document.getElementById('fixed_amount_field').style.display = 'block';
    } else if (calculationType === 'daily_rate_multiplier') {
        document.getElementById('daily_rate_multiplier_field').style.display = 'block';
    }
});

// Trigger change event on page load to show the correct field
document.addEventListener('DOMContentLoaded', function() {
    const calculationType = document.getElementById('calculation_type').value;
    if (calculationType) {
        document.getElementById('calculation_type').dispatchEvent(new Event('change'));
    }
});
</script>
    </div>
</div>
</x-app-layout>
