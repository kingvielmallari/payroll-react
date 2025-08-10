<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Allowance/Bonus Details</h1>
            <div class="flex space-x-2">
                <a href="{{ route('settings.allowances.edit', $allowance) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Edit Setting
                </a>
                <a href="{{ route('settings.allowances.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Back to List
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ $allowance->name }}</h3>
            <p class="text-sm text-gray-500 capitalize">{{ $allowance->type }} - {{ str_replace('_', ' ', $allowance->category) }}</p>
        </div>
        
        <div class="px-6 py-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Basic Information</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm text-gray-500">Description</dt>
                            <dd class="text-sm text-gray-900">{{ $allowance->description ?: 'No description' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $allowance->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $allowance->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Calculation Type</dt>
                            <dd class="text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $allowance->calculation_type) }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Calculation Details</h4>
                    <dl class="space-y-2">
                        @if($allowance->rate_percentage)
                            <div>
                                <dt class="text-sm text-gray-500">Rate Percentage</dt>
                                <dd class="text-sm text-gray-900">{{ $allowance->rate_percentage }}%</dd>
                            </div>
                        @endif
                        @if($allowance->fixed_amount)
                            <div>
                                <dt class="text-sm text-gray-500">Fixed Amount</dt>
                                <dd class="text-sm text-gray-900">₱{{ number_format($allowance->fixed_amount, 2) }}</dd>
                            </div>
                        @endif
                        @if($allowance->daily_rate_multiplier)
                            <div>
                                <dt class="text-sm text-gray-500">Daily Rate Multiplier</dt>
                                <dd class="text-sm text-gray-900">{{ $allowance->daily_rate_multiplier }}x</dd>
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
                        <dd class="inline">{{ $allowance->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="inline">Updated:</dt>
                        <dd class="inline">{{ $allowance->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
    </div>
</div>
</x-app-layout>
