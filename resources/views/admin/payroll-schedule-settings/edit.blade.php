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
                @if($payrollScheduleSetting->pay_type === 'semi_monthly')
                    <!-- First Cutoff Period -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">1st Cut-off Period Configuration</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- 1st Period Start Day -->
                                <div>
                                    <label for="first_period_start" class="block text-sm font-medium text-gray-700 mb-2">
                                        Start Day <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           id="first_period_start" 
                                           name="first_period_start" 
                                           value="{{ old('first_period_start', $payrollScheduleSetting->semi_monthly_config['first_period']['start_day'] ?? 1) }}"
                                           min="1" 
                                           max="31"
                                           required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="1">
                                    @error('first_period_start')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- 1st Period End Day -->
                                <div>
                                    <label for="first_period_end" class="block text-sm font-medium text-gray-700 mb-2">
                                        End Day <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           id="first_period_end" 
                                           name="first_period_end" 
                                           value="{{ old('first_period_end', $payrollScheduleSetting->semi_monthly_config['first_period']['end_day'] ?? 15) }}"
                                           min="1" 
                                           max="31"
                                           required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="15">
                                    @error('first_period_end')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- 1st Period Pay Day -->
                                <div>
                                    <label for="first_period_pay" class="block text-sm font-medium text-gray-700 mb-2">
                                        Pay Day <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           id="first_period_pay" 
                                           name="first_period_pay" 
                                           value="{{ old('first_period_pay', $payrollScheduleSetting->semi_monthly_config['first_period']['pay_day'] ?? 20) }}"
                                           min="1" 
                                           max="31"
                                           required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="20">
                                    @error('first_period_pay')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Cutoff Period -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">2nd Cut-off Period Configuration</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- 2nd Period Start Day -->
                                <div>
                                    <label for="second_period_start" class="block text-sm font-medium text-gray-700 mb-2">
                                        Start Day <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           id="second_period_start" 
                                           name="second_period_start" 
                                           value="{{ old('second_period_start', $payrollScheduleSetting->semi_monthly_config['second_period']['start_day'] ?? 16) }}"
                                           min="1" 
                                           max="31"
                                           required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="16">
                                    @error('second_period_start')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- 2nd Period End Day -->
                                <div>
                                    <label for="second_period_end" class="block text-sm font-medium text-gray-700 mb-2">
                                        End Day <span class="text-red-500">*</span>
                                    </label>
                                    <select id="second_period_end" 
                                            name="second_period_end" 
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="28" {{ old('second_period_end', $payrollScheduleSetting->semi_monthly_config['second_period']['end_day'] ?? -1) == 28 ? 'selected' : '' }}>28th (Feb safe)</option>
                                        <option value="30" {{ old('second_period_end', $payrollScheduleSetting->semi_monthly_config['second_period']['end_day'] ?? -1) == 30 ? 'selected' : '' }}>30th</option>
                                        <option value="-1" {{ old('second_period_end', $payrollScheduleSetting->semi_monthly_config['second_period']['end_day'] ?? -1) == -1 ? 'selected' : '' }}>Last day of month</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Choose "Last day of month" for automatic adjustment (28-31)</p>
                                    @error('second_period_end')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- 2nd Period Pay Day -->
                                <div>
                                    <label for="second_period_pay" class="block text-sm font-medium text-gray-700 mb-2">
                                        Pay Day <span class="text-red-500">*</span>
                                    </label>
                                    <select id="second_period_pay" 
                                            name="second_period_pay" 
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="1" {{ old('second_period_pay', $payrollScheduleSetting->semi_monthly_config['second_period']['pay_day'] ?? -1) == 1 ? 'selected' : '' }}>1st of next month</option>
                                        <option value="5" {{ old('second_period_pay', $payrollScheduleSetting->semi_monthly_config['second_period']['pay_day'] ?? -1) == 5 ? 'selected' : '' }}>5th of next month</option>
                                        <option value="-1" {{ old('second_period_pay', $payrollScheduleSetting->semi_monthly_config['second_period']['pay_day'] ?? -1) == -1 ? 'selected' : '' }}>Last day of month</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">When employees get paid for this period</p>
                                    @error('second_period_pay')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Regular Cut-off Configuration for Weekly/Monthly -->
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
                @endif

                <!-- Pay Day Configuration (for Weekly/Monthly only) -->
                @if($payrollScheduleSetting->pay_type !== 'semi_monthly')
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
                @endif

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

    @if($payrollScheduleSetting->pay_type === 'semi_monthly')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add date validation for semi-monthly periods
            function validateSemiMonthlyDates() {
                const firstStart = parseInt(document.getElementById('first_period_start').value) || 1;
                const firstEnd = parseInt(document.getElementById('first_period_end').value) || 15;
                const firstPay = parseInt(document.getElementById('first_period_pay').value) || 20;
                const secondStart = parseInt(document.getElementById('second_period_start').value) || 16;
                
                // Validate first period logic
                if (firstEnd <= firstStart) {
                    alert('First period end day must be after start day');
                    return false;
                }
                
                // Validate second period starts after first ends
                if (secondStart <= firstEnd) {
                    alert('Second period start day must be after first period end day');
                    return false;
                }
                
                // Validate pay day is reasonable
                if (firstPay <= firstEnd && firstPay > 0) {
                    if (!confirm('First period pay day (' + firstPay + 'th) is before or on the period end day (' + firstEnd + 'th). This might cause confusion. Continue?')) {
                        return false;
                    }
                }
                
                return true;
            }
            
            // Add event listeners to form fields
            document.querySelectorAll('#first_period_start, #first_period_end, #first_period_pay, #second_period_start').forEach(function(field) {
                field.addEventListener('change', function() {
                    // Update second period start automatically if needed
                    if (field.id === 'first_period_end') {
                        const secondStartField = document.getElementById('second_period_start');
                        const newSecondStart = parseInt(field.value) + 1;
                        if (newSecondStart <= 31) {
                            secondStartField.value = newSecondStart;
                        }
                    }
                });
            });
            
            // Add validation on form submit
            document.querySelector('form').addEventListener('submit', function(e) {
                if (!validateSemiMonthlyDates()) {
                    e.preventDefault();
                }
            });
        });
    </script>
    @endif
</x-app-layout>
