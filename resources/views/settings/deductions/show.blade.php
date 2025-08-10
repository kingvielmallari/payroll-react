<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Deduction/Tax Details</h1>
            <div class="flex space-x-2">
                <a href="{{ route('settings.deductions.edit', $deduction) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Edit Setting
                </a>
                <a href="{{ route('settings.deductions.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ $deduction->name }}</h3>
            <p class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $deduction->type) }}</p>
        </div>
        
        <div class="px-6 py-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Basic Information</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm text-gray-500">Description</dt>
                            <dd class="text-sm text-gray-900">{{ $deduction->description ?: 'No description' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $deduction->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $deduction->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Category</dt>
                            <dd class="text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $deduction->category) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Calculation Type</dt>
                            <dd class="text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $deduction->calculation_type) }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Calculation Details</h4>
                    <dl class="space-y-2">
                        @if($deduction->rate_percentage)
                            <div>
                                <dt class="text-sm text-gray-500">Rate Percentage</dt>
                                <dd class="text-sm text-gray-900">{{ $deduction->rate_percentage }}%</dd>
                            </div>
                        @endif
                        @if($deduction->fixed_amount)
                            <div>
                                <dt class="text-sm text-gray-500">Fixed Amount</dt>
                                <dd class="text-sm text-gray-900">₱{{ number_format($deduction->fixed_amount, 2) }}</dd>
                            </div>
                        @endif
                        @if($deduction->minimum_salary_threshold)
                            <div>
                                <dt class="text-sm text-gray-500">Minimum Salary Threshold</dt>
                                <dd class="text-sm text-gray-900">₱{{ number_format($deduction->minimum_salary_threshold, 2) }}</dd>
                            </div>
                        @endif
                        @if($deduction->maximum_deduction)
                            <div>
                                <dt class="text-sm text-gray-500">Maximum Deduction</dt>
                                <dd class="text-sm text-gray-900">₱{{ number_format($deduction->maximum_deduction, 2) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-2">Timestamps</h4>
                <dl class="space-y-1 text-xs text-gray-500">
                    <div>
                        <dt class="inline">Created:</dt>
                        <dd class="inline">{{ $deduction->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="inline">Updated:</dt>
                        <dd class="inline">{{ $deduction->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
    </div>
</div>
</x-app-layout>
