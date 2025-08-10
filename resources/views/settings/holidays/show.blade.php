<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Holiday Details</h1>
            <div class="flex space-x-3">
                <a href="{{ route('settings.holidays.edit', $holiday) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Edit
                </a>
                <a href="{{ route('settings.holidays.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Back to Holidays
                </a>
            </div>
        </div>

    @if(!$holiday->is_active)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm">This holiday is currently inactive.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $holiday->name }}</h2>
            <p class="text-sm text-gray-600">{{ $holiday->date ? $holiday->date->format('F d, Y') : 'Date not set' }}</p>
        </div>

        <div class="p-6">
            @if($holiday->description)
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Description</h3>
                    <p class="text-gray-900">{{ $holiday->description }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Holiday Type</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        @if($holiday->type == 'regular') bg-green-100 text-green-800
                        @elseif($holiday->type == 'special_non_working') bg-blue-100 text-blue-800
                        @elseif($holiday->type == 'local') bg-yellow-100 text-yellow-800
                        @else bg-purple-100 text-purple-800 @endif">
                        {{ ucwords(str_replace('_', ' ', $holiday->type)) }}
                    </span>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Pay Rate Multiplier</h3>
                    <p class="text-gray-900">{{ number_format($holiday->pay_rate_multiplier, 2) }}x</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Year</h3>
                    <p class="text-gray-900">{{ $holiday->year }}</p>
                </div>

                @if($holiday->applicable_locations)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Applicable Locations</h3>
                    <p class="text-gray-900">{{ $holiday->applicable_locations }}</p>
                </div>
                @endif

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Status</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $holiday->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $holiday->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Recurring</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $holiday->is_recurring ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $holiday->is_recurring ? 'Yes' : 'No' }}
                    </span>
                </div>
            </div>

            @if($holiday->is_recurring)
                <div class="mt-6 p-4 bg-blue-50 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Recurring Holiday</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>This holiday occurs on the same date every year. The system will automatically apply this holiday for future years.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
                    <div>
                        <p><strong>Created:</strong> {{ $holiday->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <p><strong>Last Updated:</strong> {{ $holiday->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
</x-app-layout>
