<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create Rate Configuration</h1>
            <a href="{{ route('payroll-rate-configurations.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Rate Multipliers
            </a>
        </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('payroll-rate-configurations.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type_name" class="block text-sm font-medium text-gray-700 mb-2">Type Name</label>
                    <input type="text" name="type_name" id="type_name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('type_name') }}" required
                           placeholder="e.g., regular_workday">
                    @error('type_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Internal identifier (use underscore for spaces)</p>
                </div>

                <div>
                    <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">Display Name</label>
                    <input type="text" name="display_name" id="display_name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('display_name') }}" required
                           placeholder="e.g., Regular Workday">
                    @error('display_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Name shown in user interface</p>
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="Optional description of this rate configuration">{{ old('description') }}</textarea>
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
                               value="{{ old('regular_rate_multiplier', '100') }}" required>
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
                               value="{{ old('overtime_rate_multiplier', '125') }}" required>
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
                           value="{{ old('sort_order', '0') }}">
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
                                   {{ old('is_active', true) ? 'checked' : '' }}>
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
                    <div>Regular Pay = Hourly Rate × <span id="regular_preview">100%</span> × Regular Hours</div>
                    <div>Overtime Pay = Hourly Rate × <span id="overtime_preview">125%</span> × Overtime Hours</div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('payroll-rate-configurations.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Create Configuration
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
                        <!-- Sort Order -->
                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" min="0"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('sort_order') border-red-300 @enderror" 
                                   value="{{ old('sort_order', '0') }}">
                            @error('sort_order')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Display order (lower numbers appear first)</p>
                        </div>

                        <!-- Active Status -->
                        <div>
                            <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <div class="mt-1">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-900">Active</span>
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Only active configurations appear in dropdowns</p>
                        </div>
                    </div>

                    <!-- Formula Preview -->
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <h4 class="text-sm font-medium text-blue-900 mb-2">Formula Preview</h4>
                        <div class="text-sm text-blue-800 space-y-1">
                            <div>Regular Pay = Hourly Rate × <span id="regular_preview">1.0000</span> × Regular Hours</div>
                            <div>Overtime Pay = Hourly Rate × <span id="overtime_preview">1.2500</span> × Overtime Hours</div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('payroll-rate-configurations.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 transition ease-in-out duration-150">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Create Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Update formula preview when multipliers change
    document.getElementById('regular_rate_multiplier').addEventListener('input', function() {
        document.getElementById('regular_preview').textContent = parseFloat(this.value || 0).toFixed(4);
    });
    
    document.getElementById('overtime_rate_multiplier').addEventListener('input', function() {
        document.getElementById('overtime_preview').textContent = parseFloat(this.value || 0).toFixed(4);
    });
</script>
</x-app-layout>
