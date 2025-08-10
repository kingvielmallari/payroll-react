<x-app-layout>
<div class="py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="cutoff_start_day" class="block text-sm font-medium text-gray-700 mb-2">Cut-off Start Day</label>
                    <input type="number" name="cutoff_start_day" id="cutoff_start_day" 
                           min="1" max="31"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('cutoff_start_day') }}" required>
                    @error('cutoff_start_day')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cutoff_end_day" class="block text-sm font-medium text-gray-700 mb-2">Cut-off End Day</label>
                    <input type="number" name="cutoff_end_day" id="cutoff_end_day" 
                           min="1" max="31"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('cutoff_end_day') }}" required>
                    @error('cutoff_end_day')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="payday_offset_days" class="block text-sm font-medium text-gray-700 mb-2">Pay Date Offset (Days)</label>
                    <input type="number" name="payday_offset_days" id="payday_offset_days" 
                           min="0" max="30"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('payday_offset_days') }}" required>
                    <p class="mt-1 text-xs text-gray-500">Days after cut-off end to pay</p>
                    @error('payday_offset_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>ss="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create Pay Schedule</h1>
            <a href="{{ route('settings.pay-schedules.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Pay Schedules
            </a>
        </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.pay-schedules.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Schedule Name</label>
                    <input type="text" name="name" id="name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('name') }}" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pay_type" class="block text-sm font-medium text-gray-700 mb-2">Pay Type</label>
                    <select name="pay_type" id="pay_type" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Pay Type</option>
                        <option value="weekly" {{ old('pay_type') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="bi_weekly" {{ old('pay_type') == 'bi_weekly' ? 'selected' : '' }}>Bi-Weekly</option>
                        <option value="semi_monthly" {{ old('pay_type') == 'semi_monthly' ? 'selected' : '' }}>Semi-Monthly</option>
                        <option value="monthly" {{ old('pay_type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                    @error('pay_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="cutoff_start_day" class="block text-sm font-medium text-gray-700 mb-2">Cut-off Start Day</label>
                    <input type="number" name="cutoff_start_day" id="cutoff_start_day" 
                           min="1" max="31"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('cutoff_start_day') }}" required>
                    @error('cutoff_start_day')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cutoff_end_day" class="block text-sm font-medium text-gray-700 mb-2">Cut-off End Day</label>
                    <input type="number" name="cutoff_end_day" id="cutoff_end_day" 
                           min="1" max="31"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('cutoff_end_day') }}" required>
                    @error('cutoff_end_day')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="payday_offset_days" class="block text-sm font-medium text-gray-700 mb-2">Pay Date Offset (Days)</label>
                    <input type="number" name="payday_offset_days" id="payday_offset_days" 
                           min="0" max="30"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('payday_offset_days') }}" required>
                    <p class="mt-1 text-xs text-gray-500">Days after cut-off end to pay</p>
                    @error('payday_offset_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="payday_description" class="block text-sm font-medium text-gray-700 mb-2">Pay Date Description</label>
                <textarea name="payday_description" id="payday_description" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                          placeholder="e.g., Every 15th and 30th of the month">{{ old('payday_description') }}</textarea>
                @error('payday_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

                <div class="flex items-center mt-8">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.pay-schedules.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Create Schedule
                </button>
            </div>
        </form>
    </div>
    </div>
</div>
</x-app-layout>
