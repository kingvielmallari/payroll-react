<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Leave Type Details</h1>
            <div class="flex space-x-3">
                <a href="{{ route('settings.leaves.edit', $leave) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Edit
                </a>
                <a href="{{ route('settings.leaves.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Back to Leave Types
                </a>
            </div>
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

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $leave->name }}</h2>
            <p class="text-sm text-gray-600">Code: {{ $leave->code }}</p>
        </div>

        <div class="p-6">
            @if($leave->description)
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Description</h3>
                    <p class="text-gray-900">{{ $leave->description }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Category</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        @if($leave->category == 'paid') bg-green-100 text-green-800
                        @elseif($leave->category == 'unpaid') bg-red-100 text-red-800
                        @elseif($leave->category == 'sick') bg-yellow-100 text-yellow-800
                        @else bg-blue-100 text-blue-800 @endif">
                        {{ ucfirst($leave->category) }} Leave
                    </span>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Annual Entitlement</h3>
                    <p class="text-gray-900">{{ $leave->annual_entitlement }} days</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Notice Period</h3>
                    <p class="text-gray-900">{{ $leave->notice_period_days }} days</p>
                </div>

                @if($leave->max_consecutive_days)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Max Consecutive Days</h3>
                    <p class="text-gray-900">{{ $leave->max_consecutive_days }} days</p>
                </div>
                @endif

                @if($leave->gender_restriction)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Gender Restriction</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ ucfirst($leave->gender_restriction) }} Only
                    </span>
                </div>
                @endif

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Status</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $leave->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $leave->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <h3 class="text-sm font-medium text-gray-700">Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2 {{ $leave->requires_approval ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                            @if($leave->requires_approval)
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            @else
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            @endif
                        </svg>
                        <span class="text-sm text-gray-900">Requires Approval</span>
                    </div>

                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2 {{ $leave->can_be_carried_forward ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                            @if($leave->can_be_carried_forward)
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            @else
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            @endif
                        </svg>
                        <span class="text-sm text-gray-900">Can be Carried Forward</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
                    <div>
                        <p><strong>Created:</strong> {{ $leave->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <p><strong>Last Updated:</strong> {{ $leave->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
</x-app-layout>
