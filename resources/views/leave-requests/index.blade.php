@extends('layouts.app')

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                @hasrole('Employee')
                                    My Leave Requests
                                @else
                                    Leave Requests Management
                                @endhasrole
                            </h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                                @hasrole('Employee')
                                    Track your paid leave requests and their status.
                                @else
                                    Review and manage employee leave requests.
                                @endhasrole
                            </p>
                        </div>
                        @hasrole('Employee')
                            @can('create leave requests')
                                <a href="{{ route('leave-requests.create') }}" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Submit Leave Request
                                </a>
                            @endcan
                        @endhasrole
                        @hasanyrole('System Administrator|HR Head|HR Staff')
                            <div class="space-x-2">
                                <span class="text-sm text-gray-500">Total: {{ $leaveRequests->total() }}</span>
                            </div>
                        @endhasanyrole
                    </div>
                </div>

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4 mx-4 mt-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4 mx-4 mt-4" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden">
                    @if($leaveRequests->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        @hasanyrole('System Administrator|HR Head|HR Staff')
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Employee
                                            </th>
                                        @endhasanyrole
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Leave Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Dates
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Days
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Submitted
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($leaveRequests as $request)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            @hasanyrole('System Administrator|HR Head|HR Staff')
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $request->employee->first_name ?? 'N/A' }} {{ $request->employee->last_name ?? '' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $request->employee->employee_code ?? 'N/A' }}
                                                    </div>
                                                </td>
                                            @endhasanyrole
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $request->leave_type_label }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $request->start_date->format('M d, Y') }}
                                                    @if($request->start_date->format('Y-m-d') !== $request->end_date->format('Y-m-d'))
                                                        <br>to {{ $request->end_date->format('M d, Y') }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $request->days_requested }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                                                    @elseif($request->status === 'approved') bg-green-100 text-green-800
                                                    @elseif($request->status === 'rejected') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst($request->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $request->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="{{ route('leave-requests.show', $request) }}" 
                                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                        View
                                                    </a>
                                                    
                                                    @hasrole('Employee')
                                                        @if($request->status === 'pending')
                                                            <a href="{{ route('leave-requests.edit', $request) }}" 
                                                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                                Edit
                                                            </a>
                                                        @endif
                                                    @endhasrole
                                                    
                                                    @hasanyrole('System Administrator|HR Head|HR Staff')
                                                        @if($request->status === 'pending')
                                                            <a href="{{ route('leave-requests.edit', $request) }}" 
                                                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                                Review
                                                            </a>
                                                        @endif
                                                    @endhasanyrole
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($leaveRequests->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                                {{ $leaveRequests->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No leave requests found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @hasrole('Employee')
                                    You haven't submitted any leave requests yet.
                                @else
                                    No leave requests have been submitted by employees.
                                @endhasrole
                            </p>
                            @hasrole('Employee')
                                @can('create leave requests')
                                    <div class="mt-6">
                                        <a href="{{ route('leave-requests.create') }}" 
                                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Submit Leave Request
                                        </a>
                                    </div>
                                @endcan
                            @endhasrole
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection