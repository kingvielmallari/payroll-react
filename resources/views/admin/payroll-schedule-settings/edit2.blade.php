<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Payroll Schedule Settings') }} - {{ ucfirst(str_replace('_', ' ', $payrollScheduleSetting->pay_type)) }}
            </h2>
            <a href="{{ route('payroll-schedule-settings.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Settings
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('payroll-schedule-settings.update', $payrollScheduleSetting) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Schedule Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule Configuration</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Pay Type (Read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Pay Schedule Type
                                </label>
                                <div class="p-3 bg-gray-100 rounded-md text-sm">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $payrollScheduleSetting->pay_type === 'weekly' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $payrollScheduleSetting->pay_type === 'semi_monthly' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $payrollScheduleSetting->pay_type === 'monthly' ? 'bg-purple-100 text-purple-800' : '' }}
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $payrollScheduleSetting->pay_type)) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="is_active" name="is_active" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="1" {{ old('is_active', $payrollScheduleSetting->is_active) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !old('is_active', $payrollScheduleSetting->is_active) ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cut-off Configuration -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Cut-off Period Configuration</h3>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Cut-off Description -->
                            <div>
                                <label for="cutoff_description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cut-off Period Description <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="cutoff_description" 
                                       name="cutoff_description" 
                                       value="{{ old('cutoff_description', $payrollScheduleSetting->cutoff_description) }}"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="e.g., Monday to Sunday">
                                @error('cutoff_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Cutoff Start Day -->
                                <div>
                                    <label for="cutoff_start_day" class="block text-sm font-medium text-gray-700 mb-2">
                                        Cut-off Start Day
                                    </label>
                                    <input type="number" 
                                           id="cutoff_start_day" 
                                           name="cutoff_start_day" 
                                           value="{{ old('cutoff_start_day', $payrollScheduleSetting->cutoff_start_day) }}"
                                           min="1" 
                                           max="31"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="Day of month">
                                    @error('cutoff_start_day')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Cutoff End Day -->
                                <div>
                                    <label for="cutoff_end_day" class="block text-sm font-medium text-gray-700 mb-2">
                                        Cut-off End Day
                                    </label>
                                    <input type="number" 
                                           id="cutoff_end_day" 
                                           name="cutoff_end_day" 
                                           value="{{ old('cutoff_end_day', $payrollScheduleSetting->cutoff_end_day) }}"
                                           min="1" 
                                           max="31"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="Day of month">
                                    @error('cutoff_end_day')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pay Day Configuration -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Pay Day Configuration</h3>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Pay Day Description -->
                            <div>
                                <label for="payday_description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Pay Day Description <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="payday_description" 
                                       name="payday_description" 
                                       value="{{ old('payday_description', $payrollScheduleSetting->payday_description) }}"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="e.g., Next Friday">
                                @error('payday_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Pay Day Offset -->
                            <div>
                                <label for="payday_offset_days" class="block text-sm font-medium text-gray-700 mb-2">
                                    Pay Day Offset (Days) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       id="payday_offset_days" 
                                       name="payday_offset_days" 
                                       value="{{ old('payday_offset_days', $payrollScheduleSetting->payday_offset_days) }}"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="Days after cutoff end">
                                <p class="mt-1 text-xs text-gray-500">
                                    Number of days after cutoff period ends when employees get paid
                                </p>
                                @error('payday_offset_days')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Information</h3>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Notes -->
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes
                                </label>
                                <textarea id="notes" 
                                          name="notes" 
                                          rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                          placeholder="Additional notes or special rules">{{ old('notes', $payrollScheduleSetting->notes) }}</textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Custom Rules (JSON) -->
                            <div>
                                <label for="cutoff_rules" class="block text-sm font-medium text-gray-700 mb-2">
                                    Custom Rules (JSON)
                                </label>
                                <textarea id="cutoff_rules" 
                                          name="cutoff_rules" 
                                          rows="4"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs"
                                          placeholder='{"example": "value"}'>{{ old('cutoff_rules', json_encode($payrollScheduleSetting->cutoff_rules, JSON_PRETTY_PRINT)) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">
                                    Advanced configuration in JSON format. Leave empty if not needed.
                                </p>
                                @error('cutoff_rules')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('payroll-schedule-settings.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
