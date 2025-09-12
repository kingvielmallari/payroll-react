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
       

            <!-- Template Download Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Download Template</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                Download a simple Excel template with the required columns: Employee Number, Date, Time In, Time Out, Break In, Break Out.
                                Includes sample data showing both 12-hour (AM/PM) and 24-hour time formats.
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
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md" id="drop-zone">
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
                                    <div id="file-info" class="hidden mt-2 text-sm text-green-600"></div>
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break Out</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">EMP-2025-0024</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">10/09/2024</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">8:00 am</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">5:00 pm</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">12:00 pm</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1:00 pm</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">EMP-2025-0024</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">11/09/2024</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">8:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">17:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">12:00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">13:00</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">EMP-2025-0024</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">13/09/2024</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">9:00 am</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">6:00 pm</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 italic">(empty)</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 italic">(empty)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <p><strong>Notes:</strong></p>
                        <ul class="list-disc list-inside space-y-1 mt-2">
                            <li><strong>Employee Number</strong> and <strong>Date</strong> are required - records without these will be skipped</li>
                            <li>Only active employees will be imported - inactive employee records will be skipped</li>
                            <li>Time fields support both <strong>12-hour format</strong> (8:00 AM, 5:00 PM) and <strong>24-hour format</strong> (08:00, 17:00)</li>
                            <li>Break times are optional - empty values are allowed</li>
                            <li>For duplicate employee + date combinations, the most complete record will be imported</li>
                            <li>Import will behave exactly like the manual bulk time log creation process</li>
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
        const dropZone = document.getElementById('drop-zone');
        const fileInfo = document.getElementById('file-info');
        let currentImportId = null;
        
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
            
            if (files.length > 0) {
                fileInput.files = files;
                updateFileName(files[0]);
            }
        }
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                updateFileName(e.target.files[0]);
            }
        });
        
        function updateFileName(file) {
            // Show file info
            fileInfo.classList.remove('hidden');
            fileInfo.innerHTML = `✓ Selected: <strong>${file.name}</strong> (${formatFileSize(file.size)})`;
            
            // Update drop zone to show success
            dropZone.classList.add('border-green-400', 'bg-green-50');
            dropZone.classList.remove('border-gray-300');
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Enhanced form submission with preview and confirmation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default to handle via JavaScript
            
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('Please select a file to import.', 'error');
                return false;
            }
            
            handleImportPreview();
        });

        async function handleImportPreview() {
            const formData = new FormData();
            formData.append('dtr_file', fileInput.files[0]);
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            try {
                showLoadingState('Analyzing file...');
                
                const response = await fetch('{{ route("dtr.import-preview") }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                hideLoadingState();

                if (result.success) {
                    showConfirmationDialog(result);
                } else {
                    showAlert(result.message || 'Failed to analyze file', 'error');
                }
            } catch (error) {
                hideLoadingState();
                showAlert('Error analyzing file: ' + error.message, 'error');
            }
        }

        function showConfirmationDialog(previewResult) {
            const message = `
                <div class="text-left">
                    <h3 class="text-lg font-semibold mb-4">Import Confirmation</h3>
                    <p class="mb-2"><strong>Records to be imported:</strong> ${previewResult.valid_records_count}</p>
                    ${previewResult.skipped_count > 0 ? `<p class="mb-2 text-yellow-600"><strong>Records to be skipped:</strong> ${previewResult.skipped_count}</p>` : ''}
                    ${previewResult.error_count > 0 ? `<p class="mb-2 text-red-600"><strong>Records with errors:</strong> ${previewResult.error_count}</p>` : ''}
                    <p class="mt-4 text-sm text-gray-600">Do you want to proceed with the import?</p>
                    ${previewResult.error_count > 0 ? `<div class="mt-2 text-xs text-red-500">Some records contain errors and will be skipped.</div>` : ''}
                </div>
            `;

            if (confirm(`Import Confirmation\n\nRecords to be imported: ${previewResult.valid_records_count}\n${previewResult.skipped_count > 0 ? `Records to be skipped: ${previewResult.skipped_count}\n` : ''}${previewResult.error_count > 0 ? `Records with errors: ${previewResult.error_count}\n` : ''}\nDo you want to proceed with the import?`)) {
                proceedWithImport();
            }
        }

        async function proceedWithImport() {
            const formData = new FormData();
            formData.append('dtr_file', fileInput.files[0]);
            formData.append('overwrite_existing', document.getElementById('overwrite_existing').checked ? '1' : '0');
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            try {
                showLoadingState('Importing records...');
                
                const response = await fetch('{{ route("dtr.import") }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                hideLoadingState();

                if (result.success) {
                    currentImportId = result.import_id;
                    showSuccessMessage(result);
                } else {
                    showAlert(result.message || 'Import failed', 'error');
                }
            } catch (error) {
                hideLoadingState();
                showAlert('Error during import: ' + error.message, 'error');
            }
        }

        function showSuccessMessage(result) {
            let successHtml = `
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Import Completed Successfully!</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p><strong>Successfully imported:</strong> ${result.imported_count} records</p>
                                ${result.skipped_count > 0 ? `<p><strong>Skipped:</strong> ${result.skipped_count} records</p>` : ''}
                                ${result.error_count > 0 ? `<p><strong>Errors:</strong> ${result.error_count} records</p>` : ''}
                            </div>
                            <div class="mt-4">
                                <button onclick="deleteLatestImport()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Delete All Latest Import from DB
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Insert success message at the top of the container
            const container = document.querySelector('.max-w-7xl.mx-auto.py-10.px-4');
            container.insertAdjacentHTML('afterbegin', successHtml);

            // Reset form
            form.reset();
            fileInfo.classList.add('hidden');
            dropZone.classList.remove('border-green-400', 'bg-green-50');
            dropZone.classList.add('border-gray-300');
        }

        async function deleteLatestImport() {
            if (!currentImportId) {
                showAlert('No import ID found', 'error');
                return;
            }

            if (!confirm('Are you sure you want to delete all records from the latest import? This action cannot be undone.')) {
                return;
            }

            try {
                showLoadingState('Deleting records...');
                
                const response = await fetch('{{ route("dtr.delete-latest-import") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        import_id: currentImportId
                    })
                });

                const result = await response.json();
                hideLoadingState();

                if (result.success) {
                    showAlert(`Successfully deleted ${result.deleted_count} records from the latest import.`, 'success');
                    currentImportId = null;
                    // Remove the success message div
                    const successDiv = document.querySelector('.bg-green-50');
                    if (successDiv) {
                        successDiv.remove();
                    }
                } else {
                    showAlert(result.message || 'Failed to delete records', 'error');
                }
            } catch (error) {
                hideLoadingState();
                showAlert('Error deleting records: ' + error.message, 'error');
            }
        }

        function showLoadingState(message) {
            const loadingHtml = `
                <div id="loading-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
                            <h3 class="text-lg font-medium text-gray-900 mt-2">${message}</h3>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
        }

        function hideLoadingState() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        function showAlert(message, type = 'info') {
            const alertClass = type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 
                             type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 
                             'bg-blue-50 border-blue-200 text-blue-800';
            
            const alertHtml = `
                <div class="${alertClass} border rounded-md p-4 mb-4">
                    <p>${message}</p>
                </div>
            `;
            
            const container = document.querySelector('.max-w-7xl.mx-auto.py-10.px-4');
            container.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector(`.${alertClass.split(' ')[0]}`);
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</x-app-layout>
