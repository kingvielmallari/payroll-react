<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    DTR Management
                </h2>
                <p class="text-sm text-gray-600 mt-1">Period: {{ $periodLabel }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Payrolls
                </a>
                <a href="{{ route('dtr.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    DTR Management
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">DTR Records for {{ $periodLabel }}</h3>
                        <div class="text-sm text-gray-500">
                            Total Records: {{ $dtrs->count() }}
                        </div>
                    </div>

                    @if($dtrs->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Department
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Period
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Hours
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($dtrs as $dtr)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-blue-600">
                                                            {{ substr($dtr->employee->first_name, 0, 1) }}{{ substr($dtr->employee->last_name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $dtr->employee->user->name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ $dtr->employee->employee_number }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $dtr->employee->department->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $dtr->period_start->format('M d') }} - {{ $dtr->period_end->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="text-blue-600 font-medium">{{ number_format($dtr->total_regular_hours, 1) }}h Regular</span>
                                                @if($dtr->total_overtime_hours > 0)
                                                    <span class="text-orange-600 text-xs">{{ number_format($dtr->total_overtime_hours, 1) }}h Overtime</span>
                                                @endif
                                                @if($dtr->total_late_hours > 0)
                                                    <span class="text-red-600 text-xs">{{ number_format($dtr->total_late_hours, 1) }}h Late</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                {{ $dtr->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $dtr->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $dtr->status === 'pending' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $dtr->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                                {{ ucfirst($dtr->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            @can('view time logs')
                                                <a href="{{ route('dtr.show', $dtr) }}" 
                                                   class="text-blue-600 hover:text-blue-900">View</a>
                                            @endcan
                                            @can('edit time logs')
                                                <a href="{{ route('dtr.edit', $dtr) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            @endcan
                                            @can('delete time logs')
                                                <form action="{{ route('dtr.destroy', $dtr) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900"
                                                            onclick="return confirm('Are you sure you want to delete this DTR?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 12h6m-6 4h6m2 5l3-3m-3 3l3 3m2-9V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V9"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No DTR records found</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No DTR records exist for this period.
                            </p>
                        </div>
                    @endif

                    <!-- Summary Information -->
                    @if($dtrs->count() > 0)
                        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">DTR Management Tips</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Click "Edit" to update time logs for each employee</li>
                                            <li>Use "View" to see detailed DTR information</li>
                                            <li>DTR records can be deleted if needed</li>
                                            <li>Regular hours are calculated based on employee schedule</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
