<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Rate Configuration Details</h1>
            <div class="flex space-x-2">
                <a href="{{ route('payroll-rate-configurations.edit', $payrollRateConfiguration) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Edit Configuration
                </a>
                <a href="{{ route('payroll-rate-configurations.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
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
                <h3 class="text-lg font-medium text-gray-900">{{ $payrollRateConfiguration->display_name }}</h3>
                <p class="text-sm text-gray-500">{{ $payrollRateConfiguration->type_name }}</p>
            </div>
            
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Basic Information</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">Description</dt>
                                <dd class="text-sm text-gray-900">{{ $payrollRateConfiguration->description ?: 'No description' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Status</dt>
                                <dd>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $payrollRateConfiguration->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $payrollRateConfiguration->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Sort Order</dt>
                                <dd class="text-sm text-gray-900">{{ $payrollRateConfiguration->sort_order }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Rate Multipliers</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">Regular Hours Rate</dt>
                                <dd class="text-sm text-gray-900">{{ intval($payrollRateConfiguration->regular_rate_multiplier * 100) }}%</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Overtime Rate</dt>
                                <dd class="text-sm text-gray-900">{{ intval($payrollRateConfiguration->overtime_rate_multiplier * 100) }}%</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Formula Preview -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Rate Calculation Formula</h4>
                    <div class="text-sm text-blue-800 space-y-1">
                        <div><strong>Regular Pay:</strong> Hourly Rate × {{ intval($payrollRateConfiguration->regular_rate_multiplier * 100) }}% × Regular Hours</div>
                        <div><strong>Overtime Pay:</strong> Hourly Rate × {{ intval($payrollRateConfiguration->overtime_rate_multiplier * 100) }}% × Overtime Hours</div>
                    </div>
                </div>

                <!-- Usage Examples -->
                <div class="mt-6 bg-gray-50 border border-gray-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Usage Examples (₱100/hour base rate)</h4>
                    <div class="text-sm text-gray-700 space-y-1">
                        <div><strong>8 Regular Hours:</strong> ₱100 × {{ intval($payrollRateConfiguration->regular_rate_multiplier * 100) }}% × 8 = ₱{{ number_format(100 * $payrollRateConfiguration->regular_rate_multiplier * 8, 2) }}</div>
                        <div><strong>2 Overtime Hours:</strong> ₱100 × {{ intval($payrollRateConfiguration->overtime_rate_multiplier * 100) }}% × 2 = ₱{{ number_format(100 * $payrollRateConfiguration->overtime_rate_multiplier * 2, 2) }}</div>
                        <div><strong>Total (8 reg + 2 OT):</strong> ₱{{ number_format((100 * $payrollRateConfiguration->regular_rate_multiplier * 8) + (100 * $payrollRateConfiguration->overtime_rate_multiplier * 2), 2) }}</div>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Timestamps</h4>
                    <dl class="space-y-1 text-xs text-gray-500">
                        <div>
                            <dt class="inline">Created:</dt>
                            <dd class="inline">{{ $payrollRateConfiguration->created_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="inline">Updated:</dt>
                            <dd class="inline">{{ $payrollRateConfiguration->updated_at->format('M j, Y g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
