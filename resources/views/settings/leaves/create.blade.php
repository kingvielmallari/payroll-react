<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create Leave Type</h1>
            <a href="{{ route('settings.leaves.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Leave Types
            </a>
        </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.leaves.store') }}">
            @csrf

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Leave Type Name *</label>
                <input type="text" name="name" id="name" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                       value="{{ old('name') }}" 
                       placeholder="e.g. Vacation Leave, Sick Leave"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="total_days" class="block text-sm font-medium text-gray-700 mb-2">Total Day/s *</label>
                    <input type="number" name="total_days" id="total_days" min="1"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('total_days', 1) }}" required>
                    @error('total_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="leave_limit" class="block text-sm font-medium text-gray-700 mb-2">Leave Limit *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="limit_quantity" id="limit_quantity" min="1"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                               placeholder="Quantity" value="{{ old('limit_quantity', 1) }}" required>
                        <select name="limit_period" id="limit_period" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="monthly" {{ old('limit_period') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ old('limit_period') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="annually" {{ old('limit_period') == 'annually' ? 'selected' : '' }}>Annually</option>
                        </select>
                    </div>
                    @error('limit_quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('limit_period')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Pay Settings -->
            <div class="mt-8 bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pay Settings</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pay_applicable_to" class="block text-sm font-medium text-gray-700 mb-2">Applicable To</label>
                        <select name="pay_applicable_to" id="pay_applicable_to" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="all" {{ old('pay_applicable_to') == 'all' ? 'selected' : '' }}>All Employees</option>
                            <option value="with_benefits" {{ old('pay_applicable_to') == 'with_benefits' ? 'selected' : '' }}>Employees with Benefits Only</option>
                            <option value="without_benefits" {{ old('pay_applicable_to') == 'without_benefits' ? 'selected' : '' }}>Employees without Benefits Only</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Which employees the pay rule applies to</p>
                        @error('pay_applicable_to')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="pay_rule" class="block text-sm font-medium text-gray-700 mb-2">Pay Rule</label>
                        <select name="pay_rule" id="pay_rule" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="full" {{ old('pay_rule') == 'full' ? 'selected' : '' }}>Full Daily Rate (100%)</option>
                            <option value="half" {{ old('pay_rule') == 'half' ? 'selected' : '' }}>Half Daily Rate (50%)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Amount of daily rate to pay during leave</p>
                        @error('pay_rule')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.leaves.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Create Leave Type
                </button>
            </div>
        </form>
    </div>
    </div>
</div>
</x-app-layout>
