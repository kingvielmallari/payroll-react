<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Notifications -->
            @isset($notifications)
                @if(count($notifications) > 0)
                    <div class="mb-6">
                        @foreach($notifications as $notification)
                            <div class="bg-{{ $notification['type'] === 'warning' ? 'yellow' : 'blue' }}-50 border-l-4 border-{{ $notification['type'] === 'warning' ? 'yellow' : 'blue' }}-400 p-4 mb-2">
                                <div class="flex">
                                    <div class="ml-3">
                                        <p class="text-sm text-{{ $notification['type'] === 'warning' ? 'yellow' : 'blue' }}-700">
                                            <a href="{{ $notification['link'] ?? '#' }}" class="underline">
                                                {{ $notification['message'] }}
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endisset

            <!-- Statistics Cards -->
            @isset($stats)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @foreach($stats as $key => $value)
                        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="text-2xl text-gray-400">
                                            @switch($key)
                                                @case('total_employees')
                                                    👥
                                                    @break
                                                @case('pending_time_logs')
                                                    ⏰
                                                    @break
                                                @case('pending_leave_requests')
                                                    📋
                                                    @break
                                                @case('active_payrolls')
                                                    💰
                                                    @break
                                                @case('monthly_payroll')
                                                    💵
                                                    @break
                                                @case('my_time_logs')
                                                    ⏰
                                                    @break
                                                @case('my_leave_requests')
                                                    📋
                                                    @break
                                                @case('pending_leaves')
                                                    ⏳
                                                    @break
                                                @case('latest_payroll')
                                                    💰
                                                    @break
                                                @default
                                                    📊
                                            @endswitch
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                {{ ucwords(str_replace('_', ' ', $key)) }}
                                            </dt>
                                            <dd class="flex items-baseline">
                                                <div class="text-2xl font-semibold text-gray-900">
                                                    @if($key === 'monthly_payroll')
                                                        ₱{{ number_format($value, 2) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endisset

            <!-- Performance Overview -->
            @isset($topPerformers)
                @if($topPerformers->isNotEmpty())
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Top Performers -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0">
                                    <div class="text-2xl">🏆</div>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-lg font-medium text-gray-900">Top Performers</h3>
                                    <p class="text-sm text-gray-500">Based on {{ isset($currentMonth) ? $currentMonth->format('F Y') : now()->format('F Y') }} DTR & calculated salary</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                @foreach($topPerformers as $index => $data)
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="text-sm font-bold text-green-600 w-6">{{ $index + 1 }}.</div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $data['employee']->full_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $data['employee']->department->name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-green-600">₱{{ number_format($data['calculated_salary'], 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($data['total_hours'], 1) }} hrs</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Least Performers -->
                    @isset($leastPerformers)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0">
                                    <div class="text-2xl">📈</div>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-lg font-medium text-gray-900">Needs Attention</h3>
                                    <p class="text-sm text-gray-500">Employees with lower DTR performance</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                @foreach($leastPerformers as $index => $data)
                                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="text-sm font-bold text-orange-600 w-6">{{ $index + 1 }}.</div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $data['employee']->full_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $data['employee']->department->name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-orange-600">₱{{ number_format($data['calculated_salary'], 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($data['total_hours'], 1) }} hrs</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endisset
                </div>
                @endif
            @endisset

            <!-- Quick Actions & Recent Activities -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            @can('create employees')
                                <a href="{{ route('employees.create') }}" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                    <span class="text-blue-600 mr-3">👤</span>
                                    <span class="text-blue-700 font-medium">Add New Employee</span>
                                </a>
                            @endcan
                            
                            @can('view own time logs')
                                <a href="{{ route('my-time-logs') }}" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                                    <span class="text-purple-600 mr-3">⏰</span>
                                    <span class="text-purple-700 font-medium">My Time Logs</span>
                                </a>
                            @endcan
                            
                            @can('create own leave requests')
                                <a href="#" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                                    <span class="text-yellow-600 mr-3">📋</span>
                                    <span class="text-yellow-700 font-medium">Request Leave</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activities</h3>
                        @isset($recentActivities)
                            @if(count($recentActivities) > 0)
                                <div class="space-y-3">
                                    @foreach($recentActivities as $activity)
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <div class="text-sm text-gray-400">
                                                    @switch($activity['type'])
                                                        @case('payroll')
                                                            💰
                                                            @break
                                                        @case('leave')
                                                            📋
                                                            @break
                                                        @case('time_log')
                                                            ⏰
                                                            @break
                                                        @default
                                                            📊
                                                    @endswitch
                                                </div>
                                            </div>
                                            <div class="ml-3 min-w-0 flex-1">
                                                <p class="text-sm text-gray-900">{{ $activity['message'] }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $activity['date']->diffForHumans() }} by {{ $activity['user'] }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">No recent activities.</p>
                            @endif
                        @endisset
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
