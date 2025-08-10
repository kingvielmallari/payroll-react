<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    DTR - {{ $employee->first_name }} {{ $employee->last_name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $employee->employee_number }} • {{ $employee->department->name ?? 'No Department' }}</p>
            </div>
            <div class="text-right">
                <div class="font-semibold text-gray-900">{{ $payrollSettings->frequency === 'semi_monthly' ? 'Semi-Monthly' : 'Monthly' }} Payroll</div>
                <div class="text-sm text-gray-600">{{ $currentPeriod['period_label'] }} • {{ $currentPeriod['pay_label'] }}</div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('time-logs.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to DTR List
                </a>
            </div>

            <!-- DTR Card -->
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold">DAILY TIME RECORD</h1>
                            <p class="text-blue-100 text-sm">{{ strtoupper($employee->first_name . ' ' . $employee->last_name) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-blue-100">For the Period of</p>
                            <p class="text-xl font-bold">{{ strtoupper($currentPeriod['period_label']) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-blue-100">
                        <p>Regular Days: {{ collect($dtrData)->where('is_weekend', false)->where('is_holiday', null)->count() }}</p>
                    </div>
                </div>

                <!-- DTR Table -->
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-gray-400">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th rowspan="2" class="border border-gray-400 p-2 text-xs font-bold text-center w-12">Day</th>
                                    <th colspan="2" class="border border-gray-400 p-2 text-xs font-bold text-center">AM</th>
                                    <th colspan="2" class="border border-gray-400 p-2 text-xs font-bold text-center">PM</th>
                                    <th rowspan="2" class="border border-gray-400 p-2 text-xs font-bold text-center w-16">Over Time</th>
                                    <th rowspan="2" class="border border-gray-400 p-2 text-xs font-bold text-center w-24">Remarks</th>
                                    <th rowspan="2" class="border border-gray-400 p-2 text-xs font-bold text-center w-12">Actions</th>
                                </tr>
                                <tr class="bg-gray-100">
                                    <th class="border border-gray-400 p-1 text-xs font-bold text-center w-20">Arrival</th>
                                    <th class="border border-gray-400 p-1 text-xs font-bold text-center w-20">Depart</th>
                                    <th class="border border-gray-400 p-1 text-xs font-bold text-center w-20">Arrival</th>
                                    <th class="border border-gray-400 p-1 text-xs font-bold text-center w-20">Depart</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dtrData as $day)
                                <tr class="hover:bg-gray-50 {{ $day['is_weekend'] || $day['is_holiday'] ? 'bg-yellow-50' : '' }}" 
                                    data-date="{{ $day['date']->format('Y-m-d') }}">
                                    <td class="border border-gray-400 p-2 text-center font-medium">
                                        <div class="text-sm">{{ $day['day'] }}</div>
                                        <div class="text-xs text-gray-600">{{ substr($day['day_name'], 0, 3) }}</div>
                                        @if($day['is_holiday'])
                                        <div class="text-xs text-red-600 font-bold">{{ $day['is_holiday'] }}</div>
                                        @elseif($day['is_weekend'])
                                        <div class="text-xs text-blue-600">Weekend</div>
                                        @endif
                                    </td>
                                    
                                    <!-- AM Arrival -->
                                    <td class="border border-gray-400 p-1 text-center">
                                        <input type="time" 
                                               class="time-input w-full text-xs border-0 text-center bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded"
                                               value="{{ $day['time_in'] ? \Carbon\Carbon::parse($day['time_in'])->format('H:i') : '' }}"
                                               data-field="time_in"
                                               {{ $day['is_weekend'] || $day['is_holiday'] ? 'style=background-color:#fef3c7' : '' }}>
                                    </td>
                                    
                                    <!-- AM Depart -->
                                    <td class="border border-gray-400 p-1 text-center">
                                        <input type="time" 
                                               class="time-input w-full text-xs border-0 text-center bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded"
                                               value="{{ $day['break_in'] ? \Carbon\Carbon::parse($day['break_in'])->format('H:i') : '' }}"
                                               data-field="break_in"
                                               {{ $day['is_weekend'] || $day['is_holiday'] ? 'style=background-color:#fef3c7' : '' }}>
                                    </td>
                                    
                                    <!-- PM Arrival -->
                                    <td class="border border-gray-400 p-1 text-center">
                                        <input type="time" 
                                               class="time-input w-full text-xs border-0 text-center bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded"
                                               value="{{ $day['break_out'] ? \Carbon\Carbon::parse($day['break_out'])->format('H:i') : '' }}"
                                               data-field="break_out"
                                               {{ $day['is_weekend'] || $day['is_holiday'] ? 'style=background-color:#fef3c7' : '' }}>
                                    </td>
                                    
                                    <!-- PM Depart -->
                                    <td class="border border-gray-400 p-1 text-center">
                                        <input type="time" 
                                               class="time-input w-full text-xs border-0 text-center bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded"
                                               value="{{ $day['time_out'] ? \Carbon\Carbon::parse($day['time_out'])->format('H:i') : '' }}"
                                               data-field="time_out"
                                               {{ $day['is_weekend'] || $day['is_holiday'] ? 'style=background-color:#fef3c7' : '' }}>
                                    </td>
                                    
                                    <!-- Overtime -->
                                    <td class="border border-gray-400 p-1 text-center">
                                        <div class="text-xs font-medium {{ $day['overtime_hours'] > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                            {{ $day['overtime_hours'] > 0 ? number_format($day['overtime_hours'], 1) . 'h' : '-' }}
                                        </div>
                                    </td>
                                    
                                    <!-- Remarks -->
                                    <td class="border border-gray-400 p-1">
                                        <input type="text" 
                                               class="w-full text-xs border-0 bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded px-1"
                                               value="{{ $day['remarks'] ?? '' }}"
                                               data-field="remarks"
                                               placeholder="Remarks"
                                               {{ $day['is_weekend'] || $day['is_holiday'] ? 'style=background-color:#fef3c7' : '' }}>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td class="border border-gray-400 p-1 text-center">
                                        <button type="button" 
                                                class="save-btn inline-flex items-center px-2 py-1 bg-blue-600 border border-transparent rounded text-xs text-white font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition duration-150"
                                                style="display: none;">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Footer -->
                <div class="bg-gray-100 px-6 py-4 border-t">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                        <div class="text-center">
                            <div class="font-bold text-lg text-blue-600">
                                {{ collect($dtrData)->where('time_log', '!=', null)->count() }}
                            </div>
                            <div class="text-gray-600">Days Present</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-lg text-green-600">
                                {{ number_format(collect($dtrData)->sum('regular_hours'), 1) }}h
                            </div>
                            <div class="text-gray-600">Regular Hours</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-lg text-purple-600">
                                {{ number_format(collect($dtrData)->sum('overtime_hours'), 1) }}h
                            </div>
                            <div class="text-gray-600">Overtime Hours</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-lg text-red-600">
                                {{ number_format(collect($dtrData)->sum('late_hours'), 1) }}h
                            </div>
                            <div class="text-gray-600">Late Hours</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-lg text-gray-800">
                                {{ number_format(collect($dtrData)->sum('total_hours'), 1) }}h
                            </div>
                            <div class="text-gray-600">Total Hours</div>
                        </div>
                    </div>
                </div>

                <!-- Certification Footer -->
                <div class="bg-white px-6 py-4 border-t text-center">
                    <p class="text-xs text-gray-600 mb-2">
                        I certify on my honor that the above is a true and correct report of the hours of work performed, 
                        record of which was made daily at the time of arrival and departure from office.
                    </p>
                    <div class="mt-4 text-center">
                        <div class="text-sm font-bold text-gray-800 border-t border-gray-400 inline-block px-8 pt-1">
                            {{ strtoupper($employee->first_name . ' ' . $employee->last_name) }}
                        </div>
                        <div class="text-xs text-gray-600 mt-1">Verified as to the prescribed office hour</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for DTR functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timeInputs = document.querySelectorAll('.time-input, input[data-field="remarks"]');
            
            timeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const saveBtn = row.querySelector('.save-btn');
                    saveBtn.style.display = 'inline-flex';
                });

                input.addEventListener('focus', function() {
                    this.classList.add('border', 'border-blue-300', 'bg-white');
                });

                input.addEventListener('blur', function() {
                    this.classList.remove('border', 'border-blue-300');
                    if (!this.value) {
                        this.classList.remove('bg-white');
                    }
                });
            });

            // Save button click handlers
            document.querySelectorAll('.save-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const date = row.dataset.date;
                    const inputs = row.querySelectorAll('input[data-field]');
                    
                    const data = {
                        employee_id: {{ $employee->id }},
                        log_date: date,
                        _token: '{{ csrf_token() }}'
                    };

                    inputs.forEach(input => {
                        data[input.dataset.field] = input.value;
                    });

                    // Add default values
                    data.log_type = 'regular';
                    data.is_holiday = false; // Will be determined by server
                    data.is_rest_day = false;

                    // Show loading state
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<div class="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>';
                    this.disabled = true;

                    fetch('{{ route("time-logs.update-time-entry") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.style.display = 'none';
                            // Show success feedback
                            this.innerHTML = '<svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
                            setTimeout(() => {
                                this.innerHTML = originalHtml;
                                this.disabled = false;
                                // Reload page to update calculations
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert('Error updating time entry');
                            this.innerHTML = originalHtml;
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating time entry');
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    });
                });
            });
        });
    </script>
</x-app-layout>
