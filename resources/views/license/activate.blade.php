<x-guest-layout>
    <div class="mb-6 text-center">
        @if($currentLicense && $currentLicense->isValid() && !($isUpgrade ?? false))
            <h1 class="mt-3 text-2xl font-bold text-gray-900">Activated License</h1>
            <p class="mt-2 text-sm text-gray-600">Hooray! Your license key is now activated</p>
        @elseif($isUpgrade ?? false)
            <h1 class="text-2xl font-bold text-gray-900">Upgrade License</h1>
            <p class="mt-2 text-sm text-gray-600">Enter your new license key to upgrade the payroll system</p>
        @else
            <h1 class="text-2xl font-bold text-gray-900">Activate License</h1>
            <p class="mt-2 text-sm text-gray-600">Enter your license key to activate the payroll system</p>
        @endif
    </div>

    @if($currentLicense && $currentLicense->isValid() && !($isUpgrade ?? false))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">License Successfully Activated!</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p>Customer: {{ $currentLicense->customer ?? 'Licensed User' }}</p>
                        <p>Max Employees: {{ $currentLicense->employee_limit ?? 'N/A' }}</p>
                        <p>Expires: {{ $currentLicense->expires_at->format('M d, Y - g:i A') }}</p>
                    </div>
                    <div class="mt-4 space-x-4">
                        <a href="{{ url('/') }}" class="text-sm text-green-800 underline">
                            Go to Login Page →
                        </a>
                 
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(!$currentLicense || !$currentLicense->isValid() || ($isUpgrade ?? false))
                <form method="POST" action="{{ route('license.activate.store') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="license_key" class="block text-sm font-medium text-gray-700">
                            {{ ($isUpgrade ?? false) ? 'New License Key' : 'License Key' }}
                        </label>
                        <textarea id="license_key" 
                                name="license_key" 
                                rows="3"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="{{ ($isUpgrade ?? false) ? 'Paste your new license key here...' : 'Paste your license key here...' }}">{{ old('license_key') }}</textarea>
                        @error('license_key')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ ($isUpgrade ?? false) ? 'Upgrade License' : 'Activate License' }}
                    </button>
                </form>

                <div class="mt-6">
                    <div class="text-sm text-gray-600">
                        <p class="font-medium">Need a License Key?</p>
                        <div class="mt-2 mb-2 p-3 bg-gray-50 rounded">
                            <p class="text-xs">Contact your system administrator to obtain a valid license key for this payroll system.</p>
                        </div>
                    </div>
                </div>
            @endif

    {{-- @if(session('success'))
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif --}}

    <div class="mt-4 text-center">
        <a href="{{ url('/') }}" class="text-sm text-gray-600 underline">
            Already have access? Login here
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('license_key');
            const form = textarea?.closest('form');
            
            if (textarea && form) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        form.submit();
                    }
                });
            }
        });
    </script>
</x-guest-layout>