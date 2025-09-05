<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Employee') }}
            </h2>
            <a href="{{ route('employees.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Employees
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('employees.store') }}" class="space-y-6">
                @csrf

                <!-- Personal Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize-input">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize-input">
                                @error('middle_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize-input">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                                <input type="text" name="suffix" id="suffix" value="{{ old('suffix') }}" placeholder="Jr., Sr., III"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize-input">
                                @error('suffix')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700">Birth Date <span class="text-red-500">*</span></label>
                                <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('birth_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                                <select name="gender" id="gender" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @error('gender')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="civil_status" class="block text-sm font-medium text-gray-700">Civil Status <span class="text-red-500">*</span></label>
                                <select name="civil_status" id="civil_status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Status</option>
                                    <option value="single" {{ old('civil_status') == 'single' ? 'selected' : '' }}>Single</option>
                                    <option value="married" {{ old('civil_status') == 'married' ? 'selected' : '' }}>Married</option>
                                    <option value="divorced" {{ old('civil_status') == 'divorced' ? 'selected' : '' }}>Divorced</option>
                                    <option value="widowed" {{ old('civil_status') == 'widowed' ? 'selected' : '' }}>Widowed</option>
                                </select>
                                @error('civil_status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="09XXXXXXXXX"
                                       maxlength="11" minlength="11" pattern="[0-9]{11}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Must be exactly 11 digits (e.g., 09123456789)</p>
                            </div>
                        </div>

                        <div >
                            <label for="address" class="block text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                            <input type="text" name="address" id="address" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize-input" 
                                   value="{{ old('address') }}">
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm lowercase-input">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">User Role <span class="text-red-500">*</span></label>
                                <select name="role" id="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p class="mt-2 text-sm text-gray-600">
                            <strong>Note:</strong> Default password will be set to the <span id="password-preview">employee number</span>. The employee should change this on first login.
                        </p>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Employment Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="employee_number" class="block text-sm font-medium text-gray-700">Employee Number <span class="text-red-500">*</span></label>
                                <div class="mt-1 flex">
                                    <input type="text" name="employee_number" id="employee_number" value="{{ old('employee_number', ($employeeSettings['auto_generate_employee_number'] ?? false) ? $nextEmployeeNumber : '') }}" required
                                           placeholder="{{ ($employeeSettings['auto_generate_employee_number'] ?? false) ? $nextEmployeeNumber : 'Enter employee number' }}"
                                           class="block w-full {{ ($employeeSettings['auto_generate_employee_number'] ?? false) ? 'rounded-l-md' : 'rounded-md' }} border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           {{ ($employeeSettings['auto_generate_employee_number'] ?? false) ? 'readonly' : '' }}>
                                    @if($employeeSettings['auto_generate_employee_number'] ?? false)
                                        <button type="button" id="generate-employee-number" 
                                                class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-blue-50 text-blue-600 text-sm hover:bg-blue-100">
                                            Generate
                                        </button>
                                    @endif
                                </div>
                                @if($employeeSettings['auto_generate_employee_number'] ?? false)
                                    <p class="mt-1 text-xs text-gray-500">Employee number will be automatically generated</p>
                                @else
                                    <p class="mt-1 text-xs text-gray-500">Enter employee number manually</p>
                                @endif
                                @error('employee_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="hire_date" class="block text-sm font-medium text-gray-700">Hire Date <span class="text-red-500">*</span></label>
                                <input type="date" name="hire_date" id="hire_date" value="{{ old('hire_date') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('hire_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="department_id" class="block text-sm font-medium text-gray-700">Department <span class="text-red-500">*</span></label>
                                <select name="department_id" id="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" 
                                                {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="position_id" class="block text-sm font-medium text-gray-700">Position <span class="text-red-500">*</span></label>
                                <select name="position_id" id="position_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Position</option>
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}" 
                                                {{ old('position_id') == $position->id ? 'selected' : '' }}
                                                data-department-id="{{ $position->department_id }}">
                                            {{ $position->title }} ({{ $position->department->name ?? 'No Department' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('position_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="employment_type" class="block text-sm font-medium text-gray-700">Employment Type <span class="text-red-500">*</span></label>
                                <select name="employment_type" id="employment_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Type</option>
                                    <option value="regular" {{ old('employment_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="probationary" {{ old('employment_type') == 'probationary' ? 'selected' : '' }}>Probationary</option>
                                    <option value="contractual" {{ old('employment_type') == 'contractual' ? 'selected' : '' }}>Contractual</option>
                                    <option value="part_time" {{ old('employment_type') == 'part_time' ? 'selected' : '' }}>Part Time</option>
                                    <option value="casual" {{ old('employment_type') == 'casual' ? 'selected' : '' }}>Casual</option>
                                </select>
                                @error('employment_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="time_schedule_id" class="block text-sm font-medium text-gray-700">Time Schedule <span class="text-red-500">*</span></label>
                                <select name="time_schedule_id" id="time_schedule_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Schedule</option>
                                    @foreach($timeSchedules as $schedule)
                                        <option value="{{ $schedule->id }}" 
                                                {{ old('time_schedule_id') == $schedule->id ? 'selected' : '' }}>
                                            {{ $schedule->name }} ({{ $schedule->time_range_display }})
                                        </option>
                                    @endforeach
                                    {{-- <option value="custom" {{ old('time_schedule_id') == 'custom' ? 'selected' : '' }}>
                                        Custom Time Schedule
                                    </option> --}}
                                </select>
                                @error('time_schedule_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Employee's shift timing (e.g., 8:00 AM - 5:00 PM)</p>
                            </div>

                            <div>
                                <label for="day_schedule_id" class="block text-sm font-medium text-gray-700">Day Schedule <span class="text-red-500">*</span></label>
                                <select name="day_schedule_id" id="day_schedule_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Day Schedule</option>
                                    @foreach($daySchedules as $daySchedule)
                                        <option value="{{ $daySchedule->id }}" 
                                                {{ old('day_schedule_id') == $daySchedule->id ? 'selected' : '' }}>
                                            {{ $daySchedule->name }} ({{ $daySchedule->days_display }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('day_schedule_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Working days for accurate DTR calculation</p>
                            </div>

                            <div>
                                <label for="pay_schedule" class="block text-sm font-medium text-gray-700">Pay Frequency <span class="text-red-500">*</span></label>
                                <select name="pay_schedule" id="pay_schedule" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Pay Frequency</option>
                                    @foreach($paySchedules as $paySchedule)
                                        <option value="{{ $paySchedule->code }}" 
                                                {{ old('pay_schedule') == $paySchedule->code ? 'selected' : '' }}
                                                {{ !$paySchedule->is_active ? 'disabled' : '' }}>
                                            {{ $paySchedule->name }}
                                            @if(!$paySchedule->is_active)
                                                (Disabled - Not Configured)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('pay_schedule')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">How often the employee gets paid</p>
                            </div>

                            <div>
                                <label for="benefits_status" class="block text-sm font-medium text-gray-700">Benefits Status <span class="text-red-500">*</span></label>
                                <select name="benefits_status" id="benefits_status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Benefits Status</option>
                                    <option value="with_benefits" {{ old('benefits_status') == 'with_benefits' ? 'selected' : '' }}>With Benefits</option>
                                    <option value="without_benefits" {{ old('benefits_status') == 'without_benefits' ? 'selected' : '' }}>Without Benefits</option>
                                </select>
                                @error('benefits_status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Determines eligibility for health insurance, SSS, etc.</p>
                            </div>

                            <div>
                                <label for="paid_leaves" class="block text-sm font-medium text-gray-700">Number of Paid Leaves <span class="text-red-500">*</span></label>
                                <input type="number" name="paid_leaves" id="paid_leaves" value="{{ old('paid_leaves') }}" required min="0" max="365"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('paid_leaves')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Annual paid leave entitlement</p>
                            </div>

                            <div>
                                <label for="employment_status" class="block text-sm font-medium text-gray-700">Employment Status <span class="text-red-500">*</span></label>
                                <select name="employment_status" id="employment_status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Status</option>
                                    <option value="active" {{ old('employment_status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('employment_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="terminated" {{ old('employment_status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                    <option value="resigned" {{ old('employment_status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                </select>
                                @error('employment_status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Salary Information</h3>
                        <p class="text-sm text-gray-600 mb-4">Enter any rate and others will calculate automatically. Based on 8 hours/day, 5 days/week, and 22 days/month.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label for="hourly_rate" class="block text-sm font-medium text-gray-700">Hourly Rate</label>
                                <input type="text" name="hourly_rate" id="hourly_rate" value="{{ old('hourly_rate') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="hourly_rate_raw" id="hourly_rate_raw">
                                @error('hourly_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per hour rate</p>
                            </div>

                            <div>
                                <label for="daily_rate" class="block text-sm font-medium text-gray-700">Daily Rate</label>
                                <input type="text" name="daily_rate" id="daily_rate" value="{{ old('daily_rate') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="daily_rate_raw" id="daily_rate_raw">
                                @error('daily_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per day rate</p>
                            </div>

                            <div>
                                <label for="weekly_rate" class="block text-sm font-medium text-gray-700">Weekly Rate</label>
                                <input type="text" name="weekly_rate" id="weekly_rate" value="{{ old('weekly_rate') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="weekly_rate_raw" id="weekly_rate_raw">
                                @error('weekly_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per week rate</p>
                            </div>

                            <div>
                                <label for="semi_monthly_rate" class="block text-sm font-medium text-gray-700">Semi-Monthly Rate</label>
                                <input type="text" name="semi_monthly_rate" id="semi_monthly_rate" value="{{ old('semi_monthly_rate') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="semi_monthly_rate_raw" id="semi_monthly_rate_raw">
                                @error('semi_monthly_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Twice per month</p>
                            </div>

                            <div>
                                <label for="basic_salary" class="block text-sm font-medium text-gray-700">Monthly Rate</label>
                                <input type="text" name="basic_salary" id="basic_salary" value="{{ old('basic_salary') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="basic_salary_raw" id="basic_salary_raw">
                                @error('basic_salary')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per month rate</p>
                            </div>
                        </div>
                        
                        <!-- Salary Calculation Summary -->
                        {{-- <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Salary Breakdown</h4>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs text-gray-600">
                                <div>Hourly: <span id="calc_hourly" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Daily: <span id="calc_daily" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Weekly: <span id="calc_weekly" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Semi-Monthly: <span id="calc_semi" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Monthly: <span id="calc_monthly" class="font-medium text-gray-900">₱0.00</span></div>
                            </div>
                        </div> --}}
                    </div>
                </div>

                <!-- Government IDs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Government IDs</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="sss_number" class="block text-sm font-medium text-gray-700">SSS Number</label>
                                <input type="text" name="sss_number" id="sss_number" value="{{ old('sss_number') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('sss_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="philhealth_number" class="block text-sm font-medium text-gray-700">PhilHealth Number</label>
                                <input type="text" name="philhealth_number" id="philhealth_number" value="{{ old('philhealth_number') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('philhealth_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="pagibig_number" class="block text-sm font-medium text-gray-700">Pag-IBIG Number</label>
                                <input type="text" name="pagibig_number" id="pagibig_number" value="{{ old('pagibig_number') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('pagibig_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="tin_number" class="block text-sm font-medium text-gray-700">TIN Number</label>
                                <input type="text" name="tin_number" id="tin_number" value="{{ old('tin_number') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('tin_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Emergency Contact</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700">Contact Name</label>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('emergency_contact_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="emergency_contact_relationship" class="block text-sm font-medium text-gray-700">Relationship</label>
                                <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('emergency_contact_relationship')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('emergency_contact_phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Bank Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-gray-700">Bank Name</label>
                                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('bank_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bank_account_number" class="block text-sm font-medium text-gray-700">Account Number</label>
                                <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('bank_account_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bank_account_name" class="block text-sm font-medium text-gray-700">Account Name</label>
                                <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('bank_account_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('employees.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Employee
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .salary-input {
            text-align: right;
            font-weight: 500;
        }
        .salary-input::placeholder {
            font-weight: normal;
            opacity: 0.5;
            text-align: right;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-capitalize function for names and address
            function capitalizeWords(input) {
                let value = input.value;
                // Split by spaces and capitalize first letter of each word
                let words = value.split(' ').map(word => {
                    if (word.length > 0) {
                        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
                    }
                    return word;
                });
                input.value = words.join(' ');
            }

            // Add event listeners for capitalize inputs
            document.querySelectorAll('.capitalize-input').forEach(function(input) {
                input.addEventListener('input', function() {
                    capitalizeWords(this);
                });
                
                input.addEventListener('blur', function() {
                    capitalizeWords(this);
                });
            });

            // Email lowercase conversion
            const emailInput = document.querySelector('.lowercase-input');
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    this.value = this.value.toLowerCase();
                });
            }

            // Update password preview when employee number changes
            const employeeNumberInput = document.getElementById('employee_number');
            const passwordPreview = document.getElementById('password-preview');
            const generateBtn = document.getElementById('generate-employee-number');
            
            // Auto-generate employee number on page load if enabled
            @if($employeeSettings['auto_generate_employee_number'] ?? false)
            window.addEventListener('load', function() {
                // Only auto-generate if field is empty (no old value)
                if (!employeeNumberInput.value.trim()) {
                    generateEmployeeNumber();
                }
            });
            @endif
            
            // Generate employee number function
            function generateEmployeeNumber() {
                generateBtn.disabled = true;
                generateBtn.textContent = 'Loading...';
                
                fetch('{{ route('settings.employee.next-number') }}')
                    .then(response => response.json())
                    .then(data => {
                        employeeNumberInput.value = data.employee_number;
                        updatePasswordPreview();
                    })
                    .catch(error => {
                        console.error('Error generating employee number:', error);
                        alert('Failed to generate employee number. Please try again.');
                    })
                    .finally(() => {
                        generateBtn.disabled = false;
                        generateBtn.textContent = 'Generate';
                    });
            }
            
            // Bind generate button click
            if (generateBtn) {
                generateBtn.addEventListener('click', generateEmployeeNumber);
            }
            
            // Check for duplicate employee numbers on input
            let duplicateCheckTimeout;
            if (employeeNumberInput) {
                employeeNumberInput.addEventListener('input', function() {
                    clearTimeout(duplicateCheckTimeout);
                    duplicateCheckTimeout = setTimeout(() => {
                        checkEmployeeNumberDuplicate(this.value);
                    }, 500); // Debounce for 500ms
                });
            }
            
            function checkEmployeeNumberDuplicate(employeeNumber) {
                if (!employeeNumber.trim()) return;
                
                fetch('{{ route('employees.check-duplicate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ employee_number: employeeNumber })
                })
                .then(response => response.json())
                .then(data => {
                    const existingError = employeeNumberInput.parentElement.parentElement.querySelector('.duplicate-error');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    if (data.exists) {
                        const errorDiv = document.createElement('p');
                        errorDiv.className = 'mt-1 text-sm text-red-600 duplicate-error';
                        errorDiv.textContent = 'This employee number is already taken.';
                        employeeNumberInput.parentElement.parentElement.appendChild(errorDiv);
                        employeeNumberInput.classList.add('border-red-500');
                    } else {
                        employeeNumberInput.classList.remove('border-red-500');
                    }
                })
                .catch(error => {
                    console.error('Error checking duplicate:', error);
                });
            }
            
            if (employeeNumberInput && passwordPreview) {
                function updatePasswordPreview() {
                    const empNumber = employeeNumberInput.value.trim();
                    if (empNumber) {
                        passwordPreview.textContent = `"${empNumber}"`;
                        passwordPreview.style.fontWeight = 'bold';
                        passwordPreview.style.color = '#1f2937'; // gray-800
                    } else {
                        passwordPreview.textContent = 'employee number';
                        passwordPreview.style.fontWeight = 'normal';
                        passwordPreview.style.color = '#6b7280'; // gray-500
                    }
                }
                
                // Update on input change
                employeeNumberInput.addEventListener('input', updatePasswordPreview);
                employeeNumberInput.addEventListener('blur', updatePasswordPreview);
                
                // Initial update if there's already a value
                updatePasswordPreview();
            }

            // Phone number validation - only allow numbers
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    // Remove any non-digit characters
                    this.value = this.value.replace(/\D/g, '');
                    
                    // Limit to 11 digits
                    if (this.value.length > 11) {
                        this.value = this.value.slice(0, 11);
                    }
                });

                phoneInput.addEventListener('keypress', function(e) {
                    // Only allow numbers
                    if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Escape', 'Enter'].includes(e.key)) {
                        e.preventDefault();
                    }
                });
            }

            // Salary calculation - all fields can calculate each other
            const hourlyRateInput = document.getElementById('hourly_rate');
            const dailyRateInput = document.getElementById('daily_rate');
            const basicSalaryInput = document.getElementById('basic_salary');
            
            const hourlyRateRaw = document.getElementById('hourly_rate_raw');
            const dailyRateRaw = document.getElementById('daily_rate_raw');
            const basicSalaryRaw = document.getElementById('basic_salary_raw');
            
            let isCalculating = false; // Prevent infinite loops

            // Format number as Philippine peso
            function formatPeso(value) {
                if (!value || value === 0) return '';
                const num = typeof value === 'string' ? parseFloat(value) : value;
                if (isNaN(num)) return '';
                return '₱' + num.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // Parse peso formatted string to number
            function parsePeso(value) {
                if (!value) return 0;
                // Remove peso sign, commas, and spaces
                const cleaned = value.replace(/[₱,\s]/g, '');
                const num = parseFloat(cleaned);
                return isNaN(num) ? 0 : num;
            }

            // Clear all other fields when current field is cleared
            function clearOtherFields(currentField) {
                if (isCalculating) return;
                
                if (currentField === 'hourly') {
                    dailyRateInput.value = '';
                    basicSalaryInput.value = '';
                    dailyRateRaw.value = '';
                    basicSalaryRaw.value = '';
                } else if (currentField === 'daily') {
                    hourlyRateInput.value = '';
                    basicSalaryInput.value = '';
                    hourlyRateRaw.value = '';
                    basicSalaryRaw.value = '';
                } else if (currentField === 'basic') {
                    hourlyRateInput.value = '';
                    dailyRateInput.value = '';
                    hourlyRateRaw.value = '';
                    dailyRateRaw.value = '';
                }
            }

            function calculateFromHourly() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(hourlyRateInput.value);
                
                // If input is empty or zero, clear other fields
                if (!hourlyRateInput.value.trim() || rawValue === 0) {
                    clearOtherFields('hourly');
                    hourlyRateRaw.value = '';
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const dailyRate = rawValue * 8;
                    const monthlySalary = dailyRate * 22;
                    
                    dailyRateInput.value = formatPeso(dailyRate);
                    basicSalaryInput.value = formatPeso(monthlySalary);
                    
                    // Update raw values for form submission
                    hourlyRateRaw.value = rawValue;
                    dailyRateRaw.value = dailyRate;
                    basicSalaryRaw.value = monthlySalary;
                }
                isCalculating = false;
            }

            function calculateFromDaily() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(dailyRateInput.value);
                
                // If input is empty or zero, clear other fields
                if (!dailyRateInput.value.trim() || rawValue === 0) {
                    clearOtherFields('daily');
                    dailyRateRaw.value = '';
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const hourlyRate = rawValue / 8;
                    const monthlySalary = rawValue * 22;
                    
                    hourlyRateInput.value = formatPeso(hourlyRate);
                    basicSalaryInput.value = formatPeso(monthlySalary);
                    
                    // Update raw values for form submission
                    dailyRateRaw.value = rawValue;
                    hourlyRateRaw.value = hourlyRate;
                    basicSalaryRaw.value = monthlySalary;
                }
                isCalculating = false;
            }

            function calculateFromBasic() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(basicSalaryInput.value);
                
                // If input is empty or zero, clear other fields
                if (!basicSalaryInput.value.trim() || rawValue === 0) {
                    clearOtherFields('basic');
                    basicSalaryRaw.value = '';
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const dailyRate = rawValue / 22;
                    const hourlyRate = dailyRate / 8;
                    
                    dailyRateInput.value = formatPeso(dailyRate);
                    hourlyRateInput.value = formatPeso(hourlyRate);
                    
                    // Update raw values for form submission
                    basicSalaryRaw.value = rawValue;
                    dailyRateRaw.value = dailyRate;
                    hourlyRateRaw.value = hourlyRate;
                }
                isCalculating = false;
            }

            // Format input on blur
            function formatInput(input, rawInput) {
                const rawValue = parsePeso(input.value);
                if (rawValue > 0) {
                    input.value = formatPeso(rawValue);
                    rawInput.value = rawValue;
                } else {
                    input.value = '';
                    rawInput.value = '';
                }
            }

            // Handle input formatting and allow only numbers, commas, periods, and peso sign
            function handleSalaryInput(e) {
                const input = e.target;
                let value = input.value;
                
                // Allow backspace, delete, tab, escape, enter
                if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) {
                    return;
                }
                
                // Allow numbers, decimal point, and peso sign
                if (!/[0-9₱.,]/.test(e.key)) {
                    e.preventDefault();
                }
            }

            // Add event listeners to all three fields
            if (hourlyRateInput) {
                hourlyRateInput.addEventListener('input', calculateFromHourly);
                hourlyRateInput.addEventListener('blur', () => formatInput(hourlyRateInput, hourlyRateRaw));
                hourlyRateInput.addEventListener('keydown', handleSalaryInput);
            }
            
            if (dailyRateInput) {
                dailyRateInput.addEventListener('input', calculateFromDaily);
                dailyRateInput.addEventListener('blur', () => formatInput(dailyRateInput, dailyRateRaw));
                dailyRateInput.addEventListener('keydown', handleSalaryInput);
            }
            
            if (basicSalaryInput) {
                basicSalaryInput.addEventListener('input', calculateFromBasic);
                basicSalaryInput.addEventListener('blur', () => formatInput(basicSalaryInput, basicSalaryRaw));
                basicSalaryInput.addEventListener('keydown', handleSalaryInput);
            }

            // Form submission - use raw values for actual form submission
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submitting...'); // Debug log
                    
                    // Parse and set clean numeric values for all salary fields
                    const hourlyValue = parsePeso(hourlyRateInput.value);
                    const dailyValue = parsePeso(dailyRateInput.value);
                    const basicValue = parsePeso(basicSalaryInput.value);
                    
                    // Set clean numeric values or empty strings
                    hourlyRateInput.value = hourlyValue > 0 ? hourlyValue.toString() : '';
                    dailyRateInput.value = dailyValue > 0 ? dailyValue.toString() : '';
                    basicSalaryInput.value = basicValue > 0 ? basicValue.toString() : '0'; // Basic salary is required
                    
                    console.log('Final values being submitted:', {
                        hourly: hourlyRateInput.value,
                        daily: dailyRateInput.value,
                        basic: basicSalaryInput.value
                    });
                    
                    // Remove the hidden fields from form submission since we're using the main inputs
                    if (hourlyRateRaw && hourlyRateRaw.parentNode) {
                        hourlyRateRaw.remove();
                    }
                    if (dailyRateRaw && dailyRateRaw.parentNode) {
                        dailyRateRaw.remove();
                    }
                    if (basicSalaryRaw && basicSalaryRaw.parentNode) {
                        basicSalaryRaw.remove();
                    }
                });
            }
            
            // Benefits Status and Pay Schedule Dynamic Behavior
            const benefitsStatusSelect = document.getElementById('benefits_status');
            const payScheduleSelect = document.getElementById('pay_schedule');
            const paidLeavesInput = document.getElementById('paid_leaves');
            const salaryBreakdownContainer = document.querySelector('.mt-6.p-4.bg-gray-50.rounded-lg');
            
            // Salary rate input fields
            const hourlyRateField = document.getElementById('hourly_rate').closest('div');
            const dailyRateField = document.getElementById('daily_rate').closest('div');
            const weeklyRateField = document.getElementById('weekly_rate').closest('div');
            const semiMonthlyRateField = document.getElementById('semi_monthly_rate').closest('div');
            const monthlyRateField = document.getElementById('basic_salary').closest('div');
            
            // Function to update paid leaves input based on benefits status
            function updatePaidLeavesStatus() {
                const paidLeavesLabel = document.querySelector('label[for="paid_leaves"]');
                
                if (benefitsStatusSelect.value === 'without_benefits') {
                    paidLeavesInput.disabled = true;
                    paidLeavesInput.value = '';
                    paidLeavesInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                    paidLeavesInput.removeAttribute('required');
                    // Update label to remove required indicator
                    paidLeavesLabel.innerHTML = 'Number of Paid Leaves';
                } else {
                    paidLeavesInput.disabled = false;
                    paidLeavesInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    paidLeavesInput.setAttribute('required', 'required');
                    // Update label to show required indicator
                    paidLeavesLabel.innerHTML = 'Number of Paid Leaves <span class="text-red-500">*</span>';
                }
            }
            
            // Function to highlight selected pay schedule rate field
            function highlightPayScheduleRate() {
                // Reset all fields to default styling
                [hourlyRateField, dailyRateField, weeklyRateField, semiMonthlyRateField, monthlyRateField].forEach(field => {
                    const input = field.querySelector('input[type="text"]');
                    const label = field.querySelector('label');
                    
                    if (input) {
                        input.classList.remove('border-green-500', 'bg-green-50', 'focus:border-green-500', 'focus:ring-green-500');
                        input.classList.add('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
                    }
                    if (label) {
                        label.classList.remove('text-green-700', 'font-semibold');
                        label.classList.add('text-gray-700');
                    }
                });
                
                // Highlight the selected pay schedule rate field
                let targetField = null;
                switch (payScheduleSelect.value) {
                    case 'weekly':
                        targetField = weeklyRateField;
                        break;
                    case 'semi_monthly':
                        targetField = semiMonthlyRateField;
                        break;
                    case 'monthly':
                        targetField = monthlyRateField;
                        break;
                }
                
                if (targetField) {
                    const input = targetField.querySelector('input[type="text"]');
                    const label = targetField.querySelector('label');
                    
                    if (input) {
                        input.classList.remove('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
                        input.classList.add('border-green-500', 'bg-green-50', 'focus:border-green-500', 'focus:ring-green-500');
                    }
                    if (label) {
                        label.classList.remove('text-gray-700');
                        label.classList.add('text-green-700', 'font-semibold');
                    }
                }
            }
            
            // Function to update salary breakdown content with real deductions
            async function updateSalaryBreakdown() {
                if (!salaryBreakdownContainer) return;
                
                const breakdownContent = salaryBreakdownContainer.querySelector('.grid');
                if (!breakdownContent) return;
                
                if (benefitsStatusSelect.value === 'with_benefits' && payScheduleSelect.value) {
                    // Show detailed breakdown for employees with benefits
                    let selectedRateLabel = '';
                    let selectedRateSpanId = '';
                    let salaryFieldId = '';
                    
                    switch (payScheduleSelect.value) {
                        case 'weekly':
                            selectedRateLabel = 'Weekly Rate';
                            selectedRateSpanId = 'calc_weekly';
                            salaryFieldId = 'weekly_rate';
                            break;
                        case 'semi_monthly':
                            selectedRateLabel = 'Semi-Monthly Rate';
                            selectedRateSpanId = 'calc_semi';
                            salaryFieldId = 'semi_monthly_rate';
                            break;
                        case 'monthly':
                            selectedRateLabel = 'Monthly Rate';
                            selectedRateSpanId = 'calc_monthly';
                            salaryFieldId = 'basic_salary';
                            break;
                    }
                    
                    if (selectedRateLabel) {
                        // Show loading state
                        breakdownContent.innerHTML = `
                            <div class="col-span-2 md:col-span-5">
                                <div class="p-4 bg-green-50 border border-green-200 rounded-md">
                                    <div class="flex items-center justify-between mb-3">
                                        <h5 class="text-sm font-semibold text-green-800">Estimated Salary Breakdown</h5>
                                        <div class="text-xs text-green-600">Loading...</div>
                                    </div>
                                    <div id="deduction-details">
                                        <div class="animate-pulse">
                                            <div class="h-4 bg-green-200 rounded w-3/4 mb-2"></div>
                                            <div class="h-4 bg-green-200 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Function to fetch and display deductions
                        const updateDeductionCalculation = async () => {
                            const salaryField = document.getElementById(salaryFieldId);
                            if (!salaryField) return;
                            
                            const salaryValue = parsePeso(salaryField.value);
                            if (salaryValue <= 0) {
                                updateDeductionDisplay([], 0, 0, salaryValue);
                                return;
                            }
                            
                            try {
                                const response = await fetch('{{ route("employees.calculate-deductions") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        salary: salaryValue,
                                        benefits_status: benefitsStatusSelect.value,
                                        pay_schedule: payScheduleSelect.value
                                    })
                                });
                                
                                if (response.ok) {
                                    const data = await response.json();
                                    updateDeductionDisplay(data.deductions, data.total_deductions, data.net_pay, salaryValue);
                                } else {
                                    console.error('Error fetching deductions:', response.statusText);
                                    updateDeductionDisplay([], 0, salaryValue, salaryValue);
                                }
                            } catch (error) {
                                console.error('Error calculating deductions:', error);
                                updateDeductionDisplay([], 0, salaryValue, salaryValue);
                            }
                        };
                        
                        // Function to update the display with deduction data
                        const updateDeductionDisplay = (deductions, totalDeductions, netPay, grossSalary) => {
                            const detailsContainer = document.getElementById('deduction-details');
                            if (!detailsContainer) return;
                            
                            let deductionItems = '';
                            if (deductions.length > 0) {
                                deductionItems = deductions.map(deduction => 
                                    `<div class="flex justify-between text-xs text-green-700 mb-1">
                                        <span>${deduction.name}</span>
                                        <span>-${deduction.formatted_amount}</span>
                                    </div>`
                                ).join('');
                            } else {
                                deductionItems = '<div class="text-xs text-green-600 text-center py-2">No deductions applicable</div>';
                            }
                            
                            detailsContainer.innerHTML = `
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm font-medium text-green-800 border-b border-green-200 pb-2 mb-2">
                                        <span>${selectedRateLabel}</span>
                                        <span>₱${grossSalary.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    </div>
                                    ${deductionItems}
                                    ${deductions.length > 0 ? `
                                        <div class="flex justify-between text-xs text-green-700 border-t border-green-200 pt-2 mt-2">
                                            <span class="font-medium">Total Deductions</span>
                                            <span class="font-medium">-₱${totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                        </div>
                                    ` : ''}
                                    <div class="flex justify-between text-sm font-bold text-green-900 border-t-2 border-green-300 pt-2 mt-2">
                                        <span>Estimated Net Pay</span>
                                        <span>₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    </div>
                                </div>
                            `;
                        };
                        
                        // Initial calculation
                        updateDeductionCalculation();
                        
                        // Watch for salary changes
                        const salaryField = document.getElementById(salaryFieldId);
                        if (salaryField) {
                            // Debounce function to avoid too many API calls
                            let debounceTimer;
                            const debouncedUpdate = () => {
                                clearTimeout(debounceTimer);
                                debounceTimer = setTimeout(updateDeductionCalculation, 500);
                            };
                            
                            salaryField.removeEventListener('input', salaryField.deductionListener);
                            salaryField.deductionListener = debouncedUpdate;
                            salaryField.addEventListener('input', salaryField.deductionListener);
                        }
                    }
                } else {
                    // Show default breakdown for employees without benefits or no pay schedule selected
                    breakdownContent.innerHTML = `
                        <div>Hourly: <span id="calc_hourly" class="font-medium text-gray-900">₱0.00</span></div>
                        <div>Daily: <span id="calc_daily" class="font-medium text-gray-900">₱0.00</span></div>
                        <div>Weekly: <span id="calc_weekly" class="font-medium text-gray-900">₱0.00</span></div>
                        <div>Semi-Monthly: <span id="calc_semi" class="font-medium text-gray-900">₱0.00</span></div>
                        <div>Monthly: <span id="calc_monthly" class="font-medium text-gray-900">₱0.00</span></div>
                    `;
                }
            }
            
            // Event listeners for benefits status and pay schedule changes
            if (benefitsStatusSelect) {
                benefitsStatusSelect.addEventListener('change', function() {
                    updatePaidLeavesStatus();
                    updateSalaryBreakdown();
                });
                // Initialize on page load
                updatePaidLeavesStatus();
            }
            
            if (payScheduleSelect) {
                payScheduleSelect.addEventListener('change', function() {
                    highlightPayScheduleRate();
                    updateSalaryBreakdown();
                });
                // Initialize on page load
                highlightPayScheduleRate();
            }
            
            // Initialize salary breakdown on page load
            updateSalaryBreakdown();
            
            // Department and Position Dynamic Filtering
            const departmentSelect = document.getElementById('department_id');
            const positionSelect = document.getElementById('position_id');
            
            if (departmentSelect && positionSelect) {
                // Store all positions for filtering
                const allPositions = Array.from(positionSelect.options).slice(1); // Remove the first "Select Position" option
                
                function filterPositions() {
                    const selectedDeptId = departmentSelect.value;
                    
                    // Clear current position options (keep the first default option)
                    positionSelect.innerHTML = '<option value="">Select Position</option>';
                    
                    if (selectedDeptId) {
                        // Filter and add relevant positions
                        const relevantPositions = allPositions.filter(option => 
                            option.dataset.departmentId === selectedDeptId
                        );
                        
                        relevantPositions.forEach(option => {
                            positionSelect.appendChild(option.cloneNode(true));
                        });
                        
                        // If only one position available, auto-select it
                        if (relevantPositions.length === 1) {
                            positionSelect.value = relevantPositions[0].value;
                        }
                    }
                    
                    // Reset position selection if current selection is not valid for new department
                    if (positionSelect.value && !Array.from(positionSelect.options).some(opt => opt.value === positionSelect.value)) {
                        positionSelect.value = '';
                    }
                }
                
                // Event listener for department change
                departmentSelect.addEventListener('change', filterPositions);
                
                // Initialize on page load
                filterPositions();
            }
            
            // Custom Schedule Handlers
            const timeScheduleSelect = document.getElementById('time_schedule_id');
            const dayScheduleSelect = document.getElementById('day_schedule_id');
            const customTimeModal = document.getElementById('custom-time-modal');
            const customDayModal = document.getElementById('custom-day-modal');
            
            // Custom time schedule modal handlers
            if (timeScheduleSelect && customTimeModal) {
                timeScheduleSelect.addEventListener('change', function() {
                    if (this.value === 'custom') {
                        customTimeModal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    }
                });
                
                // Close modal buttons
                const closeTimeModal = () => {
                    customTimeModal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                    timeScheduleSelect.value = timeScheduleSelect.dataset.previousValue || '';
                };
                
                customTimeModal.querySelector('[data-action="close"]').addEventListener('click', closeTimeModal);
                customTimeModal.querySelector('[data-action="cancel"]').addEventListener('click', closeTimeModal);
                
                // Save custom time schedule
                customTimeModal.querySelector('[data-action="save"]').addEventListener('click', function() {
                    const form = customTimeModal.querySelector('form');
                    const formData = new FormData(form);
                    
                    fetch('{{ route("settings.time-schedules.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Add new option to select
                            const newOption = document.createElement('option');
                            newOption.value = data.schedule.id;
                            newOption.textContent = data.schedule.name + ' (' + data.schedule.time_in + ' - ' + data.schedule.time_out + ')';
                            timeScheduleSelect.insertBefore(newOption, timeScheduleSelect.lastElementChild);
                            
                            // Select the new option
                            timeScheduleSelect.value = data.schedule.id;
                            
                            // Close modal
                            closeTimeModal();
                            
                            // Reset form
                            form.reset();
                            
                            alert('Time schedule created successfully!');
                        } else {
                            alert('Error creating time schedule: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error creating time schedule. Please try again.');
                    });
                });
                
                // Store previous value for cancel functionality
                timeScheduleSelect.addEventListener('focus', function() {
                    this.dataset.previousValue = this.value;
                });
            }
            
            // Custom day schedule modal handlers
            if (dayScheduleSelect && customDayModal) {
                dayScheduleSelect.addEventListener('change', function() {
                    if (this.value === 'custom') {
                        customDayModal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    }
                });
                
                // Close modal buttons
                const closeDayModal = () => {
                    customDayModal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                    dayScheduleSelect.value = dayScheduleSelect.dataset.previousValue || '';
                };
                
                customDayModal.querySelector('[data-action="close"]').addEventListener('click', closeDayModal);
                customDayModal.querySelector('[data-action="cancel"]').addEventListener('click', closeDayModal);
                
                // Save custom day schedule
                customDayModal.querySelector('[data-action="save"]').addEventListener('click', function() {
                    const form = customDayModal.querySelector('form');
                    const formData = new FormData(form);
                    
                    fetch('{{ route("settings.day-schedules.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Add new option to select
                            const newOption = document.createElement('option');
                            newOption.value = data.schedule.id;
                            newOption.textContent = data.schedule.name + ' (' + data.schedule.days_display + ')';
                            dayScheduleSelect.insertBefore(newOption, dayScheduleSelect.lastElementChild);
                            
                            // Select the new option
                            dayScheduleSelect.value = data.schedule.id;
                            
                            // Close modal
                            closeDayModal();
                            
                            // Reset form
                            form.reset();
                            
                            alert('Day schedule created successfully!');
                        } else {
                            alert('Error creating day schedule: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error creating day schedule. Please try again.');
                    });
                });
                
                // Store previous value for cancel functionality
                dayScheduleSelect.addEventListener('focus', function() {
                    this.dataset.previousValue = this.value;
                });
            }
        });
    </script>
    
    <script src="{{ asset('js/salary-calculator.js') }}"></script>
</x-app-layout>
