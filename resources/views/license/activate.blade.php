<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-900">Activate License</h1>
        <p class="mt-2 text-sm text-gray-600">Enter your license key to activate the payroll system</p>
    </div>

            @if($currentLicense && $currentLicense->isValid())
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">License Active</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>Plan: {{ $currentLicense->subscriptionPlan->name }}</p>
                                <p>Expires: {{ $currentLicense->expires_at->format('M d, Y') }}</p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('dashboard') }}" class="text-sm text-green-800 underline">
                                    Go to Dashboard →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('license.activate.store') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="license_key" class="block text-sm font-medium text-gray-700">License Key</label>
                        <textarea id="license_key" 
                                name="license_key" 
                                rows="3"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Paste your license key here...">{{ old('license_key') }}</textarea>
                        @error('license_key')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Activate License
                    </button>
                </form>

                <div class="mt-6">
                    <div class="text-sm text-gray-600">
                        <p class="font-medium">Available Plans:</p>
                        @foreach($plans as $plan)
                            <div class="mt-2 p-3 bg-gray-50 rounded">
                                <p class="font-medium">{{ $plan->name }} - ${{ $plan->price }}</p>
                                <p class="text-xs">Max {{ $plan->max_employees == -1 ? 'Unlimited' : $plan->max_employees }} employees</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

    <div class="mt-4 text-center">
        <a href="{{ url('/') }}" class="text-sm text-gray-600 underline">
            Already have access? Login here
        </a>
    </div>
</x-guest-layout>