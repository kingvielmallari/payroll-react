<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ __('My Payslips') }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        View your approved payslips
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <style>
        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50" style="display: none; backdrop-filter: blur(3px);">
        <div class="bg-white p-8 rounded-xl text-center max-w-md mx-4 shadow-2xl">
            <div class="loading-spinner mx-auto mb-4"></div>
            <div class="text-lg font-semibold text-gray-800 mb-2" id="loadingText">Sending Email...</div>
            <div class="text-sm text-gray-600" id="loadingSubtext">Please wait while we process your request.</div>
        </div>
    </div>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <!-- Filter Inputs and Action Buttons in 1 Row -->
                    <div class="flex flex-wrap items-end gap-4 mb-4 w-full">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm payroll-filter focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Pay Period</label>
                            <select name="pay_period" id="pay_period" class="mt-1 block w-full h-10 px-3 border-gray-300 rounded-md shadow-sm payroll-filter focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">All Periods</option>
                                <!-- Pay periods will be populated dynamically based on schedule selection -->
                            </select>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" id="reset_filters" class="inline-flex items-center px-4 h-10 bg-gray-600 border border-transparent rounded-md text-white text-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-6">
                <!-- Total Net Pay -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Net Pay</dt>
                                    <dd class="text-lg font-medium text-gray-900">₱{{ number_format($summaryStats['total_net_pay'] ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Deductions -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Deductions</dt>
                                    <dd class="text-lg font-medium text-gray-900">₱{{ number_format($summaryStats['total_deductions'] ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Gross Pay -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Gross Pay</dt>
                                    <dd class="text-lg font-medium text-gray-900">₱{{ number_format($summaryStats['total_gross_pay'] ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payslips Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">My Payslips</h3>
                        <div class="text-sm text-gray-600">
                            <div>Showing {{ $payrolls->count() }} of {{ $payrolls->total() }} payslips</div>
                        </div>
                    </div>

                    <div id="payroll-list-container">
                        @include('payslips.partials.payroll-list', ['payrolls' => $payrolls])
                    </div>

                    <div id="pagination-container">
                        @include('payslips.partials.pagination', ['payrolls' => $payrolls])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.payroll-filter');
            const payPeriodSelect = document.getElementById('pay_period');

            // Function to update pay periods (fetch all available periods)
            function updatePayPeriods() {
                // Fetch all available pay periods for approved payrolls
                fetch(`{{ route('payslips.index') }}?action=get_periods`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.periods) {
                        payPeriodSelect.innerHTML = '<option value="">All Periods</option>';
                        data.periods.forEach(period => {
                            const option = document.createElement('option');
                            option.value = period.value;
                            option.textContent = period.label;
                            payPeriodSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching periods:', error));
            }

            // Function to apply filters via AJAX (no page reload)
            // applyFilters function is defined later with context menu support

            // Add event listeners for live filtering
            filterSelects.forEach(select => {
                select.addEventListener('change', applyFilters);
            });

            // Initialize pay periods on page load
            updatePayPeriods();
            // Set the selected pay period if it exists in URL
            const urlParams = new URLSearchParams(window.location.search);
            const selectedPeriod = urlParams.get('pay_period');
            if (selectedPeriod) {
                setTimeout(() => {
                    payPeriodSelect.value = selectedPeriod;
                }, 500); // Wait for periods to load
            }

            // Handle per page selection
            const perPageSelect = document.getElementById('per_page');
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function() {
                    applyFilters(); // Use AJAX instead of page reload
                });
            }

            // Reset filters functionality
            document.getElementById('reset_filters').addEventListener('click', function() {
                window.location.href = '{{ route("payslips.index") }}';
            });

            // Context Menu functionality
            let contextMenu = null;
            let currentPayrollId = null;
            let currentPayrollDetailId = null;
            let contextMenuInitialized = false;

            function initializeContextMenuActions() {
                if (contextMenuInitialized) return;
                
                contextMenu = document.getElementById('payslipContextMenu');
                
                // Context menu actions - only initialize once
                document.getElementById('viewPayslip').addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent anchor hash from being added to URL
                    if (currentPayrollId) {
                        window.open(`{{ url('payrolls') }}/${currentPayrollId}/payslip`, '_blank');
                    }
                    hideContextMenu();
                });

                document.getElementById('downloadPayslip').addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent anchor hash from being added to URL
                    if (currentPayrollId) {
                        // Use the same route as individual payslip view (payrolls.payslip.download)
                        window.location.href = `{{ url('payrolls') }}/${currentPayrollId}/payslip/download`;
                    } else {
                        alert('Unable to download payslip. Please try again.');
                    }
                    hideContextMenu();
                });

                document.getElementById('sendPayslip').addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent anchor hash from being added to URL
                    if (currentPayrollId) {
                        const employeeName = '{{ auth()->user()->name }}';
                        const employeeEmail = '{{ auth()->user()->email }}';
                        
                        if (confirm(`Send payslip to ${employeeName} via email?\nEmail address: ${employeeEmail}`)) {
                            // Store the payroll ID before hiding context menu (which clears the variables)
                            const payrollIdToSend = currentPayrollId;
                            
                            // Hide context menu before showing loading screen
                            hideContextMenu();
                            
                            // Show loading screen
                            showLoading('Sending Payslip...', `Sending payslip to ${employeeName}. Please wait while we generate and send the PDF.`);
                            
                            // Use employee-specific route that doesn't require special permissions
                            const formData = new FormData();
                            formData.append('_token', '{{ csrf_token() }}');
                            
                            fetch(`{{ url('payrolls') }}/${payrollIdToSend}/email-employee-payslip`, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) {
                                    hideLoading();
                                    if (response.status === 403) {
                                        throw new Error('You do not have access to this payroll.');
                                    }
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                hideLoading();
                                
                                // Small delay to ensure loading overlay is fully hidden before alert
                                setTimeout(() => {
                                    if (data.success) {
                                        alert('Payslip sent successfully to your email!');
                                        
                                        // Create timestamp
                                        const timestamp = 'Sent: ' + new Date().toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        });
                                        
                                        // Update row data attributes for persistent changes FIRST
                                        const payslipRow = document.querySelector(`[data-payroll-id="${payrollIdToSend}"]`);
                                        if (payslipRow) {
                                            payslipRow.setAttribute('data-send-status', 'Sent');
                                            payslipRow.setAttribute('data-send-details', timestamp);
                                        }
                                        
                                        // Update context menu to show "Resend" status
                                        updateSendPayslipContextMenu('Sent', timestamp);
                                    } else {
                                        alert('Failed to send payslip: ' + (data.message || 'Unknown error'));
                                    }
                                }, 100);
                            })
                            .catch(error => {
                                hideLoading();
                                
                                // Small delay to ensure loading overlay is fully hidden before alert
                                setTimeout(() => {
                                    console.error('Error:', error);
                                    alert('Failed to send payslip: ' + error.message);
                                }, 100);
                            });
                        }
                    } else {
                        alert('Unable to send payslip. Please try again.');
                    }
                });
                
                contextMenuInitialized = true;
            }

            function attachContextMenuListeners() {
                // Add right-click event listeners to payslip rows
                document.querySelectorAll('.payslip-row').forEach(row => {
                    row.addEventListener('contextmenu', function(e) {
                        // Only show context menu for non-processing payslips
                        if (this.dataset.status === 'processing') {
                            return;
                        }
                        
                        e.preventDefault();
                        e.stopPropagation();
                        
                        currentPayrollId = this.dataset.payrollId;
                        currentPayrollDetailId = this.dataset.payrollDetailId;
                        
                        // Update context menu header info
                        const payslipNumber = this.dataset.payrollNumber;
                        const period = this.querySelector('td:nth-child(2) .text-sm')?.textContent || 'N/A';
                        document.getElementById('contextPayslipNumber').textContent = payslipNumber || 'Unknown';
                        document.getElementById('contextPayslipPeriod').textContent = period;
                        
                        // Get current send status from row data attributes (for real-time updates)
                        const currentSendStatus = this.getAttribute('data-send-status');
                        const currentSendDetails = this.getAttribute('data-send-details');
                        
                        // Update send payslip status in context menu
                        updateSendPayslipContextMenu(currentSendStatus, currentSendDetails);
                        

                        
                        // Position context menu with proper bounds checking
                        let x = e.clientX;
                        let y = e.clientY;
                        
                        // Adjust position if menu would go off screen
                        let menuWidth = 208; // min-w-52 = 13rem = 208px
                        let menuHeight = 200; // approximate height
                        
                        if (x + menuWidth > window.innerWidth) {
                            x = window.innerWidth - menuWidth - 10;
                        }
                        
                        if (y + menuHeight > window.innerHeight) {
                            y = window.innerHeight - menuHeight - 10;
                        }
                        
                        contextMenu.style.left = x + 'px';
                        contextMenu.style.top = y + 'px';
                        contextMenu.classList.remove('hidden');
                        
                        // Animate in
                        setTimeout(() => {
                            contextMenu.classList.remove('opacity-0', 'scale-95');
                            contextMenu.classList.add('opacity-100', 'scale-100');
                        }, 10);
                    });
                });
            }

            function hideContextMenu() {
                if (contextMenu) {
                    contextMenu.classList.add('opacity-0', 'scale-95');
                    contextMenu.classList.remove('opacity-100', 'scale-100');
                    
                    setTimeout(() => {
                        contextMenu.classList.add('hidden');
                    }, 150);
                    
                    currentPayrollId = null;
                    currentPayrollDetailId = null;
                }
            }

            // Hide context menu when clicking elsewhere
            document.addEventListener('click', function(event) {
                if (contextMenu && !contextMenu.contains(event.target)) {
                    hideContextMenu();
                }
            });
            document.addEventListener('scroll', hideContextMenu);

            // Initialize context menu on page load
            initializeContextMenuActions();
            attachContextMenuListeners();

            // Re-initialize context menu after AJAX updates
            function applyFilters() {
                const formData = new FormData();
                formData.append('status', document.getElementById('status').value);
                formData.append('pay_period', document.getElementById('pay_period').value);
                formData.append('per_page', document.getElementById('per_page')?.value || 10);

                fetch('{{ route("payslips.index") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update payroll list
                    document.getElementById('payroll-list-container').innerHTML = data.html;
                    
                    // Update pagination
                    document.getElementById('pagination-container').innerHTML = data.pagination;
                    
                    // Re-attach context menu listeners for new content
                    attachContextMenuListeners();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Loading helper functions
            function showLoading(title = 'Processing...', message = 'Please wait while we process your request.') {
                const loadingText = document.getElementById('loadingText');
                const loadingSubtext = document.getElementById('loadingSubtext');
                const loadingOverlay = document.getElementById('loadingOverlay');
                
                if (loadingText) loadingText.textContent = title;
                if (loadingSubtext) loadingSubtext.textContent = message;
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'flex';
                    loadingOverlay.style.opacity = '1';
                }
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            }
            
            function hideLoading() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) {
                    overlay.style.display = 'none';
                    overlay.style.opacity = '0';
                }
                document.body.style.overflow = 'auto'; // Restore scrolling
            }

            function updateSendPayslipContextMenu(sendStatus, sendDetails) {
                const sendTextElement = document.getElementById('contextPayslipSendText');
                const sendStatusElement = document.getElementById('contextPayslipSendStatus');
                
                if (sendTextElement && sendStatusElement) {
                    if (sendStatus === 'Sent') {
                        sendTextElement.textContent = 'Resend Payslip';
                        sendStatusElement.textContent = sendDetails;
                        sendStatusElement.className = 'text-xs text-gray-500';
                    } else {
                        sendTextElement.textContent = 'Send Payslip';
                        sendStatusElement.textContent = '';
                        sendStatusElement.className = 'text-xs text-gray-500';
                    }
                }
            }


        });
    </script>
</x-app-layout>