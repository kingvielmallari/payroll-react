<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Leave Type</h1>
            <a href="{{ route('settings.leaves.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Back to Leave Types
            </a>
        </div>

    @if(!$leave->is_active)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm">This leave type is currently inactive.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('settings.leaves.update', $leave) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Leave Type Name</label>
                    <input type="text" name="name" id="name" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('name', $leave->name) }}" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Leave Code</label>
                    <input type="text" name="code" id="code" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('code', $leave->code) }}" required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $leave->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="annual_entitlement" class="block text-sm font-medium text-gray-700 mb-2">Annual Entitlement (Days)</label>
                    <input type="number" name="annual_entitlement" id="annual_entitlement" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('annual_entitlement', $leave->annual_entitlement) }}" required>
                    @error('annual_entitlement')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_consecutive_days" class="block text-sm font-medium text-gray-700 mb-2">Max Consecutive Days</label>
                    <input type="number" name="max_consecutive_days" id="max_consecutive_days" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('max_consecutive_days', $leave->max_consecutive_days) }}">
                    @error('max_consecutive_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notice_period_days" class="block text-sm font-medium text-gray-700 mb-2">Notice Period (Days)</label>
                    <input type="number" name="notice_period_days" id="notice_period_days" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ old('notice_period_days', $leave->notice_period_days) }}" required>
                    @error('notice_period_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" id="category" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Category</option>
                        <option value="paid" {{ old('category', $leave->category) == 'paid' ? 'selected' : '' }}>Paid Leave</option>
                        <option value="unpaid" {{ old('category', $leave->category) == 'unpaid' ? 'selected' : '' }}>Unpaid Leave</option>
                        <option value="sick" {{ old('category', $leave->category) == 'sick' ? 'selected' : '' }}>Sick Leave</option>
                        <option value="emergency" {{ old('category', $leave->category) == 'emergency' ? 'selected' : '' }}>Emergency Leave</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gender_restriction" class="block text-sm font-medium text-gray-700 mb-2">Gender Restriction</label>
                    <select name="gender_restriction" id="gender_restriction" 
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">No Restriction</option>
                        <option value="male" {{ old('gender_restriction', $leave->gender_restriction) == 'male' ? 'selected' : '' }}>Male Only</option>
                        <option value="female" {{ old('gender_restriction', $leave->gender_restriction) == 'female' ? 'selected' : '' }}>Female Only</option>
                    </select>
                    @error('gender_restriction')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="requires_approval" id="requires_approval" value="1" 
                           {{ old('requires_approval', $leave->requires_approval) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="requires_approval" class="ml-2 block text-sm text-gray-700">Requires Approval</label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="can_be_carried_forward" id="can_be_carried_forward" value="1" 
                           {{ old('can_be_carried_forward', $leave->can_be_carried_forward) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="can_be_carried_forward" class="ml-2 block text-sm text-gray-700">Can be Carried Forward to Next Year</label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', $leave->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('settings.leaves.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Update Leave Type
                </button>
            </div>
        </form>
    </div>
    </div>
</div>
</x-app-layout>
