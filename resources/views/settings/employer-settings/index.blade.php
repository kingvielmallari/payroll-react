<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employer Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings.employer.update') }}" class="space-y-6">
                @csrf

                <!-- Business Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Business Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="registered_business_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Registered Business Name / Actual Employer Name
                                </label>
                                <input type="text" name="registered_business_name" id="registered_business_name" 
                                       value="{{ old('registered_business_name', $settings->registered_business_name) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="Enter registered business name">
                                @error('registered_business_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="tax_identification_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tax Identification Number (TIN)
                                </label>
                                <input type="text" name="tax_identification_number" id="tax_identification_number" 
                                       value="{{ old('tax_identification_number', $settings->tax_identification_number) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="000-000-000-000">
                                @error('tax_identification_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="rdo_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    RDO Code
                                </label>
                                <input type="text" name="rdo_code" id="rdo_code" 
                                       value="{{ old('rdo_code', $settings->rdo_code) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="000">
                                @error('rdo_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Government IDs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Government Agency Numbers</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="sss_employer_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    SSS Employer Number
                                </label>
                                <input type="text" name="sss_employer_number" id="sss_employer_number" 
                                       value="{{ old('sss_employer_number', $settings->sss_employer_number) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="00-0000000-0">
                                @error('sss_employer_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="philhealth_employer_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    PhilHealth Employer Number
                                </label>
                                <input type="text" name="philhealth_employer_number" id="philhealth_employer_number" 
                                       value="{{ old('philhealth_employer_number', $settings->philhealth_employer_number) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="00-000000000-0">
                                @error('philhealth_employer_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="hdmf_employer_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    Employer HDMF Number (Pag-IBIG)
                                </label>
                                <input type="text" name="hdmf_employer_number" id="hdmf_employer_number" 
                                       value="{{ old('hdmf_employer_number', $settings->hdmf_employer_number) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="00000000000000">
                                @error('hdmf_employer_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="registered_address" class="block text-sm font-medium text-gray-700 mb-2">
                                    Registered Address
                                </label>
                                <textarea name="registered_address" id="registered_address" rows="3"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                          placeholder="Enter complete registered address">{{ old('registered_address', $settings->registered_address) }}</textarea>
                                @error('registered_address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="postal_zip_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Postal / ZIP Code
                                </label>
                                <input type="text" name="postal_zip_code" id="postal_zip_code" 
                                       value="{{ old('postal_zip_code', $settings->postal_zip_code) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="0000">
                                @error('postal_zip_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="landline_mobile" class="block text-sm font-medium text-gray-700 mb-2">
                                    Landline / Mobile Number
                                </label>
                                <input type="text" name="landline_mobile" id="landline_mobile" 
                                       value="{{ old('landline_mobile', $settings->landline_mobile) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="+63 000 000 0000">
                                @error('landline_mobile')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="office_business_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Office / Business Email
                                </label>
                                <input type="email" name="office_business_email" id="office_business_email" 
                                       value="{{ old('office_business_email', $settings->office_business_email) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="business@company.com">
                                @error('office_business_email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signatory Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Signatory Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="signatory_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Name of Signatory
                                </label>
                                <input type="text" name="signatory_name" id="signatory_name" 
                                       value="{{ old('signatory_name', $settings->signatory_name) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="Full name of authorized signatory">
                                @error('signatory_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="signatory_designation" class="block text-sm font-medium text-gray-700 mb-2">
                                    Signatory Designation
                                </label>
                                <input type="text" name="signatory_designation" id="signatory_designation" 
                                       value="{{ old('signatory_designation', $settings->signatory_designation) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="e.g., President, General Manager, HR Director">
                                @error('signatory_designation')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                                Save Employer Settings
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Information Panel -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Important Information</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>These settings are used for generating government forms and official documents</li>
                                <li>Ensure all information is accurate and matches your official business registration</li>
                                <li>Government agency numbers should match your official registrations with SSS, PhilHealth, and Pag-IBIG</li>
                                <li>The signatory should be an authorized representative who can sign official documents</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>