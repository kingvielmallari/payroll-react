<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('System Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif



            <!-- System Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <svg class="inline w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        System Information
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">Current system configuration and settings.</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Application Status
                            </h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Version:</dt>
                                    <dd class="text-gray-900 font-medium">v1.0.0</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Environment:</dt>
                                    <dd class="text-gray-900 font-medium">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                            {{ app()->environment() }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Status:</dt>
                                    <dd class="text-gray-900 font-medium">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                </svg>
                                Configuration
                            </h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Timezone:</dt>
                                    <dd class="text-gray-900 font-medium">{{ $settings['system']['timezone'] ?? 'Asia/Manila' }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Locale:</dt>
                                    <dd class="text-gray-900 font-medium">{{ $settings['system']['locale'] ?? 'en_US' }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Currency:</dt>
                                    <dd class="text-gray-900 font-medium">PHP (₱)</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                </svg>
                                Server Information
                            </h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">PHP Version:</dt>
                                    <dd class="text-gray-900 font-medium">{{ PHP_VERSION }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Laravel:</dt>
                                    <dd class="text-gray-900 font-medium">{{ app()->version() }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Database:</dt>
                                    <dd class="text-gray-900 font-medium">MySQL</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- License Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <svg class="inline w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                        License Information
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">Current license details and usage statistics.</p>
                </div>

                <div class="p-6">
                    @if($currentLicense)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Current License -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900">Current License</h4>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $currentLicense->isValid() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $currentLicense->isValid() ? 'Active' : 'Expired' }}
                                </span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Customer:</dt>
                                    <dd class="text-gray-900 font-medium">{{ $currentLicense->customer ?? 'Licensed User' }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">License Cost:</dt>
                                    <dd class="text-gray-900 font-medium">₱{{ number_format($currentLicense->price ?? 0, 2) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Activated:</dt>
                                    <dd class="text-gray-900 font-medium">{{ $currentLicense->activated_at->format('M d, Y - g:i A') }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Expires:</dt>
                                    <dd class="text-gray-900 font-medium">{{ $currentLicense->expires_at->format('M d, Y - g:i A') }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Usage -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-6 rounded-lg border border-green-200">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Employee Usage</h4>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600">Current Employees</span>
                                        <span class="text-gray-900 font-medium">{{ $employeeCount }} / {{ $currentLicense->employee_limit ?? 'N/A' }}</span>
                                    </div>
                                    @if($currentLicense->employee_limit)
                                        @php
                                            $usagePercent = ($employeeCount / $currentLicense->employee_limit) * 100;
                                        @endphp
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ min($usagePercent, 100) }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                                            <span>{{ number_format($usagePercent, 1) }}% Used</span>
                                            <span>{{ $currentLicense->employee_limit - $employeeCount }} Available</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="pt-3 border-t border-gray-200">
                                    <div class="text-sm text-gray-600">
                                        <p class="font-medium mb-1">License Status:</p>
                                        @if($currentLicense->hasReachedEmployeeLimit())
                                            <p class="text-red-600">⚠️ Employee limit reached</p>
                                        @elseif($currentLicense->isExpired())
                                            <p class="text-red-600">⚠️ License expired</p>
                                        @else
                                            <p class="text-green-600">✅ License active and valid</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">No Active License</h3>
                            <p class="mt-1 text-sm text-gray-600">You need to activate a license to use this payroll system.</p>
                            <div class="mt-4">
                                <a href="{{ route('license.activate') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                                    Activate License
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($currentLicense && $currentLicense->plan_info && isset($currentLicense->plan_info['features']))
                    <!-- License Features -->
                    <div class="mt-6 bg-gray-50 p-6 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Available Features</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="flex items-center text-sm text-gray-700">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Up to {{ $currentLicense->employee_limit ?? 'N/A' }} Employees
                            </div>
                            @foreach($currentLicense->plan_info['features'] as $feature)
                            <div class="flex items-center text-sm text-gray-700">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                {{ str_replace('_', ' ', ucwords($feature)) }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    @if($currentLicense)
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('license.activate') }}?upgrade=1" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            Upgrade License
                        </a>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>


</x-app-layout>
