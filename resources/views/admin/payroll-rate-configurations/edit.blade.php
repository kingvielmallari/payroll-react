<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Rate Configuration</h1>
            <a href="{{ route('payroll-rate-configurations.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Rate Multipliers
            </a>
        </div>

    @if(!$payrollRateConfiguration->is_active)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm">This rate configuration is currently inactive.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('payroll-rate-configurations.update', $payrollRateConfiguration) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type_name" class="block text-sm font-medium text-gray-700 mb-2">Type Name</label>
                    <input type="text" name="type_name" id="type_name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('type_name', $payrollRateConfiguration->type_name) }}" required>
                    @error('type_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Internal identifier (use underscore for spaces)</p>
                </div>

                <div>
                    <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">Display Name</label>
                    <input type="text" name="display_name" id="display_name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('display_name', $payrollRateConfiguration->display_name) }}" required>
                    @error('display_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Name shown in user interface</p>
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $payrollRateConfiguration->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="regular_rate_multiplier" class="block text-sm font-medium text-gray-700 mb-2">Regular Hours Rate Multiplier</label>
                    <div class="relative">
                        <input type="number" name="regular_rate_multiplier" id="regular_rate_multiplier" step="1" min="0" max="1000"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8" 
                               value="{{ old('regular_rate_multiplier', intval($payrollRateConfiguration->regular_rate_multiplier * 100)) }}" required>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm">%</span>
                        </div>
                    </div>
                    @error('regular_rate_multiplier')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Multiplier for regular working hours (100% = normal rate)</p>
                </div>

                <div>
                    <label for="overtime_rate_multiplier" class="block text-sm font-medium text-gray-700 mb-2">Overtime Rate Multiplier</label>
                    <div class="relative">
                        <input type="number" name="overtime_rate_multiplier" id="overtime_rate_multiplier" step="1" min="0" max="1000"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8" 
                               value="{{ old('overtime_rate_multiplier', intval($payrollRateConfiguration->overtime_rate_multiplier * 100)) }}" required>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm">%</span>
                        </div>
                    </div>
                    @error('overtime_rate_multiplier')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Multiplier for overtime hours (125% = 1.25x normal rate)</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('sort_order', $payrollRateConfiguration->sort_order) }}">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Display order (lower numbers appear first)</p>
                </div>

                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div class="mt-1">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                   {{ old('is_active', $payrollRateConfiguration->is_active) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-900">Active</span>
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Only active configurations appear in dropdowns</p>
                </div>
            </div>

            <!-- Formula Preview -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                <h4 class="text-sm font-medium text-blue-900 mb-2">Formula Preview</h4>
                <div class="text-sm text-blue-800 space-y-1">
                    <div>Regular Pay = Hourly Rate × <span id="regular_preview">{{ intval($payrollRateConfiguration->regular_rate_multiplier * 100) }}%</span> × Regular Hours</div>
                    <div>Overtime Pay = Hourly Rate × <span id="overtime_preview">{{ intval($payrollRateConfiguration->overtime_rate_multiplier * 100) }}%</span> × Overtime Hours</div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('payroll-rate-configurations.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Update Configuration
                </button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
    // Update formula preview when multipliers change
    document.getElementById('regular_rate_multiplier').addEventListener('input', function() {
        document.getElementById('regular_preview').textContent = (parseInt(this.value) || 0) + '%';
    });
    
    document.getElementById('overtime_rate_multiplier').addEventListener('input', function() {
        document.getElementById('overtime_preview').textContent = (parseInt(this.value) || 0) + '%';
    });
</script>
</x-app-layout>
