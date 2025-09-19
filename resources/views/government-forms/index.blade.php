<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Government Forms & Reports') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Introduction -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2h1v-2h-1zm-2-2H7v4h6v-4zm2 0h1V9h-1v2z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Philippine Government Compliance Forms</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Generate official government forms required for tax compliance and employee benefits reporting.
                                All forms follow the latest 2025 format and calculation standards from BIR, SSS, PhilHealth, and Pag-IBIG.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BIR Forms Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="ml-3 text-lg font-medium text-gray-900">BIR (Bureau of Internal Revenue) Forms</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- BIR 1601C -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow opacity-50">
                            <h4 class="font-medium text-gray-900 mb-2">BIR Form 1601-C</h4>
                            <p class="text-sm text-gray-600 mb-3">Monthly Remittance Return of Income Taxes Withheld on Compensation</p>
                            <div class="space-y-2">
                                <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Monthly</span>
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Tax Withholding</span>
                            </div>
                            <div class="mt-4">
                                <span class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-gray-200 cursor-not-allowed">
                                    Not Available
                                </span>
                            </div>
                        </div>

                        <!-- BIR 2316 -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h4 class="font-medium text-gray-900 mb-2">BIR Form 2316</h4>
                            <p class="text-sm text-gray-600 mb-3">Certificate of Compensation Payment / Tax Withheld</p>
                            <div class="space-y-2">
                                <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Annual</span>
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Employee Certificate</span>
                            </div>
                            @can('generate bir forms')
                            <div class="mt-4">
                                <a href="{{ route('government-forms.bir-2316.employees') }}" 
                                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                    </svg>
                                    Generate Form
                                </a>
                            </div>
                            @endcan
                        </div>

                        <!-- BIR 1604C -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow opacity-50">
                            <h4 class="font-medium text-gray-900 mb-2">BIR Form 1604-C</h4>
                            <p class="text-sm text-gray-600 mb-3">Annual Information Return of Income Taxes Withheld on Compensation</p>
                            <div class="space-y-2">
                                <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Annual</span>
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Summary Report</span>
                            </div>
                            <div class="mt-4">
                                <span class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-gray-200 cursor-not-allowed">
                                    Not Available
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Government Agencies Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="ml-3 text-lg font-medium text-gray-900">Government Agencies Forms</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- SSS R-3 -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow opacity-50">
                            <h4 class="font-medium text-gray-900 mb-2">SSS Form R-3</h4>
                            <p class="text-sm text-gray-600 mb-3">Monthly Remittance Report for Social Security System contributions</p>
                            <div class="space-y-2">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Monthly</span>
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">SSS Contributions</span>
                            </div>
                            <div class="mt-4">
                                <span class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-gray-200 cursor-not-allowed">
                                    Not Available
                                </span>
                            </div>
                        </div>

                        <!-- PhilHealth RF-1 -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow opacity-50">
                            <h4 class="font-medium text-gray-900 mb-2">PhilHealth RF-1</h4>
                            <p class="text-sm text-gray-600 mb-3">Monthly Remittance Form for PhilHealth premium contributions</p>
                            <div class="space-y-2">
                                <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Monthly</span>
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">PhilHealth Premiums</span>
                            </div>
                            <div class="mt-4">
                                <span class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-gray-200 cursor-not-allowed">
                                    Not Available
                                </span>
                            </div>
                        </div>

                        <!-- Pag-IBIG MCRF -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow opacity-50">
                            <h4 class="font-medium text-gray-900 mb-2">Pag-IBIG MCRF</h4>
                            <p class="text-sm text-gray-600 mb-3">Monthly Collection/Remittance Form for Pag-IBIG Fund contributions</p>
                            <div class="space-y-2">
                                <span class="inline-block bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">Monthly</span>
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Pag-IBIG Contributions</span>
                            </div>
                            <div class="mt-4">
                                <span class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-gray-200 cursor-not-allowed">
                                    Not Available
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

          
        </div>
    </div>
</x-app-layout>
