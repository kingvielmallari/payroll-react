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

            <!-- Appearance Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <svg class="inline w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                        </svg>
                        Appearance
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">Customize the look and feel of your application.</p>
                </div>

                <form method="POST" action="{{ route('system-settings.update') }}" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Theme Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Theme</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="theme" value="light" 
                                           {{ $settings['appearance']['theme'] === 'light' ? 'checked' : '' }}
                                           class="form-radio h-4 w-4 text-blue-600">
                                    <span class="ml-2 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                                        </svg>
                                        Light Mode
                                    </span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="radio" name="theme" value="dark" 
                                           {{ $settings['appearance']['theme'] === 'dark' ? 'checked' : '' }}
                                           class="form-radio h-4 w-4 text-blue-600">
                                    <span class="ml-2 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-700" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                                        </svg>
                                        Dark Mode
                                    </span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Choose between light and dark theme for better visibility.</p>
                        </div>

                        <!-- Quick Theme Toggle Button -->
                        <div>
                            <button type="button" id="quick-theme-toggle" 
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-md transition-colors duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Quick Toggle Theme
                            </button>
                            <p class="text-xs text-gray-500 mt-1">Instantly switch between light and dark mode.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 pt-4 border-t border-gray-200">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Save Appearance Settings
                        </button>
                    </div>
                </form>
            </div>

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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Application Settings</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Current Theme:</dt>
                                    <dd class="text-gray-900 font-medium">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                            {{ ucfirst($settings['appearance']['theme']) }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Timezone:</dt>
                                    <dd class="text-gray-900 font-medium">{{ $settings['system']['timezone'] }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Locale:</dt>
                                    <dd class="text-gray-900 font-medium">{{ $settings['system']['locale'] }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Quick Actions</h4>
                            <div class="space-y-2">
                                <a href="{{ route('payroll-schedule-settings.index') }}" 
                                   class="flex items-center text-sm text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h8m-6 0v8a2 2 0 002 2h4a2 2 0 002-2V7m-6 0H6a2 2 0 00-2 2v10a2 2 0 002 2h1m5-10V9a2 2 0 00-2-2H8a2 2 0 00-2 2v2m8 0V9a2 2 0 00-2-2h-2m4 4h2a2 2 0 012 2v2a2 2 0 01-2 2h-2m-4-4v6m0-6h4"></path>
                                    </svg>
                                    Payroll Schedule Settings
                                </a>
                                @if(auth()->user()->can('view employees'))
                                <a href="{{ route('employees.index') }}" 
                                   class="flex items-center text-sm text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Manage Employees
                                </a>
                                @endif
                                <a href="{{ route('dashboard') }}" 
                                   class="flex items-center text-sm text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 15v4m4-4v4m4-4v4"></path>
                                    </svg>
                                    Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <svg class="inline w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19l5-5 5 5-5 5-5-5zM9 3l5 5-5 5-5-5 5-5z"></path>
                        </svg>
                        Notification Preferences
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">Configure how you receive system notifications.</p>
                </div>

                <form method="POST" action="{{ route('system-settings.update') }}" class="p-6">
                    @csrf
                    @method('PUT')
                    
                    <!-- Keep theme value for this form -->
                    <input type="hidden" name="theme" value="{{ $settings['appearance']['theme'] }}">

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="email_notifications" name="email_notifications" type="checkbox" 
                                       {{ $settings['notifications']['email_notifications'] ? 'checked' : '' }}
                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="email_notifications" class="font-medium text-gray-700">Email Notifications</label>
                                <p class="text-gray-500">Receive notifications about payroll, employee updates, and system alerts via email.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="browser_notifications" name="browser_notifications" type="checkbox" 
                                       {{ $settings['notifications']['browser_notifications'] ? 'checked' : '' }}
                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="browser_notifications" class="font-medium text-gray-700">Browser Notifications</label>
                                <p class="text-gray-500">Get instant notifications in your browser for urgent updates.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 pt-4 border-t border-gray-200">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Save Notification Settings
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quickToggleBtn = document.getElementById('quick-theme-toggle');
            const lightRadio = document.querySelector('input[name="theme"][value="light"]');
            const darkRadio = document.querySelector('input[name="theme"][value="dark"]');

            quickToggleBtn.addEventListener('click', function() {
                fetch('{{ route('system-settings.toggle-theme') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the radio buttons
                        if (data.theme === 'light') {
                            lightRadio.checked = true;
                            darkRadio.checked = false;
                        } else {
                            lightRadio.checked = false;
                            darkRadio.checked = true;
                        }
                        
                        // Reload page to apply theme changes
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    </script>
</x-app-layout>
