<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Allowance/Bonus: {{ $allowance->name }}</h1>
            <a href="{{ route('settings.allowances.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to List
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
        <form method="POST" action="{{ route('settings.allowances.update', $allowance) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $allowance->name) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                    <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Type</option>
                        <option value="allowance" {{ old('type', $allowance->type) == 'allowance' ? 'selected' : '' }}>Allowance</option>
                        <option value="bonus" {{ old('type', $allowance->type) == 'bonus' ? 'selected' : '' }}>Bonus</option>
                        <option value="benefit" {{ old('type', $allowance->type) == 'benefit' ? 'selected' : '' }}>Benefit</option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" id="category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Category</option>
                        <option value="regular" {{ old('category', $allowance->category) == 'regular' ? 'selected' : '' }}>Regular</option>
                        <option value="conditional" {{ old('category', $allowance->category) == 'conditional' ? 'selected' : '' }}>Conditional</option>
                        <option value="one_time" {{ old('category', $allowance->category) == 'one_time' ? 'selected' : '' }}>One Time</option>
                    </select>
                    @error('category')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="calculation_type" class="block text-sm font-medium text-gray-700">Calculation Type</label>
                    <select name="calculation_type" id="calculation_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Calculation Type</option>
                        <option value="percentage" {{ old('calculation_type', $allowance->calculation_type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                        <option value="fixed_amount" {{ old('calculation_type', $allowance->calculation_type) == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount</option>
                        <option value="daily_rate_multiplier" {{ old('calculation_type', $allowance->calculation_type) == 'daily_rate_multiplier' ? 'selected' : '' }}>Daily Rate Multiplier</option>
                    </select>
                    @error('calculation_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $allowance->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div id="rate_percentage_field" style="display: none;">
                    <label for="rate_percentage" class="block text-sm font-medium text-gray-700">Rate Percentage (%)</label>
                    <input type="number" name="rate_percentage" id="rate_percentage" step="0.01" min="0" max="100" value="{{ old('rate_percentage', $allowance->rate_percentage) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('rate_percentage')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="fixed_amount_field" style="display: none;">
                    <label for="fixed_amount" class="block text-sm font-medium text-gray-700">Fixed Amount (₱)</label>
                    <input type="number" name="fixed_amount" id="fixed_amount" step="0.01" min="0" value="{{ old('fixed_amount', $allowance->fixed_amount) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('fixed_amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="multiplier_field" style="display: none;">
                    <label for="multiplier" class="block text-sm font-medium text-gray-700">Multiplier</label>
                    <input type="number" name="multiplier" id="multiplier" step="0.01" min="0" value="{{ old('multiplier', $allowance->multiplier) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('multiplier')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="frequency" class="block text-sm font-medium text-gray-700">Frequency</label>
                    <select name="frequency" id="frequency" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Frequency</option>
                        <option value="daily" {{ old('frequency', $allowance->frequency) == 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="per_payroll" {{ old('frequency', $allowance->frequency) == 'per_payroll' ? 'selected' : '' }}>Per Payroll</option>
                        <option value="monthly" {{ old('frequency', $allowance->frequency) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('frequency', $allowance->frequency) == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="annually" {{ old('frequency', $allowance->frequency) == 'annually' ? 'selected' : '' }}>Annually</option>
                    </select>
                    @error('frequency')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $allowance->sort_order) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('sort_order')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="minimum_amount" class="block text-sm font-medium text-gray-700">Minimum Amount (₱)</label>
                    <input type="number" name="minimum_amount" id="minimum_amount" step="0.01" min="0" value="{{ old('minimum_amount', $allowance->minimum_amount) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('minimum_amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="maximum_amount" class="block text-sm font-medium text-gray-700">Maximum Amount (₱)</label>
                    <input type="number" name="maximum_amount" id="maximum_amount" step="0.01" min="0" value="{{ old('maximum_amount', $allowance->maximum_amount) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('maximum_amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Application Settings</h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_taxable" id="is_taxable" value="1" {{ old('is_taxable', $allowance->is_taxable) ? 'checked' : '' }} 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_taxable" class="ml-2 block text-sm text-gray-900">Taxable</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="apply_to_regular_days" id="apply_to_regular_days" value="1" {{ old('apply_to_regular_days', $allowance->apply_to_regular_days) ? 'checked' : '' }} 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="apply_to_regular_days" class="ml-2 block text-sm text-gray-900">Regular Days</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="apply_to_overtime" id="apply_to_overtime" value="1" {{ old('apply_to_overtime', $allowance->apply_to_overtime) ? 'checked' : '' }} 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="apply_to_overtime" class="ml-2 block text-sm text-gray-900">Overtime</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="apply_to_holidays" id="apply_to_holidays" value="1" {{ old('apply_to_holidays', $allowance->apply_to_holidays) ? 'checked' : '' }} 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="apply_to_holidays" class="ml-2 block text-sm text-gray-900">Holidays</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="apply_to_rest_days" id="apply_to_rest_days" value="1" {{ old('apply_to_rest_days', $allowance->apply_to_rest_days) ? 'checked' : '' }} 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="apply_to_rest_days" class="ml-2 block text-sm text-gray-900">Rest Days</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $allowance->is_active) ? 'checked' : '' }} 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('settings.allowances.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Update Allowance/Bonus
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
    document.getElementById('multiplier_field').style.display = 'none';
    
    // Show the relevant field
    if (calculationType === 'percentage') {
        document.getElementById('rate_percentage_field').style.display = 'block';
    } else if (calculationType === 'fixed_amount') {
        document.getElementById('fixed_amount_field').style.display = 'block';
    } else if (calculationType === 'daily_rate_multiplier') {
        document.getElementById('multiplier_field').style.display = 'block';
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
