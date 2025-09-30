<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="mb-6 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.966-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mt-4">License Expired</h1>
                <p class="mt-2 text-sm text-gray-600">Your license has expired and needs to be renewed</p>
            </div>

            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="text-sm text-red-700">
                    <p class="font-medium">Access Restricted</p>
                    <p>Your payroll system license has expired. Please contact your administrator or renew your license to continue using the system.</p>
                </div>
            </div>

            <div class="space-y-4">
                <a href="{{ route('license.activate') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Activate New License
                </a>
                
                <a href="{{ url('/') }}" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>