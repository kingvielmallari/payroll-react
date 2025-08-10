<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Simple DTR - {{ $employee->first_name }} {{ $employee->last_name }}
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

            <!-- Simple DTR Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($dtrData as $day)
                <div class="bg-white shadow-lg rounded-lg p-4 {{ $day['is_weekend'] || $day['is_holiday'] ? 'bg-yellow-50 border-l-4 border-yellow-400' : 'border-l-4 border-blue-400' }}">
                    <!-- Date Header -->
                    <div class="text-center mb-4">
                        <div class="text-2xl font-bold text-gray-800">{{ $day['day'] }}</div>
                        <div class="text-sm font-medium text-gray-600">{{ $day['day_name'] }}</div>
                        <div class="text-xs text-gray-500">{{ $day['date']->format('M d, Y') }}</div>
                        @if($day['is_holiday'])
                        <div class="text-xs text-red-600 font-bold mt-1">🎉 {{ $day['is_holiday'] }}</div>
                        @elseif($day['is_weekend'])
                        <div class="text-xs text-blue-600 mt-1">Weekend</div>
                        @endif
                    </div>

                    <!-- Time Inputs -->
                    <div class="space-y-3">
                        <!-- Time In -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Time In</label>
                            <div class="relative">
                                <input type="time" 
                                       class="time-input w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="{{ $day['time_in'] ? \Carbon\Carbon::parse($day['time_in'])->format('H:i') : '' }}"
                                       data-field="time_in"
                                       data-date="{{ $day['date']->format('Y-m-d') }}">
                                <button type="button" class="clock-btn absolute right-2 top-2 text-gray-400 hover:text-blue-500" data-field="time_in">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Break In -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Break In</label>
                            <div class="relative">
                                <input type="time" 
                                       class="time-input w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="{{ $day['break_in'] ? \Carbon\Carbon::parse($day['break_in'])->format('H:i') : '' }}"
                                       data-field="break_in"
                                       data-date="{{ $day['date']->format('Y-m-d') }}">
                                <button type="button" class="clock-btn absolute right-2 top-2 text-gray-400 hover:text-blue-500" data-field="break_in">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Break Out -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Break Out</label>
                            <div class="relative">
                                <input type="time" 
                                       class="time-input w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="{{ $day['break_out'] ? \Carbon\Carbon::parse($day['break_out'])->format('H:i') : '' }}"
                                       data-field="break_out"
                                       data-date="{{ $day['date']->format('Y-m-d') }}">
                                <button type="button" class="clock-btn absolute right-2 top-2 text-gray-400 hover:text-blue-500" data-field="break_out">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Time Out -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Time Out</label>
                            <div class="relative">
                                <input type="time" 
                                       class="time-input w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="{{ $day['time_out'] ? \Carbon\Carbon::parse($day['time_out'])->format('H:i') : '' }}"
                                       data-field="time_out"
                                       data-date="{{ $day['date']->format('Y-m-d') }}">
                                <button type="button" class="clock-btn absolute right-2 top-2 text-gray-400 hover:text-blue-500" data-field="time_out">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Remarks</label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   value="{{ $day['remarks'] ?? '' }}"
                                   data-field="remarks"
                                   data-date="{{ $day['date']->format('Y-m-d') }}"
                                   placeholder="Add remarks...">
                        </div>
                    </div>

                    <!-- Hours Summary -->
                    <div class="mt-4 p-2 bg-gray-50 rounded text-center">
                        <div class="text-xs text-gray-600 mb-1">Hours Summary</div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-green-600 font-medium">{{ number_format($day['regular_hours'], 1) }}h</span>
                                <div class="text-gray-500">Regular</div>
                            </div>
                            <div>
                                <span class="text-purple-600 font-medium">{{ number_format($day['overtime_hours'], 1) }}h</span>
                                <div class="text-gray-500">Overtime</div>
                            </div>
                        </div>
                        @if($day['late_hours'] > 0)
                        <div class="text-red-600 text-xs mt-1">
                            Late: {{ number_format($day['late_hours'], 1) }}h
                        </div>
                        @endif
                    </div>

                    <!-- Save Button -->
                    <button type="button" 
                            class="save-btn w-full mt-3 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150"
                            data-date="{{ $day['date']->format('Y-m-d') }}"
                            style="display: none;">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Changes
                    </button>
                </div>
                @endforeach
            </div>

            <!-- Overall Summary -->
            <div class="mt-8 bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-6 text-white">
                <h3 class="text-lg font-semibold mb-4">Period Summary - {{ $currentPeriod['period_label'] }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold">{{ collect($dtrData)->where('time_log', '!=', null)->count() }}</div>
                        <div class="text-blue-100 text-sm">Days Present</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ number_format(collect($dtrData)->sum('regular_hours'), 1) }}h</div>
                        <div class="text-blue-100 text-sm">Regular Hours</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ number_format(collect($dtrData)->sum('overtime_hours'), 1) }}h</div>
                        <div class="text-blue-100 text-sm">Overtime Hours</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ number_format(collect($dtrData)->sum('late_hours'), 1) }}h</div>
                        <div class="text-blue-100 text-sm">Late Hours</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ number_format(collect($dtrData)->sum('total_hours'), 1) }}h</div>
                        <div class="text-blue-100 text-sm">Total Hours</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clock Modal -->
    <div id="clockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Select Time</h3>
                
                <!-- Digital Clock Display -->
                <div class="mb-4 p-4 bg-gray-100 rounded-lg">
                    <div class="text-2xl font-mono" id="digitalTime">12:00</div>
                    <div class="flex justify-center mt-2">
                        <button type="button" id="amBtn" class="px-3 py-1 mx-1 text-sm bg-gray-300 rounded hover:bg-blue-500 hover:text-white transition">AM</button>
                        <button type="button" id="pmBtn" class="px-3 py-1 mx-1 text-sm bg-gray-300 rounded hover:bg-blue-500 hover:text-white transition">PM</button>
                    </div>
                </div>

                <!-- Analog Clock -->
                <div class="mb-4 flex justify-center">
                    <div class="relative w-48 h-48 border-4 border-gray-300 rounded-full bg-white">
                        <!-- Clock face -->
                        <svg class="w-full h-full" viewBox="0 0 200 200">
                            <!-- Hour markers -->
                            <g stroke="#374151" stroke-width="2" fill="#374151">
                                @for($i = 1; $i <= 12; $i++)
                                    @php
                                        $angle = ($i * 30) - 90;
                                        $x1 = 100 + 80 * cos(deg2rad($angle));
                                        $y1 = 100 + 80 * sin(deg2rad($angle));
                                        $x2 = 100 + 90 * cos(deg2rad($angle));
                                        $y2 = 100 + 90 * sin(deg2rad($angle));
                                    @endphp
                                    <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}"></line>
                                    <text x="{{ 100 + 70 * cos(deg2rad($angle)) }}" y="{{ 100 + 70 * sin(deg2rad($angle)) + 5 }}" text-anchor="middle" font-size="12">{{ $i }}</text>
                                @endfor
                            </g>
                            <!-- Hour hand -->
                            <line id="hourHand" x1="100" y1="100" x2="100" y2="50" stroke="#1f2937" stroke-width="4" stroke-linecap="round"></line>
                            <!-- Minute hand -->
                            <line id="minuteHand" x1="100" y1="100" x2="100" y2="30" stroke="#374151" stroke-width="3" stroke-linecap="round"></line>
                            <!-- Center dot -->
                            <circle cx="100" cy="100" r="5" fill="#1f2937"></circle>
                        </svg>
                    </div>
                </div>

                <!-- Time Selectors -->
                <div class="flex justify-center space-x-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hour</label>
                        <select id="hourSelect" class="border border-gray-300 rounded px-3 py-1">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minute</label>
                        <select id="minuteSelect" class="border border-gray-300 rounded px-3 py-1">
                            @for($i = 0; $i < 60; $i += 5)
                                <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-center space-x-4">
                    <button type="button" id="cancelClock" class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded hover:bg-gray-600 focus:outline-none">
                        Cancel
                    </button>
                    <button type="button" id="setClock" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 focus:outline-none">
                        Set Time
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Simple DTR functionality -->
    <script>
        let currentInput = null;
        let currentHour = 12;
        let currentMinute = 0;
        let isAM = true;

        document.addEventListener('DOMContentLoaded', function() {
            // Time input change detection
            document.querySelectorAll('input[data-field]').forEach(input => {
                input.addEventListener('change', function() {
                    const card = this.closest('.bg-white');
                    const saveBtn = card.querySelector('.save-btn');
                    saveBtn.style.display = 'block';
                });
            });

            // Clock button handlers
            document.querySelectorAll('.clock-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const input = this.parentElement.querySelector('input');
                    openClockModal(input);
                });
            });

            // Save button handlers
            document.querySelectorAll('.save-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    saveDTREntry(this.dataset.date, this);
                });
            });

            // Clock modal functionality
            setupClockModal();
        });

        function openClockModal(input) {
            currentInput = input;
            const modal = document.getElementById('clockModal');
            
            // Parse existing time or set default
            if (input.value) {
                const [hours, minutes] = input.value.split(':');
                currentHour = parseInt(hours) > 12 ? parseInt(hours) - 12 : parseInt(hours);
                currentHour = currentHour === 0 ? 12 : currentHour;
                currentMinute = parseInt(minutes);
                isAM = parseInt(hours) < 12;
            } else {
                currentHour = 8;
                currentMinute = 0;
                isAM = true;
            }

            updateClockDisplay();
            modal.classList.remove('hidden');
        }

        function setupClockModal() {
            const modal = document.getElementById('clockModal');
            const cancelBtn = document.getElementById('cancelClock');
            const setBtn = document.getElementById('setClock');
            const hourSelect = document.getElementById('hourSelect');
            const minuteSelect = document.getElementById('minuteSelect');
            const amBtn = document.getElementById('amBtn');
            const pmBtn = document.getElementById('pmBtn');

            cancelBtn.addEventListener('click', () => {
                modal.classList.add('hidden');
                currentInput = null;
            });

            setBtn.addEventListener('click', () => {
                if (currentInput) {
                    const hour24 = isAM ? 
                        (currentHour === 12 ? 0 : currentHour) : 
                        (currentHour === 12 ? 12 : currentHour + 12);
                    
                    currentInput.value = `${String(hour24).padStart(2, '0')}:${String(currentMinute).padStart(2, '0')}`;
                    currentInput.dispatchEvent(new Event('change'));
                }
                modal.classList.add('hidden');
                currentInput = null;
            });

            hourSelect.addEventListener('change', (e) => {
                currentHour = parseInt(e.target.value);
                updateClockDisplay();
            });

            minuteSelect.addEventListener('change', (e) => {
                currentMinute = parseInt(e.target.value);
                updateClockDisplay();
            });

            amBtn.addEventListener('click', () => {
                isAM = true;
                updateClockDisplay();
            });

            pmBtn.addEventListener('click', () => {
                isAM = false;
                updateClockDisplay();
            });

            // Click outside to close
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    currentInput = null;
                }
            });
        }

        function updateClockDisplay() {
            // Update digital display
            const digitalTime = document.getElementById('digitalTime');
            digitalTime.textContent = `${currentHour}:${String(currentMinute).padStart(2, '0')}`;

            // Update AM/PM buttons
            const amBtn = document.getElementById('amBtn');
            const pmBtn = document.getElementById('pmBtn');
            
            amBtn.className = isAM ? 'px-3 py-1 mx-1 text-sm bg-blue-500 text-white rounded' : 'px-3 py-1 mx-1 text-sm bg-gray-300 rounded hover:bg-blue-500 hover:text-white transition';
            pmBtn.className = !isAM ? 'px-3 py-1 mx-1 text-sm bg-blue-500 text-white rounded' : 'px-3 py-1 mx-1 text-sm bg-gray-300 rounded hover:bg-blue-500 hover:text-white transition';

            // Update selectors
            document.getElementById('hourSelect').value = currentHour;
            document.getElementById('minuteSelect').value = String(currentMinute).padStart(2, '0');

            // Update analog clock
            updateAnalogClock();
        }

        function updateAnalogClock() {
            const hourHand = document.getElementById('hourHand');
            const minuteHand = document.getElementById('minuteHand');

            // Calculate angles
            const minuteAngle = (currentMinute * 6) - 90; // 6 degrees per minute
            const hourAngle = ((currentHour % 12) * 30 + currentMinute * 0.5) - 90; // 30 degrees per hour + minute adjustment

            // Update hour hand
            const hourX = 100 + 40 * Math.cos(hourAngle * Math.PI / 180);
            const hourY = 100 + 40 * Math.sin(hourAngle * Math.PI / 180);
            hourHand.setAttribute('x2', hourX);
            hourHand.setAttribute('y2', hourY);

            // Update minute hand
            const minuteX = 100 + 60 * Math.cos(minuteAngle * Math.PI / 180);
            const minuteY = 100 + 60 * Math.sin(minuteAngle * Math.PI / 180);
            minuteHand.setAttribute('x2', minuteX);
            minuteHand.setAttribute('y2', minuteY);
        }

        function saveDTREntry(date, button) {
            const card = button.closest('.bg-white');
            const inputs = card.querySelectorAll('input[data-field]');
            
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
            data.is_holiday = false;
            data.is_rest_day = false;

            // Show loading state
            const originalHtml = button.innerHTML;
            button.innerHTML = '<svg class="animate-spin w-4 h-4 inline mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...';
            button.disabled = true;

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
                    button.style.display = 'none';
                    // Show success feedback
                    button.innerHTML = '<svg class="w-4 h-4 inline mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Saved!';
                    setTimeout(() => {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                        // Reload page to update calculations
                        window.location.reload();
                    }, 1500);
                } else {
                    alert('Error updating time entry');
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating time entry');
                button.innerHTML = originalHtml;
                button.disabled = false;
            });
        }
    </script>
</x-app-layout>
