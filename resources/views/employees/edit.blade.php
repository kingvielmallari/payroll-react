<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Employee') }} - {{ $employee->full_name }}
            </h2>
            <a href="{{ route('employees.show', $employee->employee_number) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Employee
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $employee->first_name) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $employee->middle_name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('middle_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $employee->last_name) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                                <input type="text" name="suffix" id="suffix" value="{{ old('suffix', $employee->suffix) }}" placeholder="Jr., Sr., III"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('suffix')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700">Birth Date <span class="text-red-500">*</span></label>
                                <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('birth_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                                <select name="gender" id="gender" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @error('gender')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="civil_status" class="block text-sm font-medium text-gray-700">Civil Status <span class="text-red-500">*</span></label>
                                <select name="civil_status" id="civil_status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Status</option>
                                    <option value="single" {{ old('civil_status', $employee->civil_status) == 'single' ? 'selected' : '' }}>Single</option>
                                    <option value="married" {{ old('civil_status', $employee->civil_status) == 'married' ? 'selected' : '' }}>Married</option>
                                    <option value="divorced" {{ old('civil_status', $employee->civil_status) == 'divorced' ? 'selected' : '' }}>Divorced</option>
                                    <option value="widowed" {{ old('civil_status', $employee->civil_status) == 'widowed' ? 'selected' : '' }}>Widowed</option>
                                </select>
                                @error('civil_status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $employee->phone) }}" placeholder="09XXXXXXXXX"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6>
                            <label for="address" class="block text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                            <input type="text" name="address" id="address" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                                   value="{{ old('address', $employee->address) }}">
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
                                <input type="email" name="email" id="email" value="{{ old('email', $employee->user->email ?? '') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">User Role <span class="text-red-500">*</span></label>
                                <select name="role" id="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('role', $employee->user?->roles->first()?->name) == $role->name ? 'selected' : '' }}>
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
                            <strong>Note:</strong> To reset the password, leave blank. Otherwise, the current password will remain unchanged.
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
                                <input type="text" name="employee_number" id="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" required
                                       placeholder="EMP-2025-0001"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('employee_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="hire_date" class="block text-sm font-medium text-gray-700">Hire Date <span class="text-red-500">*</span></label>
                                <input type="date" name="hire_date" id="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" required
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
                                        <option value="{{ $department->id }}" {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>
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
                                        <option value="{{ $position->id }}" {{ old('position_id', $employee->position_id) == $position->id ? 'selected' : '' }}>
                                            {{ $position->title }}
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
                                    <option value="regular" {{ old('employment_type', $employee->employment_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="probationary" {{ old('employment_type', $employee->employment_type) == 'probationary' ? 'selected' : '' }}>Probationary</option>
                                    <option value="contractual" {{ old('employment_type', $employee->employment_type) == 'contractual' ? 'selected' : '' }}>Contractual</option>
                                    <option value="part_time" {{ old('employment_type', $employee->employment_type) == 'part_time' ? 'selected' : '' }}>Part Time</option>
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
                                        <option value="{{ $schedule->id }}" {{ old('time_schedule_id', $employee->timeSchedule?->id) == $schedule->id ? 'selected' : '' }}>
                                            {{ $schedule->name }} ({{ $schedule->time_range_display }})
                                        </option>
                                    @endforeach
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
                                    @foreach($daySchedules as $schedule)
                                        <option value="{{ $schedule->id }}" {{ old('day_schedule_id', $employee->daySchedule?->id) == $schedule->id ? 'selected' : '' }}>
                                            {{ $schedule->name }} ({{ $schedule->days_display }})
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
                                    <option value="monthly" {{ old('pay_schedule', $employee->pay_schedule ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="semi_monthly" {{ old('pay_schedule', $employee->pay_schedule ?? '') == 'semi_monthly' ? 'selected' : '' }}>Semi-Monthly</option>
                                    <option value="weekly" {{ old('pay_schedule', $employee->pay_schedule ?? '') == 'weekly' ? 'selected' : '' }}>Weekly</option>
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
                                    <option value="with_benefits" {{ old('benefits_status', $employee->benefits_status) == 'with_benefits' ? 'selected' : '' }}>With Benefits</option>
                                    <option value="without_benefits" {{ old('benefits_status', $employee->benefits_status) == 'without_benefits' ? 'selected' : '' }}>Without Benefits</option>
                                </select>
                                @error('benefits_status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Determines eligibility for health insurance, SSS, etc.</p>
                            </div>

                            <div>
                                <label for="paid_leaves" class="block text-sm font-medium text-gray-700">Number of Paid Leaves <span class="text-red-500">*</span></label>
                                <input type="number" name="paid_leaves" id="paid_leaves" value="{{ old('paid_leaves', $employee->paid_leaves) }}" required min="0" max="365"
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
                                    <option value="active" {{ old('employment_status', $employee->employment_status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('employment_status', $employee->employment_status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="terminated" {{ old('employment_status', $employee->employment_status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                    <option value="resigned" {{ old('employment_status', $employee->employment_status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
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
                                <input type="text" name="hourly_rate" id="hourly_rate" value="{{ old('hourly_rate', $employee->hourly_rate ? number_format($employee->hourly_rate, 2, '.', '') : '') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="hourly_rate_raw" id="hourly_rate_raw">
                                @error('hourly_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per hour rate</p>
                            </div>

                            <div>
                                <label for="daily_rate" class="block text-sm font-medium text-gray-700">Daily Rate</label>
                                <input type="text" name="daily_rate" id="daily_rate" value="{{ old('daily_rate', $employee->daily_rate ? number_format($employee->daily_rate, 2, '.', '') : '') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="daily_rate_raw" id="daily_rate_raw">
                                @error('daily_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per day rate</p>
                            </div>

                            <div>
                                <label for="weekly_rate" class="block text-sm font-medium text-gray-700">Weekly Rate</label>
                                <input type="text" name="weekly_rate" id="weekly_rate" value="{{ old('weekly_rate', $employee->weekly_rate ? number_format($employee->weekly_rate, 2, '.', '') : '') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="weekly_rate_raw" id="weekly_rate_raw">
                                @error('weekly_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per week rate</p>
                            </div>

                            <div>
                                <label for="semi_monthly_rate" class="block text-sm font-medium text-gray-700">Semi-Monthly Rate</label>
                                <input type="text" name="semi_monthly_rate" id="semi_monthly_rate" value="{{ old('semi_monthly_rate', $employee->semi_monthly_rate ? number_format($employee->semi_monthly_rate, 2, '.', '') : '') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="semi_monthly_rate_raw" id="semi_monthly_rate_raw">
                                @error('semi_monthly_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Twice per month</p>
                            </div>

                            <div>
                                <label for="basic_salary" class="block text-sm font-medium text-gray-700">Monthly Rate</label>
                                <input type="text" name="basic_salary" id="basic_salary" value="{{ old('basic_salary', $employee->basic_salary ? number_format($employee->basic_salary, 2, '.', '') : '') }}" placeholder="₱0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm salary-input">
                                <input type="hidden" name="basic_salary_raw" id="basic_salary_raw">
                                @error('basic_salary')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Per month rate</p>
                            </div>
                        </div>
                        
                        <!-- Salary Calculation Summary -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Salary Breakdown</h4>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs text-gray-600">
                                <div>Hourly: <span id="calc_hourly" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Daily: <span id="calc_daily" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Weekly: <span id="calc_weekly" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Semi-Monthly: <span id="calc_semi" class="font-medium text-gray-900">₱0.00</span></div>
                                <div>Monthly: <span id="calc_monthly" class="font-medium text-gray-900">₱0.00</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Government IDs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Government IDs</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="sss_number" class="block text-sm font-medium text-gray-700">SSS Number</label>
                                <input type="text" name="sss_number" id="sss_number" value="{{ old('sss_number', $employee->sss_number) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('sss_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="philhealth_number" class="block text-sm font-medium text-gray-700">PhilHealth Number</label>
                                <input type="text" name="philhealth_number" id="philhealth_number" value="{{ old('philhealth_number', $employee->philhealth_number) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('philhealth_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="pagibig_number" class="block text-sm font-medium text-gray-700">Pag-IBIG Number</label>
                                <input type="text" name="pagibig_number" id="pagibig_number" value="{{ old('pagibig_number', $employee->pagibig_number) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('pagibig_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="tin_number" class="block text-sm font-medium text-gray-700">TIN Number</label>
                                <input type="text" name="tin_number" id="tin_number" value="{{ old('tin_number', $employee->tin_number) }}"
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
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('emergency_contact_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="emergency_contact_relationship" class="block text-sm font-medium text-gray-700">Relationship</label>
                                <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('emergency_contact_relationship')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}"
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
                                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $employee->bank_name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('bank_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bank_account_number" class="block text-sm font-medium text-gray-700">Account Number</label>
                                <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('bank_account_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bank_account_name" class="block text-sm font-medium text-gray-700">Account Name</label>
                                <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $employee->bank_account_name) }}"
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
                            <a href="{{ route('employees.show', $employee->employee_number) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Employee
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
            text-align: left;
        }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get references to all salary fields
            const hourlyRateInput = document.getElementById('hourly_rate');
            const dailyRateInput = document.getElementById('daily_rate');
            const weeklyRateInput = document.getElementById('weekly_rate');
            const semiMonthlyRateInput = document.getElementById('semi_monthly_rate');
            const basicSalaryInput = document.getElementById('basic_salary');
            
            // Get references to raw value fields
            const hourlyRateRaw = document.getElementById('hourly_rate_raw');
            const dailyRateRaw = document.getElementById('daily_rate_raw');
            const weeklyRateRaw = document.getElementById('weekly_rate_raw');
            const semiMonthlyRateRaw = document.getElementById('semi_monthly_rate_raw');
            const basicSalaryRaw = document.getElementById('basic_salary_raw');
            
            // Calculation display elements
            const calcHourly = document.getElementById('calc_hourly');
            const calcDaily = document.getElementById('calc_daily');
            const calcWeekly = document.getElementById('calc_weekly');
            const calcSemi = document.getElementById('calc_semi');
            const calcMonthly = document.getElementById('calc_monthly');
            
            let isCalculating = false;

            // Format number as peso
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

            // Update calculation breakdown display
            function updateCalculationDisplay(rates) {
                if (calcHourly) calcHourly.textContent = formatPeso(rates.hourly || 0);
                if (calcDaily) calcDaily.textContent = formatPeso(rates.daily || 0);
                if (calcWeekly) calcWeekly.textContent = formatPeso(rates.weekly || 0);
                if (calcSemi) calcSemi.textContent = formatPeso(rates.semiMonthly || 0);
                if (calcMonthly) calcMonthly.textContent = formatPeso(rates.monthly || 0);
            }

            // Clear all other fields except the current one
            function clearOtherFields(currentField) {
                if (isCalculating) return;
                
                const fieldMap = {
                    'hourly': hourlyRateInput,
                    'daily': dailyRateInput,
                    'weekly': weeklyRateInput,
                    'semi': semiMonthlyRateInput,
                    'basic': basicSalaryInput
                };
                
                const rawMap = {
                    'hourly': hourlyRateRaw,
                    'daily': dailyRateRaw,
                    'weekly': weeklyRateRaw,
                    'semi': semiMonthlyRateRaw,
                    'basic': basicSalaryRaw
                };
                
                Object.keys(fieldMap).forEach(key => {
                    if (key !== currentField) {
                        if (fieldMap[key]) fieldMap[key].value = '';
                        if (rawMap[key]) rawMap[key].value = '';
                    }
                });
            }

            // Calculate all rates from hourly rate
            function calculateFromHourly() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(hourlyRateInput.value);
                
                if (!hourlyRateInput.value.trim() || rawValue === 0) {
                    clearOtherFields('hourly');
                    if (hourlyRateRaw) hourlyRateRaw.value = '';
                    updateCalculationDisplay({});
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const rates = {
                        hourly: rawValue,
                        daily: rawValue * 8,
                        weekly: rawValue * 8 * 5,
                        semiMonthly: rawValue * 8 * 11,
                        monthly: rawValue * 8 * 22
                    };
                    
                    if (dailyRateInput) dailyRateInput.value = formatPeso(rates.daily);
                    if (weeklyRateInput) weeklyRateInput.value = formatPeso(rates.weekly);
                    if (semiMonthlyRateInput) semiMonthlyRateInput.value = formatPeso(rates.semiMonthly);
                    if (basicSalaryInput) basicSalaryInput.value = formatPeso(rates.monthly);
                    
                    // Update raw values
                    if (hourlyRateRaw) hourlyRateRaw.value = rates.hourly;
                    if (dailyRateRaw) dailyRateRaw.value = rates.daily;
                    if (weeklyRateRaw) weeklyRateRaw.value = rates.weekly;
                    if (semiMonthlyRateRaw) semiMonthlyRateRaw.value = rates.semiMonthly;
                    if (basicSalaryRaw) basicSalaryRaw.value = rates.monthly;
                    
                    updateCalculationDisplay(rates);
                }
                isCalculating = false;
            }

            // Calculate all rates from daily rate
            function calculateFromDaily() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(dailyRateInput.value);
                
                if (!dailyRateInput.value.trim() || rawValue === 0) {
                    clearOtherFields('daily');
                    if (dailyRateRaw) dailyRateRaw.value = '';
                    updateCalculationDisplay({});
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const rates = {
                        hourly: rawValue / 8,
                        daily: rawValue,
                        weekly: rawValue * 5,
                        semiMonthly: rawValue * 11,
                        monthly: rawValue * 22
                    };
                    
                    if (hourlyRateInput) hourlyRateInput.value = formatPeso(rates.hourly);
                    if (weeklyRateInput) weeklyRateInput.value = formatPeso(rates.weekly);
                    if (semiMonthlyRateInput) semiMonthlyRateInput.value = formatPeso(rates.semiMonthly);
                    if (basicSalaryInput) basicSalaryInput.value = formatPeso(rates.monthly);
                    
                    // Update raw values
                    if (hourlyRateRaw) hourlyRateRaw.value = rates.hourly;
                    if (dailyRateRaw) dailyRateRaw.value = rates.daily;
                    if (weeklyRateRaw) weeklyRateRaw.value = rates.weekly;
                    if (semiMonthlyRateRaw) semiMonthlyRateRaw.value = rates.semiMonthly;
                    if (basicSalaryRaw) basicSalaryRaw.value = rates.monthly;
                    
                    updateCalculationDisplay(rates);
                }
                isCalculating = false;
            }

            // Calculate all rates from weekly rate
            function calculateFromWeekly() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(weeklyRateInput.value);
                
                if (!weeklyRateInput.value.trim() || rawValue === 0) {
                    clearOtherFields('weekly');
                    if (weeklyRateRaw) weeklyRateRaw.value = '';
                    updateCalculationDisplay({});
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const rates = {
                        hourly: rawValue / (8 * 5),
                        daily: rawValue / 5,
                        weekly: rawValue,
                        semiMonthly: rawValue * 2.2,
                        monthly: rawValue * 4.4
                    };
                    
                    if (hourlyRateInput) hourlyRateInput.value = formatPeso(rates.hourly);
                    if (dailyRateInput) dailyRateInput.value = formatPeso(rates.daily);
                    if (semiMonthlyRateInput) semiMonthlyRateInput.value = formatPeso(rates.semiMonthly);
                    if (basicSalaryInput) basicSalaryInput.value = formatPeso(rates.monthly);
                    
                    // Update raw values
                    if (hourlyRateRaw) hourlyRateRaw.value = rates.hourly;
                    if (dailyRateRaw) dailyRateRaw.value = rates.daily;
                    if (weeklyRateRaw) weeklyRateRaw.value = rates.weekly;
                    if (semiMonthlyRateRaw) semiMonthlyRateRaw.value = rates.semiMonthly;
                    if (basicSalaryRaw) basicSalaryRaw.value = rates.monthly;
                    
                    updateCalculationDisplay(rates);
                }
                isCalculating = false;
            }

            // Calculate all rates from semi-monthly rate
            function calculateFromSemiMonthly() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(semiMonthlyRateInput.value);
                
                if (!semiMonthlyRateInput.value.trim() || rawValue === 0) {
                    clearOtherFields('semi');
                    if (semiMonthlyRateRaw) semiMonthlyRateRaw.value = '';
                    updateCalculationDisplay({});
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const rates = {
                        hourly: rawValue / (8 * 11),
                        daily: rawValue / 11,
                        weekly: rawValue / 2.2,
                        semiMonthly: rawValue,
                        monthly: rawValue * 2
                    };
                    
                    if (hourlyRateInput) hourlyRateInput.value = formatPeso(rates.hourly);
                    if (dailyRateInput) dailyRateInput.value = formatPeso(rates.daily);
                    if (weeklyRateInput) weeklyRateInput.value = formatPeso(rates.weekly);
                    if (basicSalaryInput) basicSalaryInput.value = formatPeso(rates.monthly);
                    
                    // Update raw values
                    if (hourlyRateRaw) hourlyRateRaw.value = rates.hourly;
                    if (dailyRateRaw) dailyRateRaw.value = rates.daily;
                    if (weeklyRateRaw) weeklyRateRaw.value = rates.weekly;
                    if (semiMonthlyRateRaw) semiMonthlyRateRaw.value = rates.semiMonthly;
                    if (basicSalaryRaw) basicSalaryRaw.value = rates.monthly;
                    
                    updateCalculationDisplay(rates);
                }
                isCalculating = false;
            }

            // Calculate all rates from monthly salary
            function calculateFromBasic() {
                if (isCalculating) return;
                
                const rawValue = parsePeso(basicSalaryInput.value);
                
                if (!basicSalaryInput.value.trim() || rawValue === 0) {
                    clearOtherFields('basic');
                    if (basicSalaryRaw) basicSalaryRaw.value = '';
                    updateCalculationDisplay({});
                    return;
                }
                
                isCalculating = true;
                
                if (rawValue > 0) {
                    const rates = {
                        hourly: rawValue / (8 * 22),
                        daily: rawValue / 22,
                        weekly: rawValue / 4.4,
                        semiMonthly: rawValue / 2,
                        monthly: rawValue
                    };
                    
                    if (hourlyRateInput) hourlyRateInput.value = formatPeso(rates.hourly);
                    if (dailyRateInput) dailyRateInput.value = formatPeso(rates.daily);
                    if (weeklyRateInput) weeklyRateInput.value = formatPeso(rates.weekly);
                    if (semiMonthlyRateInput) semiMonthlyRateInput.value = formatPeso(rates.semiMonthly);
                    
                    // Update raw values
                    if (hourlyRateRaw) hourlyRateRaw.value = rates.hourly;
                    if (dailyRateRaw) dailyRateRaw.value = rates.daily;
                    if (weeklyRateRaw) weeklyRateRaw.value = rates.weekly;
                    if (semiMonthlyRateRaw) semiMonthlyRateRaw.value = rates.semiMonthly;
                    if (basicSalaryRaw) basicSalaryRaw.value = rates.monthly;
                    
                    updateCalculationDisplay(rates);
                }
                isCalculating = false;
            }

            // Format input on blur
            function formatInput(input, rawInput) {
                const rawValue = parsePeso(input.value);
                if (rawValue > 0) {
                    input.value = formatPeso(rawValue);
                    if (rawInput) rawInput.value = rawValue;
                } else {
                    input.value = '';
                    if (rawInput) rawInput.value = '';
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

            // Format existing values on page load and initialize calculation display
            function formatExistingValues() {
                let hasValue = false;
                let rates = {};
                
                // Check if any field has a value and format them
                if (hourlyRateInput && hourlyRateInput.value && !isNaN(parseFloat(hourlyRateInput.value))) {
                    const value = parseFloat(hourlyRateInput.value);
                    hourlyRateInput.value = formatPeso(value);
                    if (hourlyRateRaw) hourlyRateRaw.value = value;
                    rates.hourly = value;
                    hasValue = true;
                }
                
                if (dailyRateInput && dailyRateInput.value && !isNaN(parseFloat(dailyRateInput.value))) {
                    const value = parseFloat(dailyRateInput.value);
                    dailyRateInput.value = formatPeso(value);
                    if (dailyRateRaw) dailyRateRaw.value = value;
                    rates.daily = value;
                    hasValue = true;
                }
                
                if (weeklyRateInput && weeklyRateInput.value && !isNaN(parseFloat(weeklyRateInput.value))) {
                    const value = parseFloat(weeklyRateInput.value);
                    weeklyRateInput.value = formatPeso(value);
                    if (weeklyRateRaw) weeklyRateRaw.value = value;
                    rates.weekly = value;
                    hasValue = true;
                }
                
                if (semiMonthlyRateInput && semiMonthlyRateInput.value && !isNaN(parseFloat(semiMonthlyRateInput.value))) {
                    const value = parseFloat(semiMonthlyRateInput.value);
                    semiMonthlyRateInput.value = formatPeso(value);
                    if (semiMonthlyRateRaw) semiMonthlyRateRaw.value = value;
                    rates.semiMonthly = value;
                    hasValue = true;
                }
                
                if (basicSalaryInput && basicSalaryInput.value && !isNaN(parseFloat(basicSalaryInput.value))) {
                    const value = parseFloat(basicSalaryInput.value);
                    basicSalaryInput.value = formatPeso(value);
                    if (basicSalaryRaw) basicSalaryRaw.value = value;
                    rates.monthly = value;
                    hasValue = true;
                }
                
                // If we have values, update the calculation display
                if (hasValue) {
                    updateCalculationDisplay(rates);
                }
            }

            // Initialize on page load
            formatExistingValues();

            // Add event listeners to all salary fields
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
            
            if (weeklyRateInput) {
                weeklyRateInput.addEventListener('input', calculateFromWeekly);
                weeklyRateInput.addEventListener('blur', () => formatInput(weeklyRateInput, weeklyRateRaw));
                weeklyRateInput.addEventListener('keydown', handleSalaryInput);
            }
            
            if (semiMonthlyRateInput) {
                semiMonthlyRateInput.addEventListener('input', calculateFromSemiMonthly);
                semiMonthlyRateInput.addEventListener('blur', () => formatInput(semiMonthlyRateInput, semiMonthlyRateRaw));
                semiMonthlyRateInput.addEventListener('keydown', handleSalaryInput);
            }
            
            if (basicSalaryInput) {
                basicSalaryInput.addEventListener('input', calculateFromBasic);
                basicSalaryInput.addEventListener('blur', () => formatInput(basicSalaryInput, basicSalaryRaw));
                basicSalaryInput.addEventListener('keydown', handleSalaryInput);
            }

            // Benefits status and paid leaves handling
            const benefitsStatusSelect = document.getElementById('benefits_status');
            const paidLeavesInput = document.getElementById('paid_leaves');
            
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
            
            // Event listener for benefits status changes
            if (benefitsStatusSelect) {
                benefitsStatusSelect.addEventListener('change', function() {
                    updatePaidLeavesStatus();
                });
                // Initialize on page load
                updatePaidLeavesStatus();
            }

            // Form submission - use clean numeric values for actual form submission
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submitting...'); // Debug log
                    
                    // Parse and set clean numeric values for all salary fields
                    const hourlyValue = parsePeso(hourlyRateInput ? hourlyRateInput.value : '');
                    const dailyValue = parsePeso(dailyRateInput ? dailyRateInput.value : '');
                    const weeklyValue = parsePeso(weeklyRateInput ? weeklyRateInput.value : '');
                    const semiMonthlyValue = parsePeso(semiMonthlyRateInput ? semiMonthlyRateInput.value : '');
                    const basicValue = parsePeso(basicSalaryInput ? basicSalaryInput.value : '');
                    
                    // Set clean numeric values or empty strings
                    if (hourlyRateInput) hourlyRateInput.value = hourlyValue > 0 ? hourlyValue.toString() : '';
                    if (dailyRateInput) dailyRateInput.value = dailyValue > 0 ? dailyValue.toString() : '';
                    if (weeklyRateInput) weeklyRateInput.value = weeklyValue > 0 ? weeklyValue.toString() : '';
                    if (semiMonthlyRateInput) semiMonthlyRateInput.value = semiMonthlyValue > 0 ? semiMonthlyValue.toString() : '';
                    if (basicSalaryInput) basicSalaryInput.value = basicValue > 0 ? basicValue.toString() : '0'; // Basic salary is required
                    
                    console.log('Final values being submitted:', {
                        hourly: hourlyRateInput ? hourlyRateInput.value : '',
                        daily: dailyRateInput ? dailyRateInput.value : '',
                        weekly: weeklyRateInput ? weeklyRateInput.value : '',
                        semiMonthly: semiMonthlyRateInput ? semiMonthlyRateInput.value : '',
                        basic: basicSalaryInput ? basicSalaryInput.value : ''
                    });
                    
                    // Remove the hidden fields from form submission since we're using the main inputs
                    const hiddenFields = [hourlyRateRaw, dailyRateRaw, weeklyRateRaw, semiMonthlyRateRaw, basicSalaryRaw];
                    hiddenFields.forEach(field => {
                        if (field && field.parentNode) {
                            field.remove();
                        }
                    });
                });
            }
        });
    </script>
    
    <script src="{{ asset('js/salary-calculator.js') }}"></script>
    @endpush
</x-app-layout>
