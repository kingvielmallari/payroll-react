<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Payroll Settings
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-green-800">Success</h4>
                            <div class="text-sm text-green-700 mt-1">
                                {{ session('success') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Automatic Payroll Creation Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Automatic Payroll Creation</h3>
                    
                    <form method="POST" action="{{ route('settings.payroll.update') }}" class="space-y-6">
                        @csrf
                        
                        <div class="flex items-start space-x-4">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="auto_payroll_enabled" value="1" id="auto_payroll_enabled"
                                       {{ $autoPayrollEnabled ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="auto_payroll_enabled" class="font-medium text-gray-700">
                                    Enable Automatic Payroll Creation
                                </label>
                                <p class="text-gray-500 mt-1">
                                    When enabled, payrolls will be automatically created based on employee pay schedules:
                                </p>
                                <ul class="text-gray-500 mt-2 ml-4 text-sm space-y-1">
                                    <li>• <strong>Weekly:</strong> Every Monday for the previous week</li>
                                    <li>• <strong>Semi-Monthly:</strong> On the 1st and 16th of each month</li>
                                    <li>• <strong>Monthly:</strong> On the 1st of each month for the previous month</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t">
                            <button type="button" id="test-auto-payroll"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                                Test Run (Dry Run)
                            </button>
                            
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">System Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Schedule Status</h4>
                            <p class="text-sm text-gray-500">
                                The automatic payroll creation command runs daily at 6:00 AM server time.
                                Make sure your server's cron job is configured to run Laravel's scheduler.
                            </p>
                            <code class="block bg-gray-100 p-2 mt-2 text-xs rounded">
                                * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
                            </code>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Manual Command</h4>
                            <p class="text-sm text-gray-500">
                                You can also run the automatic payroll creation manually using:
                            </p>
                            <code class="block bg-gray-100 p-2 mt-2 text-xs rounded">
                                php artisan payroll:auto-create
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Results Modal -->
            <div id="test-results-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Test Run Results</h3>
                            <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="test-output" class="bg-gray-100 p-4 rounded-md text-sm font-mono whitespace-pre-wrap max-h-96 overflow-y-auto">
                            <!-- Test output will be displayed here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testButton = document.getElementById('test-auto-payroll');
            const modal = document.getElementById('test-results-modal');
            const closeModal = document.getElementById('close-modal');
            const testOutput = document.getElementById('test-output');

            testButton.addEventListener('click', function() {
                testButton.disabled = true;
                testButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Running Test...';

                fetch('{{ route("settings.payroll.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    testOutput.textContent = data.output;
                    modal.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    testOutput.textContent = 'Error running test: ' + error.message;
                    modal.classList.remove('hidden');
                })
                .finally(() => {
                    testButton.disabled = false;
                    testButton.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>Test Run (Dry Run)';
                });
            });

            closeModal.addEventListener('click', function() {
                modal.classList.add('hidden');
            });

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
