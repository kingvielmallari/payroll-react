<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Import DTR (Daily Time Record)
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('dtr.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to DTR
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Instructions Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Import Instructions</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Download the template file below to see the correct format</li>
                                <li>Fill in the employee data using either Employee Number or Email</li>
                                <li>Use date format: YYYY-MM-DD (e.g., 2024-08-09)</li>
                                <li>Use time format: HH:MM (24-hour format, e.g., 08:00, 17:30) or 12-hour format with AM/PM</li>
                                <li>Time Out can be empty for ongoing shifts</li>
                                <li>Break times are optional - if not provided, 1 hour will be automatically deducted</li>
                                <li>Supported file formats: .xlsx, .xls, .csv (max 2MB)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Download Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Download Template</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                Download a pre-formatted Excel template with sample data and empty rows for your entries.
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="{{ route('dtr.export-template') }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Download Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Form Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Upload DTR File</h3>
                    
                    <form action="{{ route('dtr.import') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <!-- File Upload -->
                        <div>
                            <label for="dtr_file" class="block text-sm font-medium text-gray-700">DTR File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="dtr_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload a file</span>
                                            <input id="dtr_file" name="dtr_file" type="file" class="sr-only" accept=".xlsx,.xls,.csv" required>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">Excel or CSV files up to 2MB</p>
                                </div>
                            </div>
                            @error('dtr_file')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Options -->
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input id="overwrite_existing" name="overwrite_existing" type="checkbox" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="overwrite_existing" class="ml-2 block text-sm text-gray-900">
                                    Overwrite existing time logs for the same date and employee
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 ml-6">
                                If unchecked, existing time logs will be skipped and not updated.
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('dtr.index') }}"
                               class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                Import DTR Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sample Format Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Expected File Format</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Number</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">EMP-2025-0001</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">juan.doe@company.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Juan Dela Cruz</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-08-09</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">08:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">17:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">12:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">13:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Regular day</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">EMP-2025-0002</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">maria.santos@company.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Maria Santos</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-08-09</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">09:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">18:30</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Overtime</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <p><strong>Notes:</strong></p>
                        <ul class="list-disc list-inside space-y-1 mt-2">
                            <li>Either Employee Number or Email is required to identify the employee</li>
                            <li>Employee Name column is for reference only and not used in import</li>
                            <li>Break times are optional - if omitted, 1 hour break will be automatically deducted</li>
                            <li>All imported time logs will be automatically approved</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- File Upload JavaScript -->
    <script>
        // File upload drag and drop functionality
        const fileInput = document.getElementById('dtr_file');
        const dropZone = fileInput.closest('.border-dashed');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
        }
        
        function unhighlight(e) {
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            fileInput.files = files;
            updateFileName(files[0]);
        }
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                updateFileName(e.target.files[0]);
            }
        });
        
        function updateFileName(file) {
            const fileNameDisplay = dropZone.querySelector('.text-sm.text-gray-600');
            if (fileNameDisplay) {
                fileNameDisplay.innerHTML = `<span class="font-medium text-blue-600">${file.name}</span> (${formatFileSize(file.size)})`;
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</x-app-layout>
