<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('License Status') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($validation['valid'])
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">License Valid</h3>
                                    <p class="text-sm text-green-700">Your license is active and valid.</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">License Issue</h3>
                                    <p class="text-sm text-red-700">{{ $validation['reason'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($license)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">License Details</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Customer</dt>
                                        <dd class="text-sm text-gray-900">{{ $license->customer ?? 'Licensed User' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Max Employees</dt>
                                        <dd class="text-sm text-gray-900">
                                            {{ $license->employee_limit == -1 ? 'Unlimited' : $license->employee_limit }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Activated On</dt>
                                        <dd class="text-sm text-gray-900">{{ $license->activated_at->format('M d, Y H:i:s') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Expires On</dt>
                                        <dd class="text-sm text-gray-900">{{ $license->expires_at->format('M d, Y H:i:s') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="text-sm">
                                            @if($license->isValid())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Usage</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Current Employees</dt>
                                        <dd class="text-sm text-gray-900">{{ $employeeCount }}</dd>
                                    </div>
                                    @if($license->employee_limit != -1)
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Usage</dt>
                                            <dd class="text-sm text-gray-900">
                                                {{ number_format(($employeeCount / $license->employee_limit) * 100, 1) }}%
                                            </dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Time Remaining</dt>
                                        <dd class="text-sm text-gray-900">{{ $license->expires_at->diffForHumans() }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        @if($license->plan_info && isset($license->plan_info['features']))
                            <div class="mt-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Available Features</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    @foreach($license->plan_info['features'] as $feature)
                                        <div class="flex items-center">
                                            <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm text-gray-900">{{ str_replace('_', ' ', $feature) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center">
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Active License</h3>
                            <p class="mt-1 text-sm text-gray-500">You need to activate a license to use this system.</p>
                            <div class="mt-6">
                                <a href="{{ route('license.activate') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Activate License
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>